<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{

    protected function decodeJsonFields(Request $request)
    {
        $jsonFields = ['attributes', 'ingredients', 'variants', 'config'];

        foreach ($jsonFields as $field) {
            $value = $request->input($field);
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $request->merge([$field => $decoded]);
                }
            }
        }
    }

    public function store(Request $request)
    {
        $workspace = App::make('workspace');

        $this->decodeJsonFields($request);

        // Логирование для отладки
        \Log::info('Product store request', [
            'categories' => $request->input('categories'),
            'all_input' => $request->all()
        ]);

        // Валидация
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100',
            'price' => 'required|numeric|min:0',
            'old_price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'categories' => 'nullable|array',
            'categories.*' => 'integer',
            'dimensions' => 'nullable|array',
            'dimensions.width' => 'nullable|numeric|min:0',
            'dimensions.height' => 'nullable|numeric|min:0',
            'dimensions.length' => 'nullable|numeric|min:0',
            'dimensions.weight' => 'nullable|numeric|min:0',
            'attributes' => 'nullable|array',
            'attributes.*.name' => 'required|string|max:255',
            'attributes.*.value' => 'required|string|max:255',
            'ingredients' => 'nullable|array',
            'ingredients.*' => 'integer',
            'images' => 'nullable|array',
            'images.*' => 'file|image|max:5120',
            'images_existing' => 'nullable|array',
            'images_existing.*' => 'string',
        ]);

        DB::beginTransaction();

        try {
            // === Обработка изображений ===
            $images = [];

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store("products/{$workspace->id}", 'public');
                    $images[] = [
                        'url' => Storage::url($path),
                        'name' => $image->getClientOriginalName(),
                        'size' => $image->getSize(),
                    ];
                }
            }

            if ($request->has('images_existing')) {
                foreach ($request->input('images_existing') as $url) {
                    $images[] = [
                        'url' => $url,
                        'name' => basename($url),
                        'size' => null,
                    ];
                }
            }

            // === Создание продукта ===
            $product = Product::create([
                'workspace_id' => $workspace->id,
                'name' => $validated['name'],
                'sku' => $validated['sku'] ?? null,
                'price' => $validated['price'],
                'old_price' => $validated['old_price'] ?? null,
                'description' => $validated['description'] ?? null,
                'dimensions' => $validated['dimensions'] ?? null,
                'images' => $images,
                'is_active' => true,
                'in_stop_list' => false,
            ]);

            // === Синхронизация категорий ===
            if (!empty($validated['categories'])) {
                // Проверяем что категории принадлежат workspace
                $validCategoryIds = $workspace->categories()
                    ->whereIn('id', $validated['categories'])
                    ->pluck('id')
                    ->toArray();

                \Log::info('Syncing categories', [
                    'requested' => $validated['categories'],
                    'valid' => $validCategoryIds
                ]);

                if (!empty($validCategoryIds)) {
                    $product->categories()->sync($validCategoryIds);
                }
            }

            // === Создание атрибутов ===
            if (!empty($validated['attributes'])) {
                foreach ($validated['attributes'] as $attr) {
                    $product->attributes()->create([
                        'name' => $attr['name'],
                        'value' => $attr['value'],
                    ]);
                }
            }

            // === Синхронизация ингредиентов ===
            if (!empty($validated['ingredients'])) {
                $product->ingredients()->sync(
                    collect($validated['ingredients'])->mapWithKeys(function ($id) {
                        return [$id => ['default_selected' => false, 'extra_price' => 0]];
                    })->toArray()
                );
            }

            DB::commit();

            $product->load(['categories', 'attributes', 'ingredients']);

            // === Обновляем products_count у категорий ===
            if (!empty($validated['categories'])) {
                $workspace->categories()
                    ->whereIn('id', $validated['categories'])
                    ->get()
                    ->each(function ($category) {
                        $category->loadCount('products');
                    });
            }

            // === Триггер вебхуков ===
            $this->triggerWebhooks($workspace, $product);

            return response()->json($product, 201);

        } catch (\Exception $e) {
            DB::rollBack();

            if (!empty($images)) {
                foreach ($images as $img) {
                    if (isset($img['url']) && str_contains($img['url'], '/storage/')) {
                        $path = str_replace('/storage/', '', $img['url']);
                        Storage::disk('public')->delete($path);
                    }
                }
            }

            Log::error('Product creation failed: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ошибка при создании товара',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $workspaceUuid, $productId)
    {
        $workspace = App::make('workspace');

        $product = $workspace->products()->findOrFail($productId);

        $this->decodeJsonFields($request);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'sku' => 'nullable|string|max:100',
            'price' => 'sometimes|numeric|min:0',
            'old_price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'categories' => 'nullable|array',
            'categories.*' => 'integer',
            'dimensions' => 'nullable|array',
            'dimensions.width' => 'nullable|numeric|min:0',
            'dimensions.height' => 'nullable|numeric|min:0',
            'dimensions.length' => 'nullable|numeric|min:0',
            'dimensions.weight' => 'nullable|numeric|min:0',
            'attributes' => 'nullable|array',
            'attributes.*.name' => 'required|string|max:255',
            'attributes.*.value' => 'required|string|max:255',
            'ingredients' => 'nullable|array',
            'ingredients.*' => 'integer',
            'images' => 'nullable|array',
            'images.*' => 'file|image|max:5120',
            'images_existing' => 'nullable|array',
            'images_existing.*' => 'string',
        ]);

        DB::beginTransaction();

        try {
            // Сохраняем старые категории для обновления счётчиков
            $oldCategoryIds = $product->categories()->pluck('categories.id')->toArray();

            // Обновляем основные поля
            $product->update([
                'name' => $validated['name'] ?? $product->name,
                'sku' => $validated['sku'] ?? $product->sku,
                'price' => $validated['price'] ?? $product->price,
                'old_price' => $validated['old_price'] ?? $product->old_price,
                'description' => $validated['description'] ?? $product->description,
                'dimensions' => $validated['dimensions'] ?? $product->dimensions,
            ]);

            // === Обработка изображений ===
            if ($request->hasFile('images') || $request->has('images_existing')) {
                $images = [];

                if ($request->hasFile('images')) {
                    foreach ($request->file('images') as $image) {
                        $path = $image->store("products/{$workspace->id}", 'public');
                        $images[] = [
                            'url' => Storage::url($path),
                            'name' => $image->getClientOriginalName(),
                            'size' => $image->getSize(),
                        ];
                    }
                }

                if ($request->has('images_existing')) {
                    foreach ($request->input('images_existing') as $url) {
                        $images[] = [
                            'url' => $url,
                            'name' => basename($url),
                            'size' => null,
                        ];
                    }
                }

                $product->update(['images' => $images]);
            }

            // === Синхронизация категорий ===
            if (array_key_exists('categories', $validated)) {
                $validCategoryIds = [];
                if (!empty($validated['categories'])) {
                    $validCategoryIds = $workspace->categories()
                        ->whereIn('id', $validated['categories'])
                        ->pluck('id')
                        ->toArray();
                }

                $product->categories()->sync($validCategoryIds);
            }

            // === Обновление атрибутов ===
            if (array_key_exists('attributes', $validated)) {
                $product->attributes()->delete();
                if (!empty($validated['attributes'])) {
                    foreach ($validated['attributes'] as $attr) {
                        $product->attributes()->create([
                            'name' => $attr['name'],
                            'value' => $attr['value'],
                        ]);
                    }
                }
            }

            // === Синхронизация ингредиентов ===
            if (array_key_exists('ingredients', $validated)) {
                if (!empty($validated['ingredients'])) {
                    $product->ingredients()->sync(
                        collect($validated['ingredients'])->mapWithKeys(function ($id) {
                            return [$id => ['default_selected' => false, 'extra_price' => 0]];
                        })->toArray()
                    );
                } else {
                    $product->ingredients()->detach();
                }
            }

            DB::commit();

            $product->load(['categories', 'attributes', 'ingredients']);

            // === Обновляем products_count у затронутых категорий ===
            $allCategoryIds = array_unique(array_merge($oldCategoryIds, $validCategoryIds ?? []));
            if (!empty($allCategoryIds)) {
                $workspace->categories()
                    ->whereIn('id', $allCategoryIds)
                    ->get()
                    ->each(function ($category) {
                        $category->loadCount('products');
                    });
            }

            // === Триггер вебхуков ===
            $this->triggerWebhooks($workspace, $product);

            return response()->json($product);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Product update failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Ошибка при обновлении товара',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    protected function triggerWebhooks($workspace, $product)
    {
        $webhooks = $workspace->webhooks()
            ->where('sync_on_update', true)
            ->get();

        foreach ($webhooks as $webhook) {
            dispatch(function() use ($webhook, $product) {
                $webhook->sync($product);
            })->afterResponse();
        }
    }

    public function destroy($workspaceUuid, $productId)
    {
        $workspace = App::make('workspace');

        $product = $workspace->products()->findOrFail($productId);

        // Удаляем изображения из storage
        if (!empty($product->images)) {
            foreach ($product->images as $image) {
                if (isset($image['url']) && str_contains($image['url'], '/storage/')) {
                    $path = str_replace('/storage/', '', $image['url']);
                    Storage::disk('public')->delete($path);
                }
            }
        }

        // Удаляем связи
        $product->categories()->detach();
        $product->attributes()->delete();
        $product->ingredients()->detach();
        $product->collections()->detach();

        // Мягкое удаление (soft delete)
        $product->delete();

        return response()->json(['message' => 'Product deleted']);
    }

    // В ProductController добавить:
    public function destroyMultiple(Request $request)
    {
        $workspace = App::make('workspace');

        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $ids = $validated['ids'];

        // Получаем товары
        $products = $workspace->products()
            ->whereIn('id', $ids)
            ->get();

        if ($products->isEmpty()) {
            return response()->json(['message' => 'No products found'], 404);
        }

        DB::beginTransaction();

        try {
            foreach ($products as $product) {
                // Удаляем изображения
                if (!empty($product->images)) {
                    foreach ($product->images as $image) {
                        if (isset($image['url']) && str_contains($image['url'], '/storage/')) {
                            $path = str_replace('/storage/', '', $image['url']);
                            Storage::disk('public')->delete($path);
                        }
                    }
                }

                // Удаляем связи
                $product->categories()->detach();
                $product->attributes()->delete();
                $product->ingredients()->detach();
                $product->collections()->detach();

                // Мягкое удаление
                $product->delete();
            }

            DB::commit();

            return response()->json([
                'message' => 'Products deleted',
                'count' => $products->count()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk delete failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Delete failed'], 500);
        }
    }

    // В ProductController.php добавить:

    /**
     * Добавить товары в стоп-лист
     */
    public function addToStopList(Request $request)
    {
        $workspace = App::make('workspace');

        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $ids = $validated['ids'];

        $count = $workspace->products()
            ->whereIn('id', $ids)
            ->update(['in_stop_list' => true]);

        ActivityLogger::bulk('added_to_stop_list', 'product', $ids);

        return response()->json([
            'message' => 'Товары добавлены в стоп-лист',
            'count' => $count,
        ]);
    }

    /**
     * Убрать товары из стоп-листа
     */
    public function removeFromStopList(Request $request)
    {
        $workspace = App::make('workspace');

        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $ids = $validated['ids'];

        $count = $workspace->products()
            ->whereIn('id', $ids)
            ->update(['in_stop_list' => false]);

        ActivityLogger::bulk('removed_from_stop_list', 'product', $ids);

        return response()->json([
            'message' => 'Товары убраны из стоп-листа',
            'count' => $count,
        ]);
    }
}
