<?php

namespace App\Exports\Sheets\Template;

use App\Exports\WithStyledSheet;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class CollectionsSheet implements FromArray, WithHeadings, WithTitle, WithEvents
{
    use WithStyledSheet;

    public function array(): array
    {
        return [
            ['Хиты продаж', 'Самые популярные товары', 'NK-001, AD-001'],
            ['Новинки', 'Новые поступления', 'NK-001'],
        ];
    }

    public function headings(): array
    {
        return ['name', 'description', 'product_skus'];
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
            'C' => 40, // product_skus
        ];
    }
}
