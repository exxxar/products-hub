<?php

namespace App\Http\Controllers;


use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class CategoryController extends Controller
{
    public function index()
    {
        $workspace = App::make('workspace');

        $categories = $workspace->categories()
            ->withCount('products')
            // 🔥 Сначала сортируем по пользовательскому порядку (NULL в конец)
            ->orderByRaw('COALESCE(sort_order, 999999) ASC')
            ->orderByDesc('products_count')   // При равенстве — по кол-ву товаров
            ->orderBy('name')                 // И по алфавиту
            ->get();

        return response()->json($categories);
    }

    /**
     * 🔥 Изменение порядка сортировки категорий
     */
    public function reorder(Request $request)
    {
        $workspace = App::make('workspace');

        $validated = $request->validate([
            'ordered_ids' => 'required|array',
            'ordered_ids.*' => 'integer|exists:categories,id',
        ]);

        // Проверяем, что все переданные ID принадлежат текущему workspace (безопасность)
        $validIds = $workspace->categories()
            ->whereIn('id', $validated['ordered_ids'])
            ->pluck('id')
            ->toArray();

        foreach ($validated['ordered_ids'] as $index => $id) {
            if (in_array($id, $validIds)) {
                $workspace->categories()
                    ->where('id', $id)
                    ->update(['sort_order' => $index]);
            }
        }

        return response()->json(['message' => 'Порядок категорий успешно обновлен']);
    }

    public function store(Request $request)
    {
        $workspace = App::make('workspace');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|integer|exists:categories,id',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'integer',
        ]);

        $category = $workspace->categories()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        // Привязка товаров если переданы
        if (!empty($validated['product_ids'])) {
            $validProductIds = $workspace->products()
                ->whereIn('id', $validated['product_ids'])
                ->pluck('id')
                ->toArray();

            if (!empty($validProductIds)) {
                $category->products()->sync($validProductIds);
            }
        }

        $category->loadCount('products'); // Теперь работает

        return response()->json($category, 201);
    }

    /**
     * Показать категорию
     */
    public function show($workspaceUuid, $categoryId)
    {
        $workspace = App::make('workspace');

        $category = $workspace->categories()->findOrFail($categoryId);

        $category->load(['products' => function($query) {
            $query->with(['categories', 'attributes']);
        }]);
        $category->loadCount('products');

        return response()->json($category);
    }

    /**
     * Обновление категории
     */
    public function update(Request $request, $workspaceUuid, $categoryId)
    {
        $workspace = App::make('workspace');

        $category = $workspace->categories()->findOrFail($categoryId);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|integer',
        ]);

        // Проверяем parent_id если он передан
        if (isset($validated['parent_id'])) {
            if ($validated['parent_id'] === $category->id) {
                return response()->json([
                    'message' => 'Категория не может быть родителем сама для себя'
                ], 422);
            }

            if ($validated['parent_id'] !== null) {
                $parentExists = $workspace->categories()
                    ->where('id', $validated['parent_id'])
                    ->exists();

                if (!$parentExists) {
                    return response()->json([
                        'message' => 'Родительская категория не найдена'
                    ], 422);
                }
            }
        }

        $category->update($validated);
        $category->loadCount('products');

        return response()->json($category);
    }

    /**
     * Удаление категории
     */
    public function destroy($workspaceUuid, $categoryId)
    {
        $workspace = App::make('workspace');


        $category = $workspace->categories()->findOrFail($categoryId);

        // Проверяем есть ли дочерние категории
        $hasChildren = $workspace->categories()
            ->where('parent_id', $category->id)
            ->exists();

        if ($hasChildren) {
            return response()->json([
                'message' => 'Нельзя удалить категорию с подкатегориями. Сначала удалите или переместите подкатегории.'
            ], 422);
        }

        // Отвязываем товары
        $category->products()->detach();

        // Сбрасываем parent_id у товаров если нужно (опционально)
      /*  $workspace->products()
            ->where('category_id', $category->id)
            ->update(['category_id' => null]);*/

        $category->delete();

        return response()->json(['message' => 'Категория удалена']);
    }

    /**
     * Добавить товары в категорию
     */
    public function addProducts(Request $request, $workspaceUuid, $categoryId)
    {
        $workspace = App::make('workspace');

        $category = $workspace->categories()->findOrFail($categoryId);

        $validated = $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'integer',
        ]);

        // Проверяем что товары принадлежат workspace
        $validProductIds = $workspace->products()
            ->whereIn('id', $validated['product_ids'])
            ->pluck('id')
            ->toArray();

        if (empty($validProductIds)) {
            return response()->json([
                'message' => 'Товары не найдены'
            ], 422);
        }

        $category->products()->syncWithoutDetaching($validProductIds);

        $category->loadCount('products');

        return response()->json($category);
    }

    /**
     * Удалить товары из категории
     */
    public function removeProducts(Request $request,$workspaceUuid,  $categoryId)
    {
        $workspace = App::make('workspace');

        $category = $workspace->categories()->findOrFail($categoryId);

        $validated = $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'integer',
        ]);

        $category->products()->detach($validated['product_ids']);

        $category->loadCount('products');

        return response()->json($category);
    }

    /**
     * Массовая синхронизация товаров категории
     */
    public function syncProducts(Request $request, $workspaceUuid, $categoryId)
    {
        $workspace = App::make('workspace');

        $category = $workspace->categories()->findOrFail($categoryId);

        $validated = $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'integer',
        ]);

        // Проверяем что товары принадлежат workspace
        $validProductIds = $workspace->products()
            ->whereIn('id', $validated['product_ids'])
            ->pluck('id')
            ->toArray();

        $category->products()->sync($validProductIds);

        $category->loadCount('products');

        return response()->json($category);
    }

    public function products(Request $request, $workspaceUuid, $categoryId)
    {
        $workspace = App::make('workspace');
        $category = $workspace->categories()->findOrFail($categoryId);
        $perPage = $request->input('per_page', 50);

        $query = $category->products()
            ->where('workspace_id', $workspace->id)
            ->with(['categories', 'attributes', 'ingredientGroups'])
            ->orderBy('created_at', 'desc');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->input('is_active'));
        }

        // ✅ Всегда возвращаем единый формат
        $products = $query->paginate($perPage);

        return response()->json([
            'products' => $products->items(),
            'pagination' => [
                'total' => $products->total(),
                'per_page' => $products->perPage(),
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'has_more' => $products->hasMorePages(),
            ]
        ]);
    }
}
