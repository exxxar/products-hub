<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MenuConfig;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Helpers\MenuHelper;
use Illuminate\Support\Str;

class MenuGeneratorController extends Controller
{
    public function getConfig()
    {
        $workspace = App::make('workspace');
        $config = $workspace->menuConfig ?? new MenuConfig([
            'workspace_id' => $workspace->id,
            'name' => 'Меню',
            'theme' => 'light',
            'accent_color' => '#0d6efd',
            'text_color' => '#212529',
            'description_color' => '#6c757d',
            'bg_color' => '#ffffff',
            'items_per_row' => 3,
            'product_image_height' => 100,
            'layout_type' => 'grid',
            'all_products_label' => 'Все товары',
            'orientation' => 'portrait',
            'card_background_color' => '#ffffff',
            'card_background_opacity' => 0.85,
            'contacts_position' => 'bottom',
            'show_prices' => true,
            'show_descriptions' => true,
            'show_images' => true,
            'qr_enabled' => false,
            'qr_position' => 'bottom-right',
            'qr_size' => 100,
            'product_qr_enabled' => false,
        ]);

        return response()->json($config);
    }

    public function saveConfig(Request $request)
    {
        $workspace = App::make('workspace');

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'theme' => 'sometimes|string|in:light,dark,modern',
            'accent_color' => 'sometimes|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'bg_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'description' => 'nullable|string',
            'contacts' => 'nullable|string',
            'contacts_position' => 'sometimes|string|in:top,bottom',
            'items_per_row' => 'sometimes|integer|min:1|max:6',
            'layout_type' => 'sometimes|string|in:grid,list',
            'orientation' => 'sometimes|string|in:portrait,landscape',
            'category_ids' => 'nullable|array',
            'show_prices' => 'boolean',
            'show_descriptions' => 'boolean',
            'show_images' => 'boolean',
            'qr_enabled' => 'boolean',
            'qr_url' => 'nullable|url',
            'qr_position' => 'sometimes|string|in:top-left,top-center,top-right,bottom-left,bottom-center,bottom-right',
            'qr_size' => 'sometimes|integer|min:50|max:200',
            'product_qr_enabled' => 'boolean',
            'product_qr_url_template' => 'nullable',
            'default_image_id' => 'nullable|integer',
            'text_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'description_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'product_image_height' => 'sometimes|integer|min:50|max:300',
            'all_products_label' => 'nullable|string|max:100',
            'card_background_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'card_background_opacity' => 'sometimes|numeric|min:0|max:1',
        ]);

        $config = $workspace->menuConfig ?? new MenuConfig(['workspace_id' => $workspace->id]);
        $config->fill($validated);
        $config->save();

        return response()->json($config);
    }

    public function uploadLogo(Request $request)
    {
        $workspace = App::make('workspace');

        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $config = $workspace->menuConfig ?? new MenuConfig(['workspace_id' => $workspace->id]);

        if ($config->logo_path) {
            Storage::disk('public')->delete($config->logo_path);
        }

        $path = $request->file('logo')->store('menu-logos/' . $workspace->id, 'public');
        $config->logo_path = $path;
        $config->save();

        return response()->json([
            'logo_url' => $config->logo_url,
            'logo_path' => $path
        ]);
    }
    public function uploadBackgroundImage(Request $request)
    {
        $workspace = App::make('workspace');

        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $config = $workspace->menuConfig ?? new MenuConfig(['workspace_id' => $workspace->id]);

        // Удаляем старую фоновую картинку
        if ($config->background_image_path) {
            Storage::disk('public')->delete($config->background_image_path);
        }

        $path = $request->file('image')->store('menu-backgrounds/' . $workspace->id, 'public');
        $config->background_image_path = $path;
        $config->save();

        return response()->json([
            'background_image_url' => Storage::url($path),
            'background_image_path' => $path
        ]);
    }

    // ✅ НОВОЕ: Удаление фоновой картинки
    public function removeBackgroundImage()
    {
        $workspace = App::make('workspace');
        $config = $workspace->menuConfig;

        if (!$config) {
            return response()->json(['error' => 'Конфигурация не найдена'], 404);
        }

        if ($config->background_image_path) {
            Storage::disk('public')->delete($config->background_image_path);
            $config->background_image_path = null;
            $config->save();
        }

        return response()->json(['message' => 'Фоновая картинка удалена']);
    }

    public function preview()
    {
        $workspace = App::make('workspace');
        $config = Workspace::query()
            ->with(["menuConfig"])
            ->findOrFail($workspace->id);

        if (!$config) {
            return response()->json(['error' => 'Конфигурация не найдена'], 404);
        }

        $products = $this->getFilteredProducts($workspace, $config, 12);
        $groupedProducts = $this->groupProductsByCategory($products);

        $qrBase64 = null;
        if ($config->qr_enabled && $config->qr_url) {
            $qrBase64 = $config->generateQrBase64($config->qr_url, $config->qr_size);
        }

        $productQrs = [];
        if ($config->product_qr_enabled) {
            foreach ($products->take(6) as $product) {
                $productUrl = $config->getProductQrUrl($product);
                if ($productUrl) {
                    $productQrs[$product->id] = $config->generateQrBase64($productUrl, 60);
                }
            }
        }

        $defaultImage = null;
        if ($config->default_image_id) {
            $defaultImage = $config->defaultImage;
        }

        return response()->json([
            'config' => $config,
            'groupedProducts' => $groupedProducts,
            'workspace' => $workspace,
            'qr_base64' => $qrBase64,
            'product_qrs' => $productQrs,
            'default_image' => $defaultImage,
        ]);
    }
    public function generatePdf(Request $request)
    {
        $workspace = App::make('workspace');
        $config = $workspace->menuConfig;

        if (!$config) {
            return response()->json(['error' => 'Конфигурация не найдена'], 404);
        }

        if ($config->default_image_id && !$config->relationLoaded('defaultImage')) {
            $config->load('defaultImage');
        }

        $products = $this->getFilteredProducts($workspace, $config);
        $groupedProducts = $this->groupProductsByCategory($products);
        $defaultImage = $config->defaultImage;

        $productQrs = [];
        if ($config->product_qr_enabled) {
            foreach ($products as $product) {
                $productUrl = $config->getProductQrUrl($product);
                if ($productUrl) {
                    $productQrs[$product->id] = $config->generateQrBase64($productUrl, 60);
                }
            }
        }

        $processedGroups = [];
        foreach ($groupedProducts as $categoryName => $productsList) {
            $processedItems = [];
            foreach ($productsList as $product) {
                $processedItems[] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $config->show_descriptions ? Str::limit($product->description ?? '', 80) : '',
                    'price' => $config->show_prices ? $product->price : null,
                    'old_price' => $config->show_prices && $product->old_price ? $product->old_price : null,
                    'image_path' => $config->show_images ? MenuHelper::getProductImage($product, $defaultImage) : null,
                    'qr_code' => ($config->product_qr_enabled && isset($productQrs[$product->id])) ? $productQrs[$product->id] : null,
                ];
            }
            $processedGroups[$categoryName] = collect($processedItems);
        }

        $generalQrBase64 = '';
        $generalQrAlign = MenuHelper::getQrAlign($config->qr_position);
        if ($config->qr_enabled && $config->qr_url) {
            try {
                $generalQrBase64 = $config->generateQrBase64($config->qr_url, $config->qr_size);
            } catch (\Exception $e) {
                $generalQrBase64 = '';
            }
        }

        // ✅ Абсолютный путь к фоновой картинке для DomPDF
        $backgroundImagePath = null;
        if ($config->background_image_path) {
            $fullPath = public_path('storage/' . $config->background_image_path);
            if (file_exists($fullPath)) {
                $backgroundImagePath = $fullPath;
            }
        }

        $pdf = Pdf::loadView('pdf.menu', [
            'config' => $config,
            'groupedProducts' => $processedGroups,
            'workspace' => $workspace,
            'generalQrBase64' => $generalQrBase64,
            'generalQrAlign' => $generalQrAlign,
            'generalQrPosition' => $config->qr_position,
            'backgroundImagePath' => $backgroundImagePath,
        ]);

        // ✅ Ориентация
        $orientation = $config->orientation === 'landscape' ? 'landscape' : 'portrait';
        $pdf->setPaper('A4', $orientation);
        $pdf->setOption('isRemoteEnabled', true);
        $pdf->setOption('defaultFont', 'DejaVu Sans');

        // ✅ НУМЕРАЦИЯ СТРАНИЦ через Canvas (правильный способ в DomPDF)
        $canvas = $pdf->getCanvas();
        $font = $canvas->get_cpdf()->selectFont('Helvetica');

        // Определяем позицию в зависимости от ориентации
        if ($orientation === 'landscape') {
            // A4 landscape: 842 x 595 points
            $pageWidth = 842;
            $yPos = 20;
        } else {
            // A4 portrait: 595 x 842 points
            $pageWidth = 595;
            $yPos = 20;
        }

        $textColor = $config->theme === 'dark' ? [0.7, 0.7, 0.7] : [0.4, 0.4, 0.4];

        $canvas->page_text(
            $pageWidth / 2 - 50,  // x — центрирование
            $yPos,                 // y — снизу
            "Страница {PAGE_NUM} из {PAGE_COUNT}",
            null,                  // шрифт (по умолчанию)
            9,                     // размер
            $textColor             // цвет [r, g, b]
        );

        $filename = sprintf('%s-%s.pdf', $config->name, now()->format('Y-m-d'));
        return $pdf->download($filename);
    }

    protected function getFilteredProducts($workspace, $config, $limit = null)
    {
        $query = $workspace->products()
            ->where('is_active', true)
            ->where('in_stop_list', false)
            ->with(['categories', 'attributes']);

        if (!empty($config->category_ids)) {
            $query->whereHas('categories', function ($q) use ($config) {
                $q->whereIn('categories.id', $config->category_ids);
            });
        }

        $query->orderBy('name');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    protected function groupProductsByCategory($products)
    {
        $groupedProducts = [];
        foreach ($products as $product) {
            foreach ($product->categories as $category) {
                if (!isset($groupedProducts[$category->name])) {
                    $groupedProducts[$category->name] = [];
                }
                $groupedProducts[$category->name][] = $product;
            }
        }

        if (empty($groupedProducts)) {
            $groupedProducts['Все товары'] = $products;
        }

        return $groupedProducts;
    }
}
