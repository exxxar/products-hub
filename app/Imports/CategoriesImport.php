<?php

namespace App\Imports;

use App\Models\Category;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;

class CategoriesImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError
{
    use SkipsErrors;

    public function __construct(protected int $workspaceId) {}

    public function model(array $row): ?Category
    {
        if (empty($row['name'])) {
            return null;
        }

        return Category::updateOrCreate(
            [
                'name' => trim($row['name']),
                'workspace_id' => $this->workspaceId
            ],
            [
                'name' => trim($row['name']),
                'description' => $row['description'] ?? null,
                'workspace_id' => $this->workspaceId
            ]
        );
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'name.required' => 'Название категории обязательно',
        ];
    }
}
