<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class CollectionController extends Controller
{
    public function index()
    {
        $workspace = App::make('workspace');

        $collections = $workspace->collections()
            ->withCount('products')
            ->with(['products' => function($query) {
                $query->with(['categories', 'attributes', 'ingredients'])
                    ->orderBy('collection_product.sort_order');
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($collections);
    }

    public function store(Request $request)
    {
        $workspace = App::make('workspace');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'integer|exists:products,id',
        ]);

        $collection = $workspace->collections()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        // Синхронизация товаров если переданы
        if (!empty($validated['product_ids'])) {
            $collection->products()->sync(
                collect($validated['product_ids'])->mapWithKeys(function ($id, $index) {
                    return [$id => ['sort_order' => $index]];
                })->toArray()
            );
        }

        $collection->load('products');

        return response()->json($collection, 201);
    }


    public function destroy($workspaceUuid, $collectionId)
    {
        $workspace = App::make('workspace');


        $collection = $workspace->collections()->findOrFail($collectionId);

        $collection->products()->detach();
        $collection->delete();

        return response()->json(['message' => 'Collection deleted']);
    }

    public function show($workspaceUuid, $collectionId)
    {
        $workspace = App::make('workspace');

        $collection = $workspace->collections()->findOrFail($collectionId);
        $collection->load('products');

        return response()->json($collection);
    }

    public function update(Request $request, $collectionId)
    {
        $workspace = App::make('workspace');

        $collection = $workspace->collections()->findOrFail($collectionId);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
        ]);

        $collection->update($validated);

        return response()->json($collection);
    }

    public function addProducts(Request $request, $collectionId)
    {
        $workspace = App::make('workspace');

        $collection = $workspace->collections()->findOrFail($collectionId);

        $validated = $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'integer|exists:products,id',
        ]);

        $maxOrder = $collection->products()->max('sort_order') ?? 0;

        $syncData = [];
        foreach ($validated['product_ids'] as $index => $productId) {
            $syncData[$productId] = ['sort_order' => $maxOrder + $index + 1];
        }

        $collection->products()->syncWithoutDetaching($syncData);
        $collection->load('products');

        return response()->json($collection);
    }

    public function removeProducts(Request $request, $collectionId)
    {
        $workspace = App::make('workspace');

        $collection = $workspace->collections()->findOrFail($collectionId);

        $validated = $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'integer|exists:products,id',
        ]);

        $collection->products()->detach($validated['product_ids']);
        $collection->load('products');

        return response()->json($collection);
    }

    public function reorderProducts(Request $request, $collectionId)
    {
        $workspace = App::make('workspace');

        $collection = $workspace->collections()->findOrFail($collectionId);

        $validated = $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'integer|exists:products,id',
        ]);

        foreach ($validated['product_ids'] as $index => $productId) {
            $collection->products()->updateExistingPivot($productId, [
                'sort_order' => $index
            ]);
        }

        $collection->load('products');

        return response()->json($collection);
    }
}
