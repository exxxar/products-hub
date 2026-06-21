<?php

namespace App\Exports\Sheets;

use App\Exports\WithStyledSheet;
use App\Models\Category;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;

class CategoriesExportSheet implements FromCollection, WithHeadings, WithMapping, WithTitle, WithEvents
{
    use WithStyledSheet;

    public function __construct(protected int $workspaceId) {}

    public function collection(): Collection
    {
        return Category::query()
            ->where('workspace_id', $this->workspaceId)
            ->withCount('products')
            ->orderBy('name')
            ->get();
    }

    public function headings(): array
    {
        return ['name', 'description', 'products_count'];
    }

    public function map($category): array
    {
        return [
            $category->name,
                $category->description ?? '',
            $category->products_count,
        ];
    }

    public function title(): string
    {
        return 'Категории';
    }

    protected function headerColor(): string
    {
        return '0D6EFD';
    }

    protected function columnWidths(): array
    {
        return [
            'A' => 30, // name
            'B' => 50, // description
            'C' => 15, // products_count
        ];
    }
}
