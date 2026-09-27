<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\ActivityLogger;

class ProductObserver
{
    public function created(Product $product): void
    {
        // 🛑 Пропускаем логирование при запуске из консоли (импорт, сиды и т.д.)
        if (app()->runningInConsole()) {
            return;
        }

        ActivityLogger::created($product);
    }

    public function updated(Product $product): void
    {
        // 🛑 Пропускаем логирование при запуске из консоли
        if (app()->runningInConsole()) {
            return;
        }

        // Не логируем если изменилось только in_stop_list (это отдельное действие)
        $changed = array_diff($product->getDirty(), ['in_stop_list', 'updated_at']);

        if (!empty($changed)) {
            ActivityLogger::updated($product, ['name', 'sku', 'price', 'old_price', 'description', 'is_active']);
        }
    }

    public function deleted(Product $product): void
    {
        // 🛑 Пропускаем логирование при запуске из консоли
        if (app()->runningInConsole()) {
            return;
        }

        ActivityLogger::deleted($product);
    }
}
