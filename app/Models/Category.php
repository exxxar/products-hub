<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['workspace_id', 'parent_id','name', 'sort_order'];

    protected $appends = ['products_count'];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    // === ПРАВИЛЬНОЕ ОТНОШЕНИЕ ===
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_categories')
            ->withTimestamps();
    }


    public function getProductsCountAttribute(): int
    {
        if (array_key_exists('products_count', $this->attributes)) {
            return (int) $this->attributes['products_count'];
        }

        return $this->products()->count();
    }

    /**
     * ✅ Глобальный скоуп — всегда подгружает products_count
     */
/*    protected static function booted()
    {
        static::addGlobalScope('withProductsCount', function (Builder $builder) {
            $builder->withCount('products');
        });
    }*/
}
