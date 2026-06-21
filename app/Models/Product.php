<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'workspace_id',
        'name',
        'price',
        'old_price',
        'sku',
        'description',
        'images',
        'dimensions',
        'external_source',
        'config',
        'external_id',
        'is_active',
        'in_stop_list',
    ];

    protected $casts = [
        'images' => 'array',
        'config' => 'array',
        'dimensions' => 'array',
        'is_active' => 'boolean',
        'in_stop_list' => 'boolean',
    ];

    protected $with = ["categories"];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class,'product_categories');
    }

    public function attributes()
    {
        return $this->hasMany(ProductAttribute::class);
    }

    public function collections()
    {
        return $this->belongsToMany(Collection::class, 'collection_product')
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    public function ingredientGroups()
    {
        return $this->belongsToMany(IngredientGroup::class, 'product_ingredient_groups')
            ->withPivot(['is_required', 'selection_type'])
            ->withTimestamps();
    }

    public function ingredients()
    {
        return $this->belongsToMany(Ingredient::class, 'product_ingredients')
            ->withPivot(['default_selected', 'extra_price'])
            ->withTimestamps();
    }
}
