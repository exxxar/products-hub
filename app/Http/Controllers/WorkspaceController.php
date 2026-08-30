<?php

namespace App\Http\Controllers;

use App\Exports\ProductsExport;
use App\Models\Product;
use App\Models\Workspace;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class WorkspaceController extends Controller
{

    public function deleteAllProducts(Request $request)
    {
        $workspace = App::make('workspace');

        $count = $workspace->products()->count();

        // Удаляем файлы изображений
        $products = $workspace->products()->with('categories')->get();
        foreach ($products as $product) {
            if (!empty($product->images)) {
                foreach ($product->images as $image) {
                    if (!empty($image['path'])) {
                        Storage::disk('public')->delete($image['path']);
                    }
                }
            }
            // Отвязываем категории
            $product->categories()->detach();
        }

        // Удаляем все товары
        $workspace->products()->delete();

        // Логирование
        ActivityLogger::log(
            'deleted',
            'product',
            null,
            "Удалено всех товаров: {$count}",
            ['count' => $count, 'workspace' => $workspace->name]
        );

        return response()->json([
            'success' => true,
            'message' => "Удалено {$count} товаров",
            'count' => $count,
        ]);
    }

    /**
     * Удалить весь workspace
     */
    public function destroy(Request $request)
    {
        $workspace = App::make('workspace');

        $name = $workspace->name;
        $uuid = $workspace->uuid;

        // Удаляем все связанные данные
        // Товары и их картинки
        foreach ($workspace->products as $product) {
            if (!empty($product->images)) {
                foreach ($product->images as $image) {
                    if (!empty($image['path'])) {
                        Storage::disk('public')->delete($image['path']);
                    }
                }
            }
        }
        $workspace->products()->delete();

        // Логи workspace
        $workspace->activityLogs()->delete();

        // Presence
        $workspace->presences()->delete();

        // Связи с другими досками
        $workspace->groups()->detach();

        // Логотип workspace
        if ($workspace->logo_path) {
            Storage::disk('public')->delete($workspace->logo_path);
        }

        // Удаляем сам workspace (каскадно удалит категории, коллекции и т.д.)
        $workspace->delete();

        // Логирование (в общий лог, т.к. workspace уже удалён)
        \Illuminate\Support\Facades\Log::info('Workspace deleted', [
            'uuid' => $uuid,
            'name' => $name,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Workspace «{$name}» удалён",
        ]);
    }

    public function show(Request $request, $uuid)
    {

        $workspace = Workspace::where('uuid', $uuid)
            ->with(["products", "categories","collections"])
            ->first();

        if (!is_null($workspace)) {
            $request->session()->put('workspace_uuid', $workspace->uuid);


            if (!$workspace->access_token) {
                $workspace->generateAccessToken();
            }

            Inertia::share(["workspace_uuid" => $workspace->uuid]);
            return Inertia::render('Workspace', [
                'item' => $workspace,
            ]);
        }

        // Создаём новую доску
        $workspace = Workspace::create([
            'uuid' => Str::uuid(),
        ]);
        $request->session()->put('workspace_uuid', $workspace->uuid);
        $workspace->generateAccessToken();


        return redirect('/workspace/' . $workspace->uuid);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'label' => 'nullable|string|max:3',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        $workspace = Workspace::create([
            'uuid' => Str::uuid()->toString(),
            'name' => $validated['name'],
            'settings' => [
                'visual' => [
                    'label' => $validated['label'] ?? null,
                    'color' => $validated['color'] ?? '#0d6efd',
                ],
            ],
        ]);

        return response()->json([
            'id' => $workspace->id,
            'uuid' => $workspace->uuid,
            'name' => $workspace->name,
            'label' => $workspace->label,
            'color' => $workspace->color,
            'initials' => $workspace->initials,
        ], 201);
    }



    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Workspace $workspace)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $workspace = App::make('workspace');

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:500',
            'url' => 'nullable|url',
            'settings' => 'nullable|array',
        ]);

        $updateData = [];

        if (isset($validated['name'])) {
            $updateData['name'] = $validated['name'];
        }

        if (isset($validated['description'])) {
            $updateData['description'] = $validated['description'];
        }

        if (isset($validated['url'])) {
            $updateData['url'] = $validated['url'];
        }

        if (isset($validated['settings'])) {
            $updateData['settings'] = array_merge($workspace->settings ?? [], $validated['settings']);
        }

        $workspace->update($updateData);

        return response()->json([
            'success' => true,
            'workspace' => $workspace->fresh(),
        ]);
    }



    public function exportExcel(Request $request)
    {
        $workspace = $request->workspace;

        return Excel::download(
            new ProductsExport($workspace->id),
            'products.xlsx'
        );
    }

    public function duplicate(Request $request, string $uuid)
    {
        $validated = $request->validate([
            'source_uuid' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // $source - это текущая доска, из которой инициировано копирование
        $source = Workspace::withCount(['products', 'categories', 'collections'])
            ->where('uuid', $validated["source_uuid"])
            ->firstOrFail();

        $workspace = App::make('workspace');

        DB::beginTransaction();

        try {
            $newWorkspace = Workspace::withoutEvents(function () use ($source, $validated, $workspace) {

                // 1. Создаем саму доску
                $newWs = Workspace::create([
                    'uuid' => Str::uuid()->toString(),
                    'name' => $validated['name'],
                    'description' => $validated['description'] ?? $source->description,
                    'settings' => $source->settings,
                    'label' => $source->label,
                    'color' => $source->color,
                    'logo_path' => $source->logo_path,
                    'url' => $source->url,
                ]);

                $workspace->linkWorkspace($newWs->uuid);

                // 2. Копируем категории
                $categoryMap = [];
                foreach ($source->categories as $cat) {
                    $newCat = $newWs->categories()->create([
                        'name' => $cat->name,
                        'parent_id' => null,
                        'sort_order' => $cat->sort_order ?? 0,
                    ]);
                    $categoryMap[$cat->id] = $newCat->id;
                }

                // Восстанавливаем иерархию категорий
                foreach ($source->categories as $cat) {
                    if ($cat->parent_id && isset($categoryMap[$cat->parent_id])) {
                        $newWs->categories()
                            ->where('id', $categoryMap[$cat->id])
                            ->update(['parent_id' => $categoryMap[$cat->parent_id]]);
                    }
                }

                // 3. Копируем коллекции и их правила
                $collectionMap = [];
                $collectionCategoryMap = [];

                foreach ($source->collections as $col) {
                    $newCol = $newWs->collections()->create([
                        'name' => $col->name,
                        'description' => $col->description,
                        'short_description' => $col->short_description,
                        'type' => $col->type,
                        'pricing_type' => $col->pricing_type,
                        'fixed_price' => $col->fixed_price,
                        'discount_percent' => $col->discount_percent,
                        'image_url' => $col->image_url,
                        'is_active' => $col->is_active,
                        'in_stop_list' => $col->in_stop_list,
                        'sort_order' => $col->sort_order,
                    ]);
                    $collectionMap[$col->id] = $newCol->id;

                    foreach ($col->collectionCategories as $cc) {
                        $newCc = $newCol->collectionCategories()->create([
                            'category_id' => $categoryMap[$cc->category_id] ?? null,
                            'category_name' => $cc->category_name,
                            'selection_rule' => $cc->selection_rule,
                            'sort_order' => $cc->sort_order,
                        ]);
                        $collectionCategoryMap[$cc->id] = $newCc->id;
                    }
                }

                // 4. Копируем товары и все их связи
                $source->products()
                    ->with(['attributes', 'ingredientGroups.ingredients', 'collections', 'components', 'categories'])
                    ->chunk(100, function ($products) use ($newWs, $categoryMap, $collectionMap, $collectionCategoryMap) {

                        $productMap = [];
                        $componentsToAttach = [];

                        foreach ($products as $prod) {
                            $newProd = $newWs->products()->create([
                                'name' => $prod->name,
                                'sku' => $prod->sku,
                                'price' => $prod->price,
                                'old_price' => $prod->old_price,
                                'description' => $prod->description,
                                'images' => $prod->images,
                                'dimensions' => $prod->dimensions,
                                'external_source' => $prod->external_source,
                                'config' => $prod->config,
                                'external_id' => $prod->external_id,
                                'is_active' => $prod->is_active,
                                'is_composite' => $prod->is_composite,
                                'in_stop_list' => $prod->in_stop_list,
                            ]);

                            $productMap[$prod->id] = $newProd->id;

                            foreach ($prod->attributes as $attr) {
                                $newProd->attributes()->create(['name' => $attr->name, 'value' => $attr->value]);
                            }

                            foreach ($prod->ingredientGroups as $group) {
                                $newGroup = $newProd->ingredientGroups()->create(['name' => $group->name, 'sort_order' => $group->sort_order]);
                                foreach ($group->ingredients as $ing) {
                                    $newGroup->ingredients()->create([
                                        'name' => $ing->name, 'extra_price' => $ing->extra_price,
                                        'is_default' => $ing->is_default, 'sort_order' => $ing->sort_order,
                                    ]);
                                }
                            }

                            $newCategoryIds = $prod->categories->map(fn($c) => $categoryMap[$c->id] ?? null)->filter()->values()->all();
                            if (!empty($newCategoryIds)) {
                                $newProd->categories()->attach($newCategoryIds);
                            }

                            $attachCollections = [];
                            foreach ($prod->collections as $col) {
                                $newColId = $collectionMap[$col->id] ?? null;
                                if ($newColId) {
                                    $attachCollections[$newColId] = ['sort_order' => $col->pivot->sort_order ?? 0];
                                }
                            }
                            if (!empty($attachCollections)) {
                                $newProd->collections()->attach($attachCollections);
                            }

                            foreach ($prod->components as $component) {
                                $newComponentId = $productMap[$component->id] ?? null;
                                if ($newComponentId) {
                                    $componentsToAttach[$newProd->id][$newComponentId] = [
                                        'quantity' => $component->pivot->quantity,
                                        'sort_order' => $component->pivot->sort_order,
                                    ];
                                }
                            }
                        }

                        foreach ($componentsToAttach as $newProdId => $components) {
                            $targetProd = $newWs->products()->find($newProdId);
                            if ($targetProd) {
                                $targetProd->components()->attach($components);
                            }
                        }

                        foreach ($collectionCategoryMap as $oldCcId => $newCcId) {
                            $oldCc = \App\Models\CollectionCategory::with('products')->find($oldCcId);
                            if ($oldCc) {
                                $newCc = \App\Models\CollectionCategory::find($newCcId);
                                if ($newCc) {
                                    $newProdIds = $oldCc->products->map(fn($p) => $productMap[$p->id] ?? null)->filter()->values()->all();
                                    if (!empty($newProdIds)) {
                                        $newCc->products()->attach($newProdIds);
                                    }
                                }
                            }
                        }
                    });

                return $newWs;
            });




            DB::commit();

            Log::info('Workspace duplicated and linked successfully', [
                'source_uuid' => $source->uuid,
                'new_id' => $newWorkspace->id,
                'new_uuid' => $newWorkspace->uuid
            ]);




            return response()->json([
                'success' => true,
                'workspace' => [
                    'id' => $newWorkspace->id,
                    'uuid' => $newWorkspace->uuid,
                    'name' => $newWorkspace->name,
                    'label' => $newWorkspace->label,
                    'color' => $newWorkspace->color,
                    'logo_url' => $newWorkspace->logo_url,
                    'is_current' => false,
                    'stats' => [
                        'products_count' => $source->products_count,
                        'categories_count' => $source->categories_count,
                        'collections_count' => $source->collections_count,
                    ]
                ],

            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Workspace duplication failed', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при копировании доски: ' . $e->getMessage()
            ], 500);
        }
    }
}
