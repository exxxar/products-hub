<?php

namespace App\Exports\Sheets;

use App\Exports\WithStyledSheet;
use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;

class ProductsExportSheet implements FromCollection, WithHeadings, WithMapping, WithTitle, WithEvents
{
    use WithStyledSheet;

    public function __construct(protected int $workspaceId) {}

    public function collection(): Collection
    {
        return Product::query()
            ->where('workspace_id', $this->workspaceId)
            ->with(['categories', 'attributes', 'ingredients'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'name',
            'sku',
            'price',
            'old_price',
            'description',
            'categories',
            'width',
            'height',
            'length',
            'weight',
            'images',
            'is_active',
            'in_stop_list',
        ];
    }

    public function map($product): array
    {
        // Категории через запятую
        $categories = $product->categories
            ->pluck('name')
            ->implode(', ');

        // Изображения через запятую
        $images = collect($product->images ?? [])
            ->pluck('url')
            ->filter()
            ->implode(', ');

        // Габариты
        $dimensions = $product->dimensions ?? [];

        return [
            $product->name,
            $product->sku,
            (float) $product->price,
            (float) ($product->old_price ?? 0),
                $product->description ?? '',
            $categories,
            (float) ($dimensions['width'] ?? 0),
            (float) ($dimensions['height'] ?? 0),
            (float) ($dimensions['length'] ?? 0),
            (float) ($dimensions['weight'] ?? 0),
            $images,
            $product->is_active ? 1 : 0,
            $product->in_stop_list ? 1 : 0,
        ];
    }

    public function title(): string
    {
        return 'Товары';
    }

    protected function headerColor(): string
    {
        return '198754';
    }

    protected function columnWidths(): array
    {
        return [
            'A' => 35, // name
            'B' => 15, // sku
            'C' => 12, // price
            'D' => 12, // old_price
            'E' => 50, // description
            'F' => 30, // categories
            'G' => 10, // width
            'H' => 10, // height
            'I' => 10, // length
            'J' => 10, // weight
            'K' => 50, // images
            'L' => 12, // is_active
            'M' => 12, // in_stop_list
        ];
    }

    protected function columnFormats(): array
    {
        return [
            'C' => '#,##0.00',
            'D' => '#,##0.00',
            'G' => '0.00',
            'H' => '0.00',
            'I' => '0.00',
            'J' => '0.00',
        ];
    }
}
