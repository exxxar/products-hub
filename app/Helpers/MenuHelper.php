<?php

namespace App\Helpers;

use App\Models\MenuConfig;
use App\Models\MenuDefaultImage;
use App\Models\Product;

class MenuHelper
{
    /**
     * Получить выравнивание для QR-кода
     */
    public static function getQrAlign(string $position): string
    {
        if (str_contains($position, 'left')) return 'left';
        if (str_contains($position, 'center')) return 'center';
        return 'right';
    }

    /**
     * Получить путь к изображению товара
     *
     * @param Product $product
     * @param MenuDefaultImage|null $defaultImage
     * @return string|null
     */
    public static function getProductImage(Product $product, ?MenuDefaultImage $defaultImage): ?string
    {
        // Сначала пытаемся взять изображение товара
        if (!empty($product->images) && isset($product->images[0]['url'])) {
            $imageUrl = $product->images[0]['url'];

            // Определяем путь к файлу
            if (str_contains($imageUrl, '/storage/')) {
                $imagePath = public_path(str_replace(url('/'), '', $imageUrl));
            } else {
                $imagePath = public_path('storage/' . str_replace('/storage/', '', $imageUrl));
            }

            if (file_exists($imagePath)) {
                return $imagePath;
            }
        }

        // Если нет изображения товара — используем дефолтное
        if ($defaultImage && $defaultImage->path) {
            $defaultPath = public_path('storage/' . $defaultImage->path);

            if (file_exists($defaultPath)) {
                return $defaultPath;
            }
        }

        return null;
    }

    /**
     * Получить URL изображения для превью (frontend)
     */
    public static function getProductImageUrl(Product $product, ?MenuDefaultImage $defaultImage): ?string
    {
        // Сначала пытаемся взять изображение товара
        if (!empty($product->images) && isset($product->images[0]['url'])) {
            return $product->images[0]['url'];
        }

        // Если нет изображения товара — используем дефолтное
        if ($defaultImage) {
            return $defaultImage->url;
        }

        return null;
    }
}
