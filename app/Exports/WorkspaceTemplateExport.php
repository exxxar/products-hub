<?php

namespace App\Exports;

use App\Exports\Sheets\Template\AttributesSheet;
use App\Exports\Sheets\Template\CategoriesSheet;
use App\Exports\Sheets\Template\CollectionsSheet;
use App\Exports\Sheets\Template\ProductsSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class WorkspaceTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new CategoriesSheet(),
            new ProductsSheet(),
            new CollectionsSheet(),
            new AttributesSheet(),
        ];
    }
}
