<?php

namespace App\Imports;

use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\SkipsUnknownSheets;

class WorkspaceImport implements WithMultipleSheets, SkipsUnknownSheets
{
    public function __construct(protected int $workspaceId) {}

    public function sheets(): array
    {
        return [
            'Категории' => new CategoriesImport($this->workspaceId),
            'categories' => new CategoriesImport($this->workspaceId),

            'Товары' => new ProductsImport($this->workspaceId),
            'products' => new ProductsImport($this->workspaceId),

            'Коллекции' => new CollectionsImport($this->workspaceId),
            'collections' => new CollectionsImport($this->workspaceId),

            'Свойства' => new AttributesImport($this->workspaceId),
            'attributes' => new AttributesImport($this->workspaceId),
        ];
    }

    public function onUnknownSheet($sheetName)
    {
        // Пропускаем неизвестные вкладки
        Log::info("Unknown sheet skipped: {$sheetName}");
    }
}
