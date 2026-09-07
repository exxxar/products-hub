<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IngredientGroup extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'selection_rule',
        'min_select',
        'max_select',
        'is_required',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'min_select' => 'integer',
        'max_select' => 'integer',
        'is_required' => 'boolean',
    ];

    // Человекочитаемые метки для каждого правила
    public const SELECTION_RULES = [
        'single'   => 'Один из группы',
        'multiple' => 'Несколько из группы',
        'all'      => 'Все обязательны',
        'optional' => 'Необязательно',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(Ingredient::class, 'group_id')->orderBy('sort_order');
    }

    /**
     * Метка правила для API
     */
    public function getSelectionRuleLabelAttribute(): string
    {
        return self::SELECTION_RULES[$this->selection_rule] ?? 'Неизвестно';
    }
}
