<?php

namespace App\Http\Controllers;

use App\Exports\WorkspaceDataExport;
use App\Exports\WorkspaceTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\WorkspaceImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class ImportController extends Controller
{
    public function store(Request $request)
    {
        $workspace = App::make('workspace');

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // 10MB
        ]);

        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();

        Log::info('Import started', [
            'workspace' => $workspace->uuid,
            'file' => $fileName,
            'size' => $file->getSize()
        ]);

        DB::beginTransaction();

        try {
            Excel::import(
                new WorkspaceImport($workspace->id),
                $file
            );

            DB::commit();

            // Загружаем обновлённые данные
            $products = $workspace->products()->with(['categories', 'attributes'])->get();
            $categories = $workspace->categories()->withCount('products')->get();
            $collections = $workspace->collections()->withCount('products')->get();

            Log::info('Import completed', [
                'workspace' => $workspace->uuid,
                'products' => $products->count(),
                'categories' => $categories->count(),
                'collections' => $collections->count()
            ]);

            return response()->json([
                'message' => 'Импорт успешно завершён',
                'stats' => [
                    'products' => $products->count(),
                    'categories' => $categories->count(),
                    'collections' => $collections->count()
                ]
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();

            $failures = $e->failures();
            $errors = [];

            foreach ($failures as $failure) {
                $errors[] = [
                    'row' => $failure->row(),
                    'attribute' => $failure->attribute(),
                    'errors' => $failure->errors(),
                    'sheet' => method_exists($failure, 'sheet') ? $failure->sheet() : null,
                    'values' => $failure->values()
                ];
            }

            Log::warning('Import validation errors', [
                'workspace' => $workspace->uuid,
                'errors_count' => count($errors)
            ]);

            return response()->json([
                'message' => 'Ошибки валидации при импорте',
                'errors' => array_slice($errors, 0, 50) // Максимум 50 ошибок
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Import failed', [
                'workspace' => $workspace->uuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Ошибка при импорте',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function downloadTemplate()
    {
        return Excel::download(new WorkspaceTemplateExport(), 'import-template.xlsx');
    }

    /**
     * Скачать шаблон Excel
     */
    public function template()
    {
        return response()->json([
            'message' => 'Скачайте шаблон Excel',
            'sheets' => [
                'Категории' => ['name', 'description'],
                'Товары' => ['name', 'sku', 'price', 'old_price', 'description', 'categories', 'width', 'height', 'length', 'weight', 'images', 'attr_бренд', 'attr_цвет'],
                'Коллекции' => ['name', 'description', 'product_skus'],
                'Свойства' => ['product_sku', 'attribute_name', 'attribute_value']
            ],
            'example_data' => [
                'Категории' => [
                    ['name' => 'Одежда', 'description' => 'Вся одежда'],
                    ['name' => 'Обувь', 'description' => 'Вся обувь']
                ],
                'Товары' => [
                    ['name' => 'Кроссовки Nike', 'sku' => 'NK-001', 'price' => '12990', 'old_price' => '15990', 'description' => 'Отличные кроссовки', 'categories' => 'Обувь, Спорт', 'width' => '30', 'height' => '12', 'length' => '45', 'weight' => '0.8', 'images' => 'https://example.com/img1.jpg, https://example.com/img2.jpg', 'attr_бренд' => 'Nike', 'attr_цвет' => 'Черный'],
                ],
                'Коллекции' => [
                    ['name' => 'Хиты продаж', 'description' => 'Самые популярные товары', 'product_skus' => 'NK-001, NK-002']
                ],
                'Свойства' => [
                    ['product_sku' => 'NK-001', 'attribute_name' => 'Материал', 'attribute_value' => 'Кожа']
                ]
            ]
        ]);
    }

    public function export(Request $request)
    {
        $workspace = App::make('workspace');

        $filename = sprintf(
            'workspace-%s-%s.xlsx',
            $workspace->uuid,
            now()->format('Y-m-d_H-i')
        );

        return Excel::download(
            new WorkspaceDataExport($workspace->id),
            $filename
        );
    }
}
