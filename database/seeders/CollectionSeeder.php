<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Workspace;
use App\Models\Collection;
use App\Models\Product;

class CollectionSeeder extends Seeder
{
    public function run()
    {
        $ws = Workspace::first();
        $products = Product::all();

        $col = Collection::create([
            'workspace_id' => $ws->id,
            'name' => 'Популярное',
            'description' => 'Лучшие товары'
        ]);

        $i = 0;
        foreach ($products as $p) {
            $col->products()->attach($p->id, ['sort_order' => $i++]);
        }
    }
}
