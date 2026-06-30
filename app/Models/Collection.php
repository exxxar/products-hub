<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Collection extends Model
{
    protected $fillable = [
        'workspace_id',
        'name',
        'description',
        'short_description',
        'type',
        'rule_config',
        'pricing_type',
        'fixed_price',
        'fixed_old_price',
        'images',
        'is_active',
        'in_stop_list',
    ];

    protected $casts = [
        'rule_config' => 'array',
        'images' => 'array',
        'fixed_price' => 'float',
        'fixed_old_price' => 'float',
        'is_active' => 'boolean',
        'in_stop_list' => 'boolean',
    ];

    protected $appends = ["calculated_price",
        "calculated_old_price",
        "products_count","discount_percent","type_label","rule_description"];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'collection_product')
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    // === Логика формирования состава ===

    /**
     * Получить товары коллекции (динамически или из БД)
     */
    public function getCollectionProducts()
    {
        return match ($this->type) {
            'manual' => $this->products,
            'category_all' => $this->getCategoryAllProducts(),
            'categories_all' => $this->getCategoriesAllProducts(),
            'workspace_all' => $this->getWorkspaceAllProducts(),
            'category_select' => $this->getCategorySelectProducts(),
            default => $this->products,
        };
    }

    protected function getCategoryAllProducts()
    {
        $categoryId = $this->rule_config['category_id'] ?? null;
        if (!$categoryId) return collect();

        return $this->workspace->products()
            ->whereHas('categories', fn($q) => $q->where('categories.id', $categoryId))
            ->where('is_active', true)
            ->where('in_stop_list', false)
            ->get();
    }

    protected function getCategoriesAllProducts()
    {
        $categoryIds = $this->rule_config['category_ids'] ?? [];
        if (empty($categoryIds)) return collect();

        return $this->workspace->products()
            ->whereHas('categories', fn($q) => $q->whereIn('categories.id', $categoryIds))
            ->where('is_active', true)
            ->where('in_stop_list', false)
            ->get();
    }

    protected function getWorkspaceAllProducts()
    {
        return $this->workspace->products()
            ->where('is_active', true)
            ->where('in_stop_list', false)
            ->get();
    }

    protected function getCategorySelectProducts()
    {
        // Для этого типа используются товары из products() (ручной выбор)
        return $this->products;
    }

    // === Ценообразование ===

    /**
     * Получить цену коллекции
     */
    public function getCalculatedPriceAttribute(): float
    {
        if ($this->pricing_type === 'fixed' && $this->fixed_price !== null) {
            return (float) $this->fixed_price;
        }

        // Сумма цен товаров
        $products = $this->getCollectionProducts();
        return (float) $products->sum('price');
    }

    /**
     * Получить старую цену коллекции
     */
    public function getCalculatedOldPriceAttribute(): ?float
    {
        if ($this->pricing_type === 'fixed' && $this->fixed_old_price !== null) {
            return (float) $this->fixed_old_price;
        }

        // Сумма старых цен товаров
        $products = $this->getCollectionProducts();
        $oldPriceSum = $products->sum(fn($p) => $p->old_price ?? $p->price);

        return $oldPriceSum > 0 ? (float) $oldPriceSum : null;
    }

    /**
     * Количество товаров в коллекции
     */
    public function getProductsCountAttribute(): int
    {
        return $this->getCollectionProducts()->count();
    }

    /**
     * Размер скидки в процентах
     */
    public function getDiscountPercentAttribute(): int
    {
        if (!$this->calculated_old_price || !$this->calculated_price) {
            return 0;
        }

        if ($this->calculated_old_price <= $this->calculated_price) {
            return 0;
        }

        return (int) round((($this->calculated_old_price - $this->calculated_price) / $this->calculated_old_price) * 100);
    }

    // === Хелперы для типов ===

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'manual' => 'Ручной выбор',
            'category_all' => 'Все товары категории',
            'categories_all' => 'Все товары нескольких категорий',
            'workspace_all' => 'Все товары',
            'category_select' => 'Выбор из категории',
            default => 'Неизвестно',
        };
    }

    public function getRuleDescriptionAttribute(): string
    {
        return match ($this->type) {
            'manual' => 'Товары выбраны вручную',
            'category_all' => $this->getCategoryName($this->rule_config['category_id'] ?? null),
            'categories_all' => $this->getCategoryNames($this->rule_config['category_ids'] ?? []),
            'workspace_all' => 'Все товары workspace',
            'category_select' => "{$this->rule_config['count']} товаров из {$this->getCategoryName($this->rule_config['category_id'] ?? null)}",
            default => '',
        };
    }

    protected function getCategoryName(?int $categoryId): string
    {
        if (!$categoryId) return 'Категория не выбрана';
        $category = $this->workspace->categories()->find($categoryId);
        return $category?->name ?? 'Категория не найдена';
    }

    protected function getCategoryNames(array $categoryIds): string
    {
        if (empty($categoryIds)) return 'Категории не выбраны';
        $categories = $this->workspace->categories()->whereIn('id', $categoryIds)->get();
        return $categories->pluck('name')->implode(', ');
    }
}
