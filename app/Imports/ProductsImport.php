<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Category;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class ProductsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError, WithChunkReading
{
    use SkipsErrors;

    public function __construct(protected int $workspaceId) {}

    public function model(array $row): ?Product
    {
        if (empty($row['name'])) {
            return null;
        }

        // Парсим цену
        $price = $this->parsePrice($row['price'] ?? 0);
        $oldPrice = $this->parsePrice($row['old_price'] ?? null);

        // Парсим габариты
        $dimensions = [
            'width' => (float)($row['width'] ?? 0),
            'height' => (float)($row['height'] ?? 0),
            'length' => (float)($row['length'] ?? 0),
            'weight' => (float)($row['weight'] ?? 0),
        ];

        // Парсим изображения (разделитель запятая или перенос строки)
        $images = $this->parseImages($row['images'] ?? '');

        // Создаём/обновляем товар
        $product = Product::updateOrCreate(
            [
                'sku' => $row['sku'] ?? uniqid('SKU_'),
                'workspace_id' => $this->workspaceId
            ],
            [
                'name' => trim($row['name']),
                'sku' => $row['sku'] ?? uniqid('SKU_'),
                'price' => $price,
                'old_price' => $oldPrice,
                'description' => $row['description'] ?? null,
                'dimensions' => $dimensions,
                'images' => $images,
                'is_active' => true,
                'in_stop_list' => ($row['in_stop_list'] ?? 0) == 1,
                'external_source' => 'excel',
            ]
        );

        // === Привязка категорий ===
        $categoryNames = $this->parseList($row['categories'] ?? '');
        if (!empty($categoryNames)) {
            $categoryIds = [];
            foreach ($categoryNames as $catName) {
                $category = Category::firstOrCreate(
                    [
                        'name' => trim($catName),
                        'workspace_id' => $this->workspaceId
                    ],
                    [
                        'name' => trim($catName),
                        'workspace_id' => $this->workspaceId
                    ]
                );
                $categoryIds[] = $category->id;
            }
            $product->categories()->sync($categoryIds);
        }

        // === Атрибуты ===
        $attributes = $this->parseAttributes($row);
        if (!empty($attributes)) {
            $product->attributes()->delete(); // Очищаем старые
            foreach ($attributes as $attr) {
                $product->attributes()->create($attr);
            }
        }

        return $product;
    }

    protected function parsePrice($value): float
    {
        if (empty($value)) return 0;

        // Убираем символы валюты и пробелы
        $value = preg_replace('/[^\d.,]/', '', $value);
        // Заменяем запятую на точку
        $value = str_replace(',', '.', $value);

        return (float)$value;
    }

    protected function parseImages(string $value): array
    {
        if (empty($value)) return [];

        $urls = preg_split('/[,;\n\r]+/', $value);
        $images = [];

        foreach ($urls as $url) {
            $url = trim($url);
            if (!empty($url) && filter_var($url, FILTER_VALIDATE_URL)) {
                $images[] = [
                    'url' => $url,
                    'name' => basename($url),
                    'size' => null
                ];
            }
        }

        return $images;
    }

    protected function parseList(string $value): array
    {
        if (empty($value)) return [];

        $items = preg_split('/[,;\n\r]+/', $value);
        return array_filter(array_map('trim', $items));
    }

    protected function parseAttributes(array $row): array
    {
        $attributes = [];

        // Ищем колонки вида attr_* или attribute_*
        foreach ($row as $key => $value) {
            if (str_starts_with($key, 'attr_') || str_starts_with($key, 'attribute_')) {
                $name = str_replace(['attr_', 'attribute_'], '', $key);
                $name = str_replace('_', ' ', $name);

                if (!empty($value)) {
                    $attributes[] = [
                        'name' => ucfirst(trim($name)),
                        'value' => trim($value)
                    ];
                }
            }
        }

        return $attributes;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'price' => 'nullable',
            'sku' => 'nullable|string|max:100',
        ];
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
