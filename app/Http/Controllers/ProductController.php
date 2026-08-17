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

    public function index(Request $request)
    {
        $workspace = App::make('workspace');

        $query = $workspace->products()
            ->with(['categories', 'attributes', 'ingredientGroups.ingredients', 'components']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->input('in_stop_list')) {
            $query->where('in_stop_list', true);
        }

        if ($request->input('is_active')) {
            $query->where('is_active', true)->where('in_stop_list', false);
        }

        $total = $query->count();
        $limit = min((int) $request->input('limit', 50), 200);
        $offset = (int) $request->input('offset', 0);

        $products = $query
            ->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get();

        return response()->json([
            'products' => $products,
            'total' => $total,
            'loaded' => $offset + $products->count(),
            'has_more' => ($offset + $products->count()) < $total,
        ]);
    }

    protected function decodeJsonFields(Request $request)
    {
        // 🔥 Добавляем ingredient_groups и components
        $jsonFields = ['attributes', 'ingredients', 'variants', 'config', 'ingredient_groups', 'components'];

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

    private function processImages(Request $request): array
    {
        $workspace = App::make('workspace');
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

        return $images;
    }

    private function cleanupImages(array $images): void
    {
        foreach ($images as $img) {
            if (isset($img['url']) && str_contains($img['url'], '/storage/')) {
                $path = str_replace('/storage/', '', $img['url']);
                Storage::disk('public')->delete($path);
            }
        }
    }

    private function syncCategories(Product $product, $workspace, array $categoryIds): array
    {
        if (empty($categoryIds)) {
            $product->categories()->detach();
            return [];
        }

        $validCategoryIds = $workspace->categories()
            ->whereIn('id', $categoryIds)
            ->pluck('id')
            ->toArray();

        $product->categories()->sync($validCategoryIds);
        return $validCategoryIds;
    }

    private function syncAttributes(Product $product, array $attributes): void
    {
        $product->attributes()->delete();
        foreach ($attributes as $attr) {
            $product->attributes()->create([
                'name' => $attr['name'],
                'value' => $attr['value'],
            ]);
        }
    }

    private function syncIngredientGroups(Product $product, array $groups): void
    {
        // Удаляем старые группы (каскадно удалятся и ингредиенты)
        $product->ingredientGroups()->delete();

        foreach ($groups as $groupIndex => $groupData) {
            $group = $product->ingredientGroups()->create([
                'name' => $groupData['name'],
                'sort_order' => $groupData['sort_order'] ?? $groupIndex,
            ]);

            if (!empty($groupData['ingredients'])) {
                foreach ($groupData['ingredients'] as $ingIndex => $ingData) {
                    $group->ingredients()->create([
                        'name' => $ingData['name'],
                        'extra_price' => $ingData['extra_price'] ?? 0,
                        'is_default' => $ingData['is_default'] ?? false,
                        'sort_order' => $ingData['sort_order'] ?? $ingIndex,
                    ]);
                }
            }
        }
    }

    private function syncComponents(Product $product, Request $request): void
    {
        $componentsInput = $request->input('components');
        if (!empty($componentsInput)) {
            $components = is_string($componentsInput) ? json_decode($componentsInput, true) : $componentsInput;
            $syncData = [];

            if (is_array($components)) {
                foreach ($components as $comp) {
                    if (isset($comp['id'])) {
                        $syncData[$comp['id']] = [
                            'quantity' => $comp['quantity'] ?? 1,
                            'is_default' => $comp['is_default'] ?? false,
                        ];
                    }
                }
            }
            $product->components()->sync($syncData);
        } else {
            $product->components()->detach();
        }
    }

    public function store(Request $request)
    {
        $workspace = App::make('workspace');
        $this->decodeJsonFields($request);

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
            'images' => 'nullable|array',
            'images.*' => 'file|image|max:5120',
            'images_existing' => 'nullable|array',
            'images_existing.*' => 'string',
            // 🆕 Добавляем валидацию для флага
            'is_composite' => 'nullable|boolean',
        ]);

        DB::beginTransaction();

        try {
            $images = $this->processImages($request);

            // 🆕 Проверяем, пришли ли компоненты в запросе
            $componentsInput = $request->input('components');
            $components = is_string($componentsInput)
                ? json_decode($componentsInput, true)
                : $componentsInput;

            // 🆕 Автоматически определяем is_composite
            $hasComponents = is_array($components) && !empty($components);
            $isComposite = $validated['is_composite'] ?? $hasComponents;

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
                'is_composite' => (bool)$isComposite, // 🆕 Устанавливаем флаг
            ]);

            $this->syncCategories($product, $workspace, $validated['categories'] ?? []);
            $this->syncAttributes($product, $validated['attributes'] ?? []);
            $this->syncIngredientGroups($product, $request->input('ingredient_groups', []));
            $this->syncComponents($product, $request);

            // 🆕 Двойная проверка: если после syncComponents появились компоненты,
            // но флаг был false — обновляем его
            if (!$isComposite && $product->components()->exists()) {
                $product->update(['is_composite' => true]);
            }

            DB::commit();

            $product->load(['categories', 'attributes', 'ingredientGroups.ingredients', 'components']);

            return response()->json($product, 201);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->cleanupImages($images ?? []);
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
            'images' => 'nullable|array',
            'images.*' => 'file|image|max:5120',
            'images_existing' => 'nullable|array',
            'images_existing.*' => 'string',
            // 🆕 Добавляем валидацию
            'is_composite' => 'nullable|boolean',
        ]);

        DB::beginTransaction();

        try {
            $oldCategoryIds = $product->categories()->pluck('categories.id')->toArray();

            $updateData = [
                'name' => $validated['name'] ?? $product->name,
                'sku' => $validated['sku'] ?? $product->sku,
                'price' => $validated['price'] ?? $product->price,
                'old_price' => $validated['old_price'] ?? $product->old_price,
                'description' => $validated['description'] ?? $product->description,
                'dimensions' => $validated['dimensions'] ?? $product->dimensions,
            ];

            // 🆕 Если флаг явно передан — используем его
            if (array_key_exists('is_composite', $validated)) {
                $updateData['is_composite'] = (bool)$validated['is_composite'];
            }

            $product->update($updateData);

            if ($request->hasFile('images') || $request->has('images_existing')) {
                $images = $this->processImages($request);
                $product->update(['images' => $images]);
            }

            if (array_key_exists('categories', $validated)) {
                $validCategoryIds = $this->syncCategories($product, $workspace, $validated['categories'] ?? []);
            } else {
                $validCategoryIds = [];
            }

            if (array_key_exists('attributes', $validated)) {
                $this->syncAttributes($product, $validated['attributes'] ?? []);
            }

            if ($request->has('ingredient_groups')) {
                $this->syncIngredientGroups($product, $request->input('ingredient_groups', []));
            }

            if ($request->has('components')) {
                $this->syncComponents($product, $request);

                // 🆕 Автоматически обновляем флаг на основе наличия компонентов
                $hasComponents = $product->components()->exists();
                if ($product->is_composite !== $hasComponents) {
                    $product->update(['is_composite' => $hasComponents]);
                }
            }

            DB::commit();

            $product->load(['categories', 'attributes', 'ingredientGroups.ingredients', 'components']);

            $allCategoryIds = array_unique(array_merge($oldCategoryIds, $validCategoryIds ?? []));
            if (!empty($allCategoryIds)) {
                $workspace->categories()
                    ->whereIn('id', $allCategoryIds)
                    ->get()
                    ->each(fn($c) => $c->loadCount('products'));
            }

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
            ->update(['in_stop_list' => false,'is_active'=>true]);

        ActivityLogger::bulk('removed_from_stop_list', 'product', $ids);

        return response()->json([
            'message' => 'Товары убраны из стоп-листа',
            'count' => $count,
        ]);
    }

    /**
     * Загрузка картинок товара
     */
    public function uploadImages(Request $request, $worksapceUuid, Product $product)
    {
        $workspace = App::make('workspace');

        if ($product->workspace_id !== $workspace->id) {
            abort(403);
        }

        $request->validate([
            'images' => 'required|array|max:10',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp,gif|max:5120', // 5MB
        ]);

        $images = $product->images ?? [];

        foreach ($request->file('images') as $file) {
            $path = $file->store("products/{$product->id}", 'public');

            $images[] = [
                'url' => Storage::url($path),
                'path' => $path,
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
            ];
        }

        $product->update(['images' => $images]);

        return response()->json([
            'success' => true,
            'images' => $product->images,
        ]);
    }

    /**
     * Удаление картинки товара
     */
    public function deleteImage(Request $request, $worksapceUuid, Product $product)
    {
        $workspace = App::make('workspace');

        if ($product->workspace_id !== $workspace->id) {
            abort(403);
        }

        $request->validate([
            'index' => 'required|integer',
        ]);

        $images = $product->images ?? [];
        $index = $request->input('index');

        if (!isset($images[$index])) {
            return response()->json(['error' => 'Image not found'], 404);
        }

        // Удаляем файл
        $image = $images[$index];
        if (!empty($image['path'])) {
            Storage::disk('public')->delete($image['path']);
        }

        // Удаляем из массива
        array_splice($images, $index, 1);
        $product->update(['images' => $images]);

        return response()->json([
            'success' => true,
            'images' => $product->images,
        ]);
    }

    /**
     * Сортировка картинок
     */
    public function reorderImages(Request $request, $worksapceUuid, Product $product)
    {
        $workspace = App::make('workspace');

        if ($product->workspace_id !== $workspace->id) {
            abort(403);
        }

        $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer',
        ]);

        $currentImages = $product->images ?? [];
        $newOrder = $request->input('order');

        $reordered = [];
        foreach ($newOrder as $index) {
            if (isset($currentImages[$index])) {
                $reordered[] = $currentImages[$index];
            }
        }

        $product->update(['images' => $reordered]);

        return response()->json([
            'success' => true,
            'images' => $product->images,
        ]);
    }
}
