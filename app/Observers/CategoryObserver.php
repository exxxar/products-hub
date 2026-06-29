<?php

namespace App\Observers;

use App\Models\Category;
use App\Services\ActivityLogger;

class CategoryObserver
{
    public function created(Category $category): void
    {
        ActivityLogger::created($category);
    }

    public function updated(Category $category): void
    {
        ActivityLogger::updated($category, ['name', 'parent_id', 'sort_order']);
    }

    public function deleted(Category $category): void
    {
        ActivityLogger::deleted($category);
    }
}
