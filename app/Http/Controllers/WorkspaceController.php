<?php

namespace App\Http\Controllers;

use App\Exports\ProductsExport;
use App\Models\Workspace;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
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
}
