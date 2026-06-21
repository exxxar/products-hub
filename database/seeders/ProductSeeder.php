<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Workspace;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductAttribute;

class ProductSeeder extends Seeder
{
    public function run()
    {
        $ws = Workspace::first();
        $cats = Category::all()->keyBy('name');

        // Пример товара
        $pizza = Product::create([
            'workspace_id' => $ws->id,
            'name' => 'Пицца Маргарита',
            'description' => 'Классическая пицца с сыром и томатами',
            'images' => null,
            'price' => 1000,
            'old_price' => 1200,
            'is_active' => true
        ]);



        // Характеристики
        ProductAttribute::insert([
            [
                'product_id' => $pizza->id,
                'name' => 'Вес',
                'value' => '450 г'
            ],
            [
                'product_id' => $pizza->id,
                'name' => 'Острота',
                'value' => '1'
            ]
        ]);

        // Второй товар
        Product::create([
            'workspace_id' => $ws->id,
            'name' => 'Кола 0.5',
            'description' => 'Газированный напиток',
            'images' => null,
            'price' => 700,
            'old_price' => 0,
            'is_active' => true
        ]);
    }
}
