<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Workspace;
use App\Models\IngredientGroup;
use App\Models\Ingredient;

class IngredientSeeder extends Seeder
{
    public function run()
    {
        $ws = Workspace::first();

        // Группа "Сыры"
        $cheeseGroup = IngredientGroup::create([
            'workspace_id' => $ws->id,
            'name' => 'Сыры',
            'selection_type' => 'multiple',
            'min' => 0,
            'max' => 3
        ]);

        Ingredient::insert([
            [
                'workspace_id' => $ws->id,
                'group_id' => $cheeseGroup->id,
                'name' => 'Моцарелла',
                'image_url' => null
            ],
            [
                'workspace_id' => $ws->id,
                'group_id' => $cheeseGroup->id,
                'name' => 'Пармезан',
                'image_url' => null
            ]
        ]);

        // Группа "Добавки"
        $extraGroup = IngredientGroup::create([
            'workspace_id' => $ws->id,
            'name' => 'Добавки',
            'selection_type' => 'optional',
            'min' => 0,
            'max' => 5
        ]);

        Ingredient::insert([
            [
                'workspace_id' => $ws->id,
                'group_id' => $extraGroup->id,
                'name' => 'Оливки',
                'image_url' => null
            ],
            [
                'workspace_id' => $ws->id,
                'group_id' => $extraGroup->id,
                'name' => 'Грибы',
                'image_url' => null
            ]
        ]);
    }
}
