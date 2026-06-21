<?php

namespace App\Exports\Sheets\Template;

use App\Exports\WithStyledSheet;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class AttributesSheet implements FromArray, WithHeadings, WithTitle, WithEvents
{
    use WithStyledSheet;

    public function array(): array
    {
        return [
            ['NK-001', 'Материал', 'Кожа'],
            ['NK-001', 'Страна', 'Вьетнам'],
            ['AD-001', 'Сезон', 'Лето'],
        ];
    }

    public function headings(): array
    {
        return ['product_sku', 'attribute_name', 'attribute_value'];
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
            'B' => 25, // attribute_name
            'C' => 40, // attribute_value
        ];
    }
}
