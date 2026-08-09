<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\CollectionCategory;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CollectionController extends Controller
{
    // ============================================================
    // Index
    // ============================================================

    public function index()
    {
        $workspace = App::make('workspace');

        $collections = $workspace->collections()
            ->with([
                'collectionCategories' => fn($q) => $q->orderBy('sort_order'),
                'collectionCategories.products',
                'products',
            ])
            ->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($collections->map(fn($c) => $this->formatCollection($c, short: false)));
    }

    protected function processImageUpload(Request $request, $workspace, ?Collection $collection = null, ?string $collectionName = null): ?string
    {
        // 1. Если загружен НОВЫЙ файл — сохраняем его
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $directory = "workspaces/{$workspace->id}/collections";
            $fileName = $this->generateImageFileName($file, $collectionName ?? 'collection');

            // storeAs возвращает относительный путь: "workspaces/1/collections/slug-uuid.jpg"
            $relativePath = $file->storeAs($directory, $fileName, 'public');

            // Добавляем префикс для полного пути
            $fullStoragePath = "storage/app/public/{$relativePath}";

            // Удаляем старое изображение ПОСЛЕ успешного сохранения нового
            if ($collection && $collection->image_url) {
                $this->deleteOldImage($collection->image_url);
            }

            return $fullStoragePath; // В БД уйдет "storage/app/public/workspaces/..."
        }

        // 2. Если пришёл флаг удаления — удаляем файл и возвращаем null
        if ($request->boolean('remove_image')) {
            if ($collection && $collection->image_url) {
                $this->deleteOldImage($collection->image_url);
            }
            return null;
        }

        // 3. Если файл не трогали — оставляем старое изображение (при обновлении)
        if ($collection) {
            return $collection->image_url;
        }

        return null;
    }

    protected function deleteOldImage(?string $imageUrl): void
    {
        if (!$imageUrl) return;

        // Если это путь с префиксом storage/app/public/
        if (Str::startsWith($imageUrl, 'storage/app/public/')) {
            // Извлекаем относительный путь для Storage::disk('public')
            $relativePath = Str::after($imageUrl, 'storage/app/public/');
            Storage::disk('public')->delete($relativePath);
            return;
        }

        // Если это относительный путь (старый формат: workspaces/1/collections/...)
        if (!Str::startsWith($imageUrl, ['http://', 'https://', '/storage/'])) {
            Storage::disk('public')->delete($imageUrl);
            return;
        }

        // Если это полный URL или /storage/... путь — извлекаем относительный путь
        if (Str::startsWith($imageUrl, ['/storage/', 'http://', 'https://'])) {
            $path = str_replace('/storage/', '', parse_url($imageUrl, PHP_URL_PATH));
            if ($path && $path !== '/') {
                Storage::disk('public')->delete($path);
            }
        }
    }

    protected function formatImageUrl(?string $value): ?string
    {
        if (!$value) return null;

        // Если это уже полный URL
        if (Str::startsWith($value, ['http://', 'https://'])) {
            return $value;
        }

        // Если это путь с префиксом storage/app/public/
        if (Str::startsWith($value, 'storage/app/public/')) {
            $relativePath = Str::after($value, 'storage/app/public/');
            return Storage::disk('public')->url($relativePath);
        }

        // Если это относительный путь (старый формат)
        return Storage::disk('public')->url($value);
    }
    // ============================================================
    // Store
    // ============================================================

    public function store(Request $request)
    {
        // 🔥 Парсим JSON-строку из FormData обратно в массив
        if ($request->has('categories') && is_string($request->input('categories'))) {
            $request->merge(['categories' => json_decode($request->input('categories'), true)]);
        }

        $workspace = App::make('workspace');

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'type' => 'required|in:custom,all_products',
            'pricing_type' => 'required|in:sum,fixed',
            'fixed_price' => 'nullable|numeric|min:0',
            'discount_percent' => 'nullable|integer|min:0|max:100',

            // 🔥 Только файл, никакого base64
            'image' => 'nullable|image|max:5120',
            'remove_image' => 'nullable|boolean',

            'categories' => 'required_if:type,custom|array',
            'categories.*.category_id' => 'required|integer|exists:categories,id',
            'categories.*.selection_rule' => 'required|in:one,multiple,all',
            'categories.*.product_ids' => 'required|array|min:1',
            'categories.*.product_ids.*' => 'integer|exists:products,id',

            'all_product_ids' => 'required_if:type,all_products|array',
            'all_product_ids.*' => 'integer|exists:products,id',
        ]);

        DB::beginTransaction();
        try {
            // 🔥 ИСПРАВЛЕНО: передаём $request и $workspace
            $imagePath = $this->processImageUpload($request, $workspace, null, $validated['name'] ?? 'collection');

            $collection = $workspace->collections()->create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'short_description' => $validated['short_description'] ?? null,
                'type' => $validated['type'],
                'pricing_type' => $validated['pricing_type'],
                'fixed_price' => $validated['pricing_type'] === Collection::PRICING_FIXED
                    ? ($validated['fixed_price'] ?? null)
                    : null,
                'discount_percent' => $validated['discount_percent'] ?? 0,
                'image_url' => $imagePath,
                'is_active' => true,
            ]);

            $this->syncCollectionStructure($collection, $workspace, $validated);

            DB::commit();

            $collection->load([
                'collectionCategories.products.categories',

                'products.categories',

            ]);

            return response()->json($this->formatCollection($collection, short: false), 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Collection store failed: ' . $e->getMessage());
            return response()->json(['message' => 'Ошибка при создании коллекции'], 500);
        }
    }

    // ============================================================
    // Update
    // ============================================================

    public function update(Request $request, $workspaceUuid, Collection $collection)
    {
        // 🔥 Парсим JSON-строку из FormData
        if ($request->has('categories') && is_string($request->input('categories'))) {
            $request->merge(['categories' => json_decode($request->input('categories'), true)]);
        }

        $workspace = App::make('workspace');

        if ($collection->workspace_id !== $workspace->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'type' => 'sometimes|in:custom,all_products',
            'pricing_type' => 'sometimes|in:sum,fixed',
            'fixed_price' => 'nullable|numeric|min:0',
            'discount_percent' => 'nullable|integer|min:0|max:100',

            // 🔥 Файл + флаг удаления
            'image' => 'nullable|image|max:5120',
            'remove_image' => 'nullable|boolean',

            'categories' => 'sometimes|array',
            'categories.*.category_id' => 'required|integer|exists:categories,id',
            'categories.*.selection_rule' => 'required|in:one,multiple,all',
            'categories.*.product_ids' => 'required|array|min:1',
            'categories.*.product_ids.*' => 'integer|exists:products,id',

            'all_product_ids' => 'sometimes|array',
            'all_product_ids.*' => 'integer|exists:products,id',
        ]);

        DB::beginTransaction();
        try {
            // Собираем только текстовые атрибуты (без картинки)
            $attributes = array_intersect_key($validated, array_flip([
                'name', 'description', 'short_description', 'type',
                'pricing_type', 'fixed_price', 'discount_percent',
            ]));

            if (isset($attributes['pricing_type']) && $attributes['pricing_type'] !== Collection::PRICING_FIXED) {
                $attributes['fixed_price'] = null;
            }

            $collection->update($attributes);

            // 🔥 ИСПРАВЛЕНО: Обрабатываем изображение отдельно
            $imagePath = $this->processImageUpload($request, $workspace, $collection, $collection->name);
            if ($imagePath !== $collection->image_url) {
                $collection->update(['image_url' => $imagePath]);
            }

            $this->syncCollectionStructure($collection, $workspace, $validated);

            DB::commit();

            $collection->load([
                'collectionCategories.products.categories',

                'products.categories',

            ]);

            return response()->json($this->formatCollection($collection, short: false));
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Collection update failed: ' . $e->getMessage());
            return response()->json(['message' => 'Ошибка при обновлении коллекции'], 500);
        }
    }

    // ============================================================
    // Destroy
    // ============================================================

    public function destroy($workspaceUuid, Collection $collection)
    {
        $workspace = App::make('workspace');

        if ($collection->workspace_id !== $workspace->id) {
            abort(403);
        }

        DB::beginTransaction();
        try {
            // Удаляем изображение вместе с коллекцией
            $this->deleteOldImage($collection->image_url);

            $collection->collectionCategories()->each(fn($cc) => $cc->products()->detach());
            $collection->collectionCategories()->delete();
            $collection->products()->detach();
            $collection->delete();

            DB::commit();
            return response()->json(['message' => 'Коллекция удалена']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Ошибка при удалении'], 500);
        }
    }

    // ============================================================
    // Upload / Remove image (отдельные эндпоинты)
    // ============================================================

    public function uploadImage(Request $request, Collection $collection)
    {
        $workspace = App::make('workspace');

        if ($collection->workspace_id !== $workspace->id) {
            abort(403);
        }

        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $this->deleteOldImage($collection->image_url);

        $file = $request->file('image');
        $directory = "workspaces/{$workspace->id}/collections";
        $fileName = $this->generateImageFileName($file, $collection->name);

        $path = $file->storeAs($directory, $fileName, 'public');
        $url = Storage::url($path);

        $collection->update(['image_url' => $path]);

        return response()->json([
            'image_url' => $url,
        ]);
    }

    public function removeImage(Collection $collection)
    {
        $workspace = App::make('workspace');
        if ($collection->workspace_id !== $workspace->id) abort(403);

        $this->deleteOldImage($collection->image_url);

        $collection->update(['image_url' => null]);

        return response()->json(['image_url' => null]);
    }

    // ============================================================
    // Show
    // ============================================================

    public function show($workspaceUuid, Collection $collection)
    {
        $workspace = App::make('workspace');

        if ($collection->workspace_id !== $workspace->id) {
            abort(403);
        }

        $collection->load([
            'collectionCategories.products.categories',
            'collectionCategories.products.attributes',

            'products.categories',
            'products.attributes',

        ]);

        $grouped = collect($collection->getGroupedProducts())
            ->map(function ($group) {
                return [
                    'collection_category_id' => $group['collection_category_id'] ?? null,
                    'category_id' => $group['category_id'],
                    'category_name' => $group['category_name'],
                    'selection_rule' => $group['selection_rule'],
                    'rule_label' => $group['rule_label'],
                    'subtotal' => $group['subtotal'],
                    'products' => $group['products']->map(fn($p) => $this->formatProduct($p))->values(),
                ];
            })
            ->values();

        return response()->json([
            'collection' => $this->formatCollection($collection, short: false),
            'groups' => $grouped,
            'total_products' => $collection->products_count,
            'total_price' => $collection->calculated_price,
            'final_price' => $collection->final_price,
        ]);
    }

    // ============================================================
    // Helpers & Sync
    // ============================================================

    protected function syncCollectionStructure(Collection $collection, $workspace, array $validated): void
    {
        $type = $validated['type'] ?? $collection->type;

        $shouldResyncCustom = ($type === Collection::TYPE_CUSTOM && isset($validated['categories']));
        $shouldResyncAll = ($type === Collection::TYPE_ALL_PRODUCTS && isset($validated['all_product_ids']));

        if ($shouldResyncCustom || $shouldResyncAll) {
            $collection->collectionCategories()->each(fn($cc) => $cc->products()->detach());
            $collection->collectionCategories()->delete();
            $collection->products()->detach();
        }

        if ($shouldResyncCustom) {
            foreach ($validated['categories'] as $sortOrder => $catData) {
                $category = $workspace->categories()->find($catData['category_id']);
                if (!$category) continue;

                $cc = $collection->collectionCategories()->create([
                    'category_id' => $category->id,
                    'category_name' => $category->name,
                    'selection_rule' => $catData['selection_rule'],
                    'sort_order' => $sortOrder,
                ]);

                $validProductIds = $workspace->products()
                    ->whereIn('id', $catData['product_ids'])
                    ->pluck('id')
                    ->toArray();

                $cc->products()->attach($validProductIds);
            }
        } elseif ($shouldResyncAll) {
            $validProductIds = $workspace->products()
                ->whereIn('id', $validated['all_product_ids'])
                ->pluck('id')
                ->toArray();

            $collection->products()->attach($validProductIds);
        }
    }


    /**
     * 🔥 Генерирует читаемое имя файла: {slug-названия}-{uuid}.{ext}
     * Пример: "happy-meal-a1b2c3d4.jpg"
     */
    protected function generateImageFileName($file, string $collectionName): string
    {
        $extension = $file->getClientOriginalExtension();
        $slug = Str::slug($collectionName);

        // Если slug пустой (кириллица без translit) - используем только UUID
        if (empty($slug)) {
            $slug = 'collection';
        }

        // Обрезаем slug до 50 символов для разумной длины имени
        $slug = Str::limit($slug, 50, '');

        // Короткий UUID (8 символов) для уникальности
        $shortUuid = Str::substr(Str::uuid()->toString(), 0, 8);

        return "{$slug}-{$shortUuid}.{$extension}";
    }



    // ============================================================
    // Formatters
    // ============================================================

    protected function formatProduct(Product $p): array
    {
        return [
            'id' => $p->id,
            'name' => $p->name,
            'sku' => $p->sku,
            'price' => (float) $p->price,
            'old_price' => $p->old_price ? (float) $p->old_price : null,
            'description' => $p->description,
            'images' => $p->images,
            'is_active' => (bool) $p->is_active,
            'in_stop_list' => (bool) $p->in_stop_list,
            'categories' => $p->categories->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
            ])->values(),
        ];
    }

    protected function formatCollection(Collection $c, bool $short = false): array
    {
        $data = [
            'id' => $c->id,
            'name' => $c->name,
            'short_description' => $c->short_description,
            'type' => $c->type,
            'type_label' => $c->type_label,
            'pricing_type' => $c->pricing_type,
            'price' => $c->calculated_price,
            'final_price' => $c->final_price,
            'discount_percent' => $c->discount_percent,
            'discount_amount' => $c->discount_amount,
            'products_count' => $c->products_count ?? $c->products->count(),
            'image_url' => $this->formatImageUrl($c->image_url),
            'is_active' => $c->is_active,
            'in_stop_list' => $c->in_stop_list,
            'created_at' => $c->created_at->toIso8601String(),
        ];

        if (!$short) {
            $data['description'] = $c->description;
            $data['rule_description'] = $c->rule_description;
            $data['fixed_price'] = $c->fixed_price;
            $data['old_price'] = $c->calculated_old_price;
        }

        if ($c->type === Collection::TYPE_CUSTOM) {
            $data['collection_categories'] = $c->collectionCategories->map(function (CollectionCategory $cc) use ($short) {
                $res = [
                    'id' => $cc->id,
                    'category_id' => $cc->category_id,
                    'category_name' => $cc->category_name,
                    'selection_rule' => $cc->selection_rule,
                    'rule_label' => $cc->rule_label,
                    'sort_order' => $cc->sort_order,
                    'product_ids' => $cc->products->pluck('id')->values(),
                    'products_count' => $cc->products->count(),
                    'subtotal' => (float) $cc->subtotal,
                ];

                if (!$short) {
                    $res['products'] = $cc->products->map(fn($p) => $this->formatProduct($p))->values();
                }

                return $res;
            })->values();
        } else {
            $data['all_product_ids'] = $c->products->pluck('id')->values();
        }

        return $data;
    }


}
