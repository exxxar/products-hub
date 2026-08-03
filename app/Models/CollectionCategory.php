<?php

// app/Models/CollectionCategory.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CollectionCategory extends Model
{
    protected $fillable = [
        'collection_id',
        'category_id',
        'category_name',
        'selection_rule',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public const RULE_ONE = 'one';
    public const RULE_MULTIPLE = 'multiple';
    public const RULE_ALL = 'all';

    public const RULES = [self::RULE_ONE, self::RULE_MULTIPLE, self::RULE_ALL];

    protected $with = ["products"];

    public function collection()
    {
        return $this->belongsTo(Collection::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Товары, выбранные в рамках этой категории коллекции
     */
    public function products()
    {
        return $this->belongsToMany(
            Product::class,
            'collection_category_product',
            'collection_category_id',
            'product_id'
        )
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('collection_category_product.sort_order');
    }

    /**
     * Человекочитаемое описание правила
     */
    public function getRuleLabelAttribute(): string
    {
        return match ($this->selection_rule) {
            self::RULE_ONE => 'Выбор 1 позиции',
            self::RULE_MULTIPLE => 'Выбор нескольких',
            self::RULE_ALL => 'Все товары категории',
            default => 'Неизвестно',
        };
    }

    /**
     * Подытог по категории
     */
    public function getSubtotalAttribute(): float
    {
        return (float) $this->products->sum('price');
    }
}
