<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Workspace;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $ws = Workspace::first();

        $categories = [
            'Пицца',
            'Суши',
            'Напитки',
            'Десерты'
        ];

        foreach ($categories as $i => $name) {
            Category::create([
                'workspace_id' => $ws->id,
                'name' => $name,
                'sort_order' => $i
            ]);
        }
    }
}
