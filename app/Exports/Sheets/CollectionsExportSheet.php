<?php

namespace App\Exports\Sheets;

use App\Exports\WithStyledSheet;
use App\Models\Collection as CollectionModel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;

class CollectionsExportSheet implements FromCollection, WithHeadings, WithMapping, WithTitle, WithEvents
{
    use WithStyledSheet;

    public function __construct(protected int $workspaceId) {}

    public function collection(): Collection
    {
        return CollectionModel::query()
            ->where('workspace_id', $this->workspaceId)
            ->with(['products' => function ($query) {
                $query->orderBy('collection_product.sort_order');
            }])
            ->withCount('products')
            ->orderBy('name')
            ->get();
    }

    public function headings(): array
    {
        return ['name', 'description', 'products_count', 'product_skus'];
    }

    public function map($collection): array
    {
        // SKU товаров через запятую в порядке сортировки
        $productSkus = $collection->products
            ->pluck('sku')
            ->filter()
            ->implode(', ');

        return [
            $collection->name,
                $collection->description ?? '',
            $collection->products_count,
            $productSkus,
        ];
    }

    public function title(): string
    {
        return 'Коллекции';
    }

    protected function headerColor(): string
    {
        return '6F42C1';
    }

    protected function columnWidths(): array
    {
        return [
            'A' => 30, // name
            'B' => 45, // description
            'C' => 15, // products_count
            'D' => 50, // product_skus
        ];
    }
}
