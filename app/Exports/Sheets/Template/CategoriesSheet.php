<?php

namespace App\Exports\Sheets\Template;

use App\Exports\WithStyledSheet;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class CategoriesSheet implements FromArray, WithHeadings, WithTitle, WithEvents
{
    use WithStyledSheet;

    public function array(): array
    {
        return [
            ['Одежда', 'Вся одежда'],
            ['Обувь', 'Вся обувь'],
            ['Аксессуары', 'Сумки, ремни, шарфы'],
        ];
    }

    public function headings(): array
    {
        return ['name', 'description'];
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
            'A' => 20, // name
            'B' => 30, // description
        ];
    }
}
