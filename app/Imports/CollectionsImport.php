<?php

namespace App\Imports;

use App\Models\Collection;
use App\Models\Product;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;

class CollectionsImport implements ToModel, WithHeadingRow, SkipsOnError
{
    use SkipsErrors;

    public function __construct(protected int $workspaceId) {}

    public function model(array $row): ?Collection
    {
        if (empty($row['name'])) {
            return null;
        }

        $collection = Collection::updateOrCreate(
            [
                'name' => trim($row['name']),
                'workspace_id' => $this->workspaceId
            ],
            [
                'name' => trim($row['name']),
                'description' => $row['description'] ?? null,
                'workspace_id' => $this->workspaceId
            ]
        );

        // Привязка товаров по SKU
        $skus = $this->parseList($row['product_skus'] ?? '');
        if (!empty($skus)) {
            $productIds = Product::query()
                ->where('workspace_id', $this->workspaceId)
                ->whereIn('sku', $skus)
                ->pluck('id')
                ->toArray();

            if (!empty($productIds)) {
                $syncData = [];
                foreach ($productIds as $index => $id) {
                    $syncData[$id] = ['sort_order' => $index];
                }
                $collection->products()->syncWithoutDetaching($syncData);
            }
        }

        return $collection;
    }

    protected function parseList(string $value): array
    {
        if (empty($value)) return [];
        $items = preg_split('/[,;\n\r]+/', $value);
        return array_filter(array_map('trim', $items));
    }
}
