<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class MenuConfig extends Model
{
    protected $fillable = [
        'workspace_id',
        'name',
        'theme',
        'accent_color',
        'text_color',
        'description_color',
        'bg_color',
        'background_image_path',
        'logo_path',
        'description',
        'contacts',
        'contacts_position',
        'items_per_row',
        'product_image_height',
        'layout_type',
        'all_products_label',
        'orientation',
        'card_background_color',
        'card_background_opacity',
        'category_ids',
        'show_prices',
        'show_descriptions',
        'show_images',
        'qr_enabled',
        'qr_url',
        'qr_position',
        'qr_size',
        'product_qr_enabled',
        'product_qr_url_template',
        'default_image_id',
    ];

    protected $casts = [
        'category_ids' => 'array',
        'show_prices' => 'boolean',
        'show_descriptions' => 'boolean',
        'show_images' => 'boolean',
        'qr_enabled' => 'boolean',
        'product_qr_enabled' => 'boolean',
        'qr_size' => 'integer',
        'items_per_row' => 'integer',
        'product_image_height' => 'integer',
        'card_background_opacity' => 'float',
    ];

    protected $appends = ['logo_url', 'background_style','background_full_path'];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function defaultImage()
    {
        return $this->belongsTo(MenuDefaultImage::class);
    }

    public function getLogoUrlAttribute()
    {
        if (!$this->logo_path) return null;
        return Storage::url($this->logo_path);
    }

    public function getBackgroundImageUrlAttribute()
    {
        if (!$this->background_image_path) return null;
        return Storage::url($this->background_image_path);
    }

    public function getBackgroundStyleAttribute(): string
    {
        if ($this->background_image_path) {
            $imageUrl = url('storage/' . $this->background_image_path);
            return "background: url('{$imageUrl}') center/cover no-repeat;";
        }
        return "background: {$this->bg_color};";
    }

    // ✅ Переименован: теперь это background_full_path (полный путь на сервере)
    public function getBackgroundFullPathAttribute()
    {
        if (!$this->background_image_path) return null;
        return public_path('storage/' . $this->background_image_path);
    }

    public function generateQrBase64(string $url, int $size = 100): string
    {
        try {
            return 'data:image/png;base64,' . base64_encode(
                    QrCode::format('png')
                        ->size($size)
                        ->margin(1)
                        ->generate($url)
                );
        } catch (\Exception $e) {
            return '';
        }
    }

    public function getProductQrUrl(Product $product): ?string
    {
        if (!$this->product_qr_enabled || !$this->product_qr_url_template) {
            return null;
        }

        $url = $this->product_qr_url_template;
        $url = str_replace('{sku}', $product->sku ?? '', $url);
        $url = str_replace('{id}', $product->id ?? '', $url);
        $url = str_replace('{name}', urlencode($product->name ?? ''), $url);
        $url = str_replace('{uuid}', $this->workspace->uuid ?? '', $url);

        return $url;
    }

    public function delete()
    {
        if ($this->logo_path) {
            Storage::disk('public')->delete($this->logo_path);
        }
        if ($this->background_image_path) {
            Storage::disk('public')->delete($this->background_image_path);
        }
        return parent::delete();
    }
}
