<?php

namespace App\Exports\Sheets;

use App\Exports\WithStyledSheet;
use App\Models\ProductAttribute;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;

class AttributesExportSheet implements FromCollection, WithHeadings, WithMapping, WithTitle, WithEvents
{
    use WithStyledSheet;

    public function __construct(protected int $workspaceId) {}

    public function collection(): Collection
    {
        return ProductAttribute::query()
            ->whereHas('product', function ($query) {
                $query->where('workspace_id', $this->workspaceId);
            })
            ->with('product')
            ->orderBy('product_id')
            ->orderBy('name')
            ->get();
    }

    public function headings(): array
    {
        return ['product_sku', 'product_name', 'attribute_name', 'attribute_value'];
    }

    public function map($attribute): array
    {
        return [
                $attribute->product->sku ?? '',
                $attribute->product->name ?? '',
            $attribute->name,
            $attribute->value,
        ];
    }

    public function title(): string
    {
        return 'Свойства';
    }

    protected function headerColor(): string
    {
        return 'FD7E14';
    }

    protected function columnWidths(): array
    {
        return [
            'A' => 20, // product_sku
            'B' => 35, // product_name
            'C' => 25, // attribute_name
            'D' => 40, // attribute_value
        ];
    }
}
