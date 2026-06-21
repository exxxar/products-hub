<?php

namespace App\Imports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;

class AttributesImport implements ToModel, WithHeadingRow, SkipsOnError
{
    use SkipsErrors;

    public function __construct(protected int $workspaceId) {}

    public function model(array $row): ?Product
    {
        if (empty($row['product_sku'])) {
            return null;
        }

        $product = Product::query()
            ->where('sku', trim($row['product_sku']))
            ->where('workspace_id', $this->workspaceId)
            ->first();

        if (!$product) {
            return null;
        }

        $attrName = trim($row['attribute_name'] ?? '');
        $attrValue = trim($row['attribute_value'] ?? '');

        if (!empty($attrName) && !empty($attrValue)) {
            $product->attributes()->updateOrCreate(
                ['name' => $attrName],
                ['value' => $attrValue]
            );
        }

        return $product;
    }
}
