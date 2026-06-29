<?php

namespace App\Observers;

use App\Models\Collection;
use App\Services\ActivityLogger;

class CollectionObserver
{
    public function created(Collection $collection): void
    {
        ActivityLogger::created($collection);
    }

    public function updated(Collection $collection): void
    {
        ActivityLogger::updated($collection, ['name', 'description']);
    }

    public function deleted(Collection $collection): void
    {
        ActivityLogger::deleted($collection);
    }
}
