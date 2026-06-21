<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

trait WithStyledSheet
{
    /**
     * Цвет заголовка (переопределяется в каждом листе)
     */
    protected function headerColor(): string
    {
        return '0D6EFD';
    }

    /**
     * Ширина колонок (переопределяется в каждом листе)
     * Формат: ['A' => 20, 'B' => 15, ...]
     */
    protected function columnWidths(): array
    {
        return [];
    }

    /**
     * Формат ячеек (переопределяется в каждом листе)
     * Формат: ['B' => '#,##0.00', 'C' => 'yyyy-mm-dd', ...]
     */
    protected function columnFormats(): array
    {
        return [];
    }

    /**
     * Высота строк данных (по умолчанию авто)
     */
    protected function rowHeight(): ?int
    {
        return null;
    }

    /**
     * Высота заголовка
     */
    protected function headerRowHeight(): int
    {
        return 28;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();
                $range = "A1:{$highestColumn}{$highestRow}";

                // === Границы для всех ячеек ===
                $sheet->getStyle($range)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'D0D0D0'],
                        ],
                    ],
                ]);

                // === Стили заголовка (первая строка) ===
                $headerRange = "A1:{$highestColumn}1";
                $sheet->getStyle($headerRange)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'size' => 11,
                        'name' => 'Calibri',
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $this->headerColor()],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'borders' => [
                        'outline' => [
                            'borderStyle' => Border::BORDER_MEDIUM,
                            'color' => ['rgb' => $this->headerColor()],
                        ],
                    ],
                ]);

                // === Стили данных ===
                if ($highestRow > 1) {
                    $dataRange = "A2:{$highestColumn}{$highestRow}";
                    $sheet->getStyle($dataRange)->applyFromArray([
                        'font' => [
                            'size' => 10,
                            'name' => 'Calibri',
                        ],
                        'alignment' => [
                            'vertical' => Alignment::VERTICAL_TOP,
                            'wrapText' => true,
                        ],
                    ]);

                    // Чередование цвета строк (зебра)
                    for ($row = 2; $row <= $highestRow; $row++) {
                        if ($row % 2 === 0) {
                            $rowRange = "A{$row}:{$highestColumn}{$row}";
                            $sheet->getStyle($rowRange)->applyFromArray([
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'F8F9FA'],
                                ],
                            ]);
                        }
                    }

                    // Высота строк данных
                    if ($this->rowHeight()) {
                        for ($row = 2; $row <= $highestRow; $row++) {
                            $sheet->getRowDimension($row)->setRowHeight($this->rowHeight());
                        }
                    }
                }

                // === Высота заголовка ===
                $sheet->getRowDimension(1)->setRowHeight($this->headerRowHeight());

                // === Явные ширины колонок ===
                $widths = $this->columnWidths();
                if (!empty($widths)) {
                    foreach ($widths as $column => $width) {
                        $sheet->getColumnDimension($column)->setWidth($width);
                    }
                } else {
                    // Если ширины не заданы — устанавливаем дефолтную
                    foreach (range('A', $highestColumn) as $col) {
                        $sheet->getColumnDimension($col)->setWidth(15);
                    }
                }

                // === Форматы колонок ===
                $formats = $this->columnFormats();
                if (!empty($formats)) {
                    foreach ($formats as $column => $format) {
                        $columnRange = "{$column}2:{$column}{$highestRow}";
                        $sheet->getStyle($columnRange)->getNumberFormat()->setFormatCode($format);
                    }
                }

                // === Закрепление заголовка ===
                $sheet->freezePane('A2');
            },
        ];
    }
}
