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
        'is_composite',
        'in_stop_list',
    ];

    protected $casts = [
        'images' => 'array',
        'config' => 'array',
        'dimensions' => 'array',
        'is_active' => 'boolean',
        'is_composite' => 'boolean',
        'in_stop_list' => 'boolean',
    ];

    protected $with = ["categories","components"];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Товары, которые входят в состав этого товара (дочерние)
     */
    public function components()
    {
        return $this->belongsToMany(Product::class, 'product_components', 'composite_product_id', 'component_product_id')
            ->withPivot(['quantity', 'sort_order'])
            ->withTimestamps();
    }

    /**
     * Товары, в состав которых входит этот товар (родительские)
     */
    public function compositeProducts()
    {
        return $this->belongsToMany(Product::class, 'product_components', 'component_product_id', 'composite_product_id')
            ->withPivot(['quantity', 'sort_order'])
            ->withTimestamps();
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

    // 🔥 Группы ингредиентов этого товара
    public function ingredientGroups()
    {
        return $this->hasMany(IngredientGroup::class)->orderBy('sort_order');
    }


}
