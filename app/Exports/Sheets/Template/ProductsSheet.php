<?php

namespace App\Exports\Sheets\Template;

use App\Exports\WithStyledSheet;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ProductsSheet implements FromArray, WithHeadings, WithTitle, WithEvents
{
    use WithStyledSheet;

    public function array(): array
    {
        return [
            ['Кроссовки Nike Air Max', 'NK-001', 12990, 15990, 'Легендарные кроссовки', 'Обувь, Спорт', 30, 12, 45, 0.8, 'https://example.com/img1.jpg, https://example.com/img2.jpg', 'Nike', 'Черный', 'Кожа'],
            ['Футболка Adidas', 'AD-001', 3490, 4990, 'Классическая футболка', 'Одежда', 40, 5, 60, 0.2, 'https://example.com/img3.jpg', 'Adidas', 'Белый', 'Хлопок'],
        ];
    }

    public function headings(): array
    {
        return ['name', 'sku', 'price', 'old_price', 'description', 'categories', 'width', 'height', 'length', 'weight', 'images', 'attr_бренд', 'attr_цвет', 'attr_материал'];
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
            'F' => 25, // categories
            'G' => 10, // width
            'H' => 10, // height
            'I' => 10, // length
            'J' => 10, // weight
            'K' => 50, // images
            'L' => 15, // attr_бренд
            'M' => 15, // attr_цвет
            'N' => 15, // attr_материал
        ];
    }

    protected function columnFormats(): array
    {
        return [
            'C' => '#,##0.00', // price
            'D' => '#,##0.00', // old_price
            'G' => '0.00',     // width
            'H' => '0.00',     // height
            'I' => '0.00',     // length
            'J' => '0.00',     // weight
        ];
    }
}
