<?php

namespace App\Exports;

use App\Exports\Sheets\CategoriesExportSheet;
use App\Exports\Sheets\CollectionsExportSheet;
use App\Exports\Sheets\ProductsExportSheet;
use App\Exports\Sheets\AttributesExportSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class WorkspaceDataExport implements WithMultipleSheets
{
    public function __construct(protected int $workspaceId) {}

    public function sheets(): array
    {
        return [
            new ProductsExportSheet($this->workspaceId),
            new CategoriesExportSheet($this->workspaceId),
            new CollectionsExportSheet($this->workspaceId),
            new AttributesExportSheet($this->workspaceId),
        ];
    }
}
