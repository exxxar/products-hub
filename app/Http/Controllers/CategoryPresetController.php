<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class CategoryPresetController extends Controller
{
    /**
     * Получить список всех пресетов
     */
    public function index()
    {
        $presets = config('category_presets');

        $formatted = collect($presets)->map(function ($preset, $key) {
            return [
                'key' => $key,
                'name' => $preset['name'],
                'description' => $preset['description'],
                'icon' => $preset['icon'],
                'color' => $preset['color'],
                'categories_count' => count($preset['categories']),
            ];
        })->values();

        return response()->json($formatted);
    }

    /**
     * Получить детали конкретного пресета
     */
    public function show($workspaceUuid, $presetKey)
    {
        $preset = config("category_presets.{$presetKey}");

        if (!$preset) {
            return response()->json(['error' => 'Preset not found'], 404);
        }

        return response()->json([
            'key' => $presetKey,
            'name' => $preset['name'],
            'description' => $preset['description'],
            'icon' => $preset['icon'],
            'color' => $preset['color'],
            'categories' => $preset['categories'],
        ]);
    }

    /**
     * Применить пресет к workspace
     */
    public function apply(Request $request, $workspaceUuid, $presetKey)
    {
        $workspace = App::make('workspace');

        $preset = config("category_presets.{$presetKey}");

        if (!$preset) {
            return response()->json(['error' => 'Preset not found'], 404);
        }

        $request->validate([
            'replace_existing' => 'boolean',
        ]);

        $replaceExisting = $request->input('replace_existing', false);

        // Если replace_existing = true - удаляем существующие категории
        if ($replaceExisting) {
            // Проверяем есть ли товары в существующих категориях
            $hasProducts = $workspace->categories()
                ->whereHas('products')
                ->exists();

            if ($hasProducts) {
                return response()->json([
                    'error' => 'Нельзя заменить категории, так как в них есть товары. Сначала удалите товары или снимите галочку "Заменить существующие".'
                ], 422);
            }

            $workspace->categories()->delete();
        }

        $createdCategories = [];

        foreach ($preset['categories'] as $categoryName) {
            // Проверяем существует ли уже такая категория
            $exists = $workspace->categories()
                ->where('name', $categoryName)
                ->exists();

            if (!$exists) {
                $category = $workspace->categories()->create([
                    'name' => $categoryName,
                ]);

                $createdCategories[] = $category;
            }
        }

        return response()->json([
            'message' => 'Пресет применён',
            'created_count' => count($createdCategories),
            'categories' => $createdCategories,
        ], 201);
    }
}
