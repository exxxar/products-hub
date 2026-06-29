<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\ActivityLogger;

class ProductObserver
{
    public function created(Product $product): void
    {
        ActivityLogger::created($product);
    }

    public function updated(Product $product): void
    {
        // Не логируем если изменилось только in_stop_list (это отдельное действие)
        $changed = array_diff($product->getDirty(), ['in_stop_list', 'updated_at']);

        if (!empty($changed)) {
            ActivityLogger::updated($product, ['name', 'sku', 'price', 'old_price', 'description', 'is_active']);
        }
    }

    public function deleted(Product $product): void
    {
        ActivityLogger::deleted($product);
    }
}
