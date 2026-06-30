<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;

class CollectionController extends Controller
{
    public function index()
    {
        $workspace = App::make('workspace');

        $collections = $workspace->collections()
            ->with('products')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'description' => $c->description,
                'short_description' => $c->short_description,
                'type' => $c->type,
                'type_label' => $c->type_label,
                'rule_config' => $c->rule_config,
                'rule_description' => $c->rule_description,
                'pricing_type' => $c->pricing_type,
                'price' => $c->calculated_price,
                'old_price' => $c->calculated_old_price,
                'discount_percent' => $c->discount_percent,
                'products_count' => $c->products_count,
                'images' => $c->images,
                'is_active' => $c->is_active,
                'in_stop_list' => $c->in_stop_list,
                'created_at' => $c->created_at->toIso8601String(),
            ]);

        return response()->json($collections);
    }

    public function store(Request $request)
    {
        $workspace = App::make('workspace');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'type' => 'required|in:manual,category_all,categories_all,workspace_all,category_select',
            'rule_config' => 'nullable|array',
            'pricing_type' => 'required|in:sum,fixed',
            'fixed_price' => 'nullable|numeric|min:0',
            'fixed_old_price' => 'nullable|numeric|min:0',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'integer',
        ]);

        $collection = Collection::create([
            'workspace_id' => $workspace->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'short_description' => $validated['short_description'] ?? null,
            'type' => $validated['type'],
            'rule_config' => $validated['rule_config'] ?? null,
            'pricing_type' => $validated['pricing_type'],
            'fixed_price' => $validated['fixed_price'] ?? null,
            'fixed_old_price' => $validated['fixed_old_price'] ?? null,
            'is_active' => true,
        ]);

        // Синхронизация товаров для manual и category_select
        if (in_array($validated['type'], ['manual', 'category_select']) && isset($validated['product_ids'])) {
            $collection->products()->sync($validated['product_ids']);
        }

        return response()->json($collection->load('products'), 201);
    }

    public function update(Request $request, Collection $collection)
    {
        $workspace = App::make('workspace');

        if ($collection->workspace_id !== $workspace->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'type' => 'sometimes|in:manual,category_all,categories_all,workspace_all,category_select',
            'rule_config' => 'nullable|array',
            'pricing_type' => 'sometimes|in:sum,fixed',
            'fixed_price' => 'nullable|numeric|min:0',
            'fixed_old_price' => 'nullable|numeric|min:0',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'integer',
        ]);

        $collection->update($validated);

        if (isset($validated['product_ids'])) {
            $collection->products()->sync($validated['product_ids']);
        }

        return response()->json($collection->fresh()->load('products'));
    }

    public function destroy(Collection $collection)
    {
        $workspace = App::make('workspace');

        if ($collection->workspace_id !== $workspace->id) {
            abort(403);
        }

        $collection->products()->detach();
        $collection->delete();

        return response()->json(['message' => 'Коллекция удалена']);
    }

    /**
     * Загрузка картинки коллекции
     */
    public function uploadImage(Request $request, Collection $collection)
    {
        $workspace = App::make('workspace');

        if ($collection->workspace_id !== $workspace->id) {
            abort(403);
        }

        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $path = $request->file('image')->store('collection-images/' . $workspace->id, 'public');

        $images = $collection->images ?? [];
        $images[] = [
            'url' => Storage::url($path),
            'path' => $path,
        ];

        $collection->update(['images' => $images]);

        return response()->json([
            'images' => $collection->images,
        ]);
    }

    /**
     * Получить товары коллекции (для предпросмотра)
     */
    public function previewProducts(Collection $collection)
    {
        $workspace = App::make('workspace');

        if ($collection->workspace_id !== $workspace->id) {
            abort(403);
        }

        $products = $collection->getCollectionProducts();

        return response()->json([
            'products' => $products,
            'count' => $products->count(),
            'total_price' => $products->sum('price'),
        ]);
    }

    public function show($workspaceUuid, Collection $collection)
    {
        $workspace = App::make('workspace');

        if ($collection->workspace_id !== $workspace->id) {
            abort(403);
        }

        $collection->load(['products.categories', 'products.attributes']);

        // Получаем товары коллекции (по правилам или вручную)
        $products = $collection->getCollectionProducts();

        // Форматируем товары
        $productsData = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => (float) $product->price,
                'old_price' => $product->old_price ? (float) $product->old_price : null,
                'description' => $product->description,
                'images' => $product->images,
                'is_active' => $product->is_active,
                'in_stop_list' => $product->in_stop_list,
                'categories' => $product->categories->map(fn($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                ])->values()->all(),
            ];
        });

        return response()->json([
            'collection' => [
                'id' => $collection->id,
                'name' => $collection->name,
                'description' => $collection->description,
                'type' => $collection->type,
                'type_label' => $collection->type_label,
                'pricing_type' => $collection->pricing_type,
                'price' => $collection->calculated_price,
                'old_price' => $collection->calculated_old_price,
                'discount_percent' => $collection->discount_percent,
                'products_count' => $products->count(),
                'images' => $collection->images,
            ],
            'products' => $productsData->values()->all(),
            'total_price' => $productsData->sum('price'),
        ]);
    }
}
