<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ingredient extends Model
{
    protected $fillable = [
        'group_id',
        'name',
        'extra_price',
        'is_default',
        'sort_order',
    ];

    protected $casts = [
        'extra_price' => 'decimal:2',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(IngredientGroup::class, 'group_id');
    }
}
