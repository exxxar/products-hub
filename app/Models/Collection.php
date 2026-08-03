<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Collection extends Model
{
    public const TYPE_CUSTOM = 'custom';
    public const TYPE_ALL_PRODUCTS = 'all_products';

    public const PRICING_SUM = 'sum';
    public const PRICING_FIXED = 'fixed';

    protected $fillable = [
        'workspace_id',
        'name',
        'description',
        'short_description',
        'type',
        'pricing_type',
        'fixed_price',
        'discount_percent',
        'image_url',
        'is_active',
        'in_stop_list',
        'sort_order',
    ];

    protected $casts = [
        'fixed_price' => 'float',
        'discount_percent' => 'integer',
        'is_active' => 'boolean',
        'in_stop_list' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $appends = [
        'calculated_price',
        'calculated_old_price',
        'products_count',
        'discount_amount',
        'final_price',
        'type_label',
        'rule_description',
    ];

    // ============================================================
    // Связи
    // ============================================================

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Категории коллекции с правилами выбора (новая логика)
     */
    public function collectionCategories()
    {
        return $this->hasMany(CollectionCategory::class)
            ->orderBy('sort_order');
    }

    /**
     * Прямая привязка товаров — используется только для type=all_products
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'collection_product')
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    // ============================================================
    // Получение всех товаров коллекции
    // ============================================================

    /**
     * Все товары коллекции (плоский список)
     */
    public function getCollectionProducts()
    {
        if ($this->type === self::TYPE_ALL_PRODUCTS) {
            return $this->products;
        }

        // custom: объединяем товары всех категорий коллекции
        return $this->collectionCategories
            ->flatMap(fn($cc) => $cc->products)
            ->unique('id')
            ->values();
    }

    /**
     * Товары, сгруппированные по категориям (для вывода клиенту)
     */
    public function getGroupedProducts()
    {
        if ($this->type === self::TYPE_ALL_PRODUCTS) {
            return [[
                'category_id' => null,
                'category_name' => 'Все товары',
                'selection_rule' => CollectionCategory::RULE_ALL,
                'rule_label' => 'Все товары workspace',
                'products' => $this->products,
                'subtotal' => (float) $this->products->sum('price'),
            ]];
        }

        return $this->collectionCategories
            ->map(fn(CollectionCategory $cc) => [
                'collection_category_id' => $cc->id,
                'category_id' => $cc->category_id,
                'category_name' => $cc->category_name,
                'selection_rule' => $cc->selection_rule,
                'rule_label' => $cc->rule_label,
                'products' => $cc->products,
                'subtotal' => (float) $cc->subtotal,
            ])
            ->values()
            ->all();
    }

    // ============================================================
    // Ценообразование
    // ============================================================

    /**
     * Сумма цен всех товаров коллекции
     */
    public function getProductsSumAttribute(): float
    {
        return (float) $this->getCollectionProducts()->sum('price');
    }

    /**
     * Базовая цена коллекции (до скидки)
     */
    public function getCalculatedPriceAttribute(): float
    {
        if ($this->pricing_type === self::PRICING_FIXED && $this->fixed_price !== null) {
            return (float) $this->fixed_price;
        }
        return $this->products_sum;
    }

    /**
     * Сумма старых цен товаров (для визуализации «было/стало»)
     */
    public function getCalculatedOldPriceAttribute(): ?float
    {
        if ($this->pricing_type === self::PRICING_FIXED) {
            // Для фикс-цены старая = базовая сумма товаров
            $sum = $this->products_sum;
            return $sum > 0 ? $sum : null;
        }

        $oldSum = $this->getCollectionProducts()->sum(fn($p) => $p->old_price ?? $p->price);
        return $oldSum > 0 ? (float) $oldSum : null;
    }

    /**
     * Размер скидки в рублях
     */
    public function getDiscountAmountAttribute(): float
    {
        $rawDiscount = $this->attributes['discount_percent'] ?? 0;

        if (!$rawDiscount || $rawDiscount <= 0) {
            return 0.0;
        }
        return (float) round($this->calculated_price * $rawDiscount / 100, 2);
    }
    /**
     * Итоговая цена со скидкой
     */
    public function getFinalPriceAttribute(): float
    {
        return max(0, $this->calculated_price - $this->discount_amount);
    }

    /**
     * Процент скидки относительно суммы товаров (для отображения «бейджа»)
     */
    public function getDiscountPercentAttribute(): int
    {
        // ✅ Используем attributes вместо магического свойства (избегаем рекурсии)
        $rawDiscount = $this->attributes['discount_percent'] ?? 0;

        // Если у коллекции указан discount_percent — его и показываем
        if ($rawDiscount > 0) {
            return (int) $rawDiscount;
        }

        // Иначе считаем разницу между суммой товаров и итоговой ценой
        if ($this->pricing_type !== self::PRICING_FIXED) {
            return 0;
        }

        $sum = $this->products_sum;
        if ($sum <= 0 || $this->fixed_price >= $sum) {
            return 0;
        }

        return (int) round(($sum - $this->fixed_price) / $sum * 100);
    }

    /**
     * Количество товаров
     */
    public function getProductsCountAttribute(): int
    {
        return $this->getCollectionProducts()->count();
    }

    // ============================================================
    // Хелперы
    // ============================================================

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_CUSTOM => 'Набор по категориям',
            self::TYPE_ALL_PRODUCTS => 'Все товары workspace',
            default => 'Неизвестно',
        };
    }

    public function getRuleDescriptionAttribute(): string
    {
        if ($this->type === self::TYPE_ALL_PRODUCTS) {
            return 'Все активные товары workspace';
        }

        $parts = $this->collectionCategories
            ->map(fn(CollectionCategory $cc) =>
                "{$cc->category_name} ({$cc->rule_label}, " .
                $cc->products->count() . " тов.)"
            )
            ->implode(', ');

        return $parts ?: 'Категории не настроены';
    }
}
