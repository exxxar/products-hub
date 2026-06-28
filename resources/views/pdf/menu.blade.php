@php
    function hex2rgb($hex) {
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        return "$r, $g, $b";
    }
@endphp

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>{{ $config->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        @page {
            margin: 15mm;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: {{ $config->text_color }};
        }

        .bg-layer {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            @if($backgroundImagePath)
background: url('file://{{ $backgroundImagePath }}') center/cover no-repeat;
            @else
background: {{ $config->bg_color }};
        @endif
    }

        .container {
            position: relative;
            z-index: 1;
            padding: 5mm;
        }

        .header { text-align: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 3px solid {{ $config->accent_color }}; }
        .logo { max-width: 150px; max-height: 80px; margin-bottom: 10px; }
        .title { font-size: 26px; font-weight: bold; color: {{ $config->accent_color }}; margin-bottom: 6px; }
        .description { font-size: 12px; color: {{ $config->description_color }}; margin-bottom: 6px; }

        .contacts { font-size: 10px; color: {{ $config->description_color }}; line-height: 1.5; text-align: center; padding: 8px; background: {{ $config->theme === 'dark' ? 'rgba(255,255,255,0.1)' : 'rgba(255,255,255,0.6)' }}; border-radius: 4px; margin: 10px 0; }

        .category { margin-bottom: 20px; page-break-inside: avoid; }
        .category-title { font-size: 16px; font-weight: bold; color: {{ $config->accent_color }}; margin-bottom: 10px; padding-bottom: 4px; border-bottom: 2px solid {{ $config->accent_color }}; }

        .products-grid { display: table; width: 100%; border-spacing: 6px; }
        .products-row { display: table-row; }

        /* ✅ Карточка с настраиваемой подложкой */
        .product-card {
            display: table-cell;
            width: {{ 100 / $config->items_per_row }}%;
            vertical-align: top;
            padding: 8px;
            background: rgba({{ hex2rgb($config->card_background_color) }}, {{ $config->card_background_opacity }});
            border: 1px solid {{ $config->theme === 'dark' ? '#444444' : '#dee2e6' }};
            border-radius: 6px;
            position: relative;
            overflow: hidden;
        }

        /* ✅ Настраиваемая высота картинок */
        .product-image {
            width: 100%;
            height: {{ $config->product_image_height }}px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 6px;
        }

        .image-placeholder {
            width: 100%;
            height: {{ $config->product_image_height }}px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: {{ $config->theme === 'dark' ? '#333' : '#e9ecef' }};
            border-radius: 4px;
            margin-bottom: 6px;
            color: {{ $config->theme === 'dark' ? '#666' : '#adb5bd' }};
            font-size: 28px;
        }

        /* ✅ Настраиваемые цвета текста */
        .product-name {
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 2px;
            color: {{ $config->text_color }};
        }

        .product-desc {
            font-size: 9px;
            color: {{ $config->description_color }};
            margin-bottom: 4px;
            line-height: 1.3;
        }

        .price-row { display: flex; align-items: center; gap: 4px; }
        .price-old { font-size: 9px; text-decoration: line-through; opacity: 0.5; }
        .price-current { font-size: 13px; font-weight: bold; color: {{ $config->accent_color }}; }

        .product-qr-bottom { margin-top: 8px; padding-top: 8px; border-top: 1px dashed {{ $config->theme === 'dark' ? '#555' : '#dee2e6' }}; display: flex; justify-content: center; align-items: center; }
        .product-qr-bottom img { width: 60px; height: 60px; display: block; background: #fff; padding: 3px; border-radius: 3px; }

        /* ✅ List layout с настраиваемой подложкой */
        .product-item {
            display: block;
            width: 100%;
            margin-bottom: 8px;
            padding: 8px;
            background: rgba({{ hex2rgb($config->card_background_color) }}, {{ $config->card_background_opacity }});
            border: 1px solid {{ $config->theme === 'dark' ? '#444444' : '#dee2e6' }};
            border-radius: 6px;
            overflow: hidden;
        }

        .product-item-inner { display: flex; align-items: center; width: 100%; }
        .item-image { flex-shrink: 0; width: 80px; margin-right: 10px; }
        .item-image img { width: 80px; height: 80px; object-fit: cover; border-radius: 4px; display: block; }
        .item-image .image-placeholder { width: 80px; height: 80px; font-size: 20px; }
        .item-content { flex: 1; min-width: 0; overflow: hidden; }
        .item-footer { flex-shrink: 0; width: 100px; text-align: right; margin-left: 10px; }

        .qr-wrapper { margin: 10px 0; }
        .qr-wrapper img { border-radius: 3px; background: #fff; padding: 2px; }
        .qr-caption { font-size: 8px; opacity: 0.6; text-align: center; margin-top: 2px; }

        .footer { margin-top: 20px; padding-top: 10px; border-top: 2px solid {{ $config->accent_color }}; text-align: center; font-size: 9px; opacity: 0.5; }
    </style>
</head>
<body>
{{-- ✅ ФОН: фиксированный слой --}}
<div class="bg-layer"></div>

<div class="container">
    @if($config->contacts && $config->contacts_position === 'top')
        <div class="contacts">{!! nl2br(e($config->contacts)) !!}</div>
    @endif

    <div class="header">
        @if($config->logo_path)
            <img src="{{ public_path('storage/' . $config->logo_path) }}" class="logo" alt="Logo">
        @endif
        <div class="title">{{ $config->name }}</div>
        @if($config->description)
            <div class="description">{{ $config->description }}</div>
        @endif
    </div>

    @if($generalQrBase64 && str_contains($generalQrPosition, 'top'))
        <div class="qr-wrapper" style="text-align: {{ $generalQrAlign }};">
            <img src="{{ $generalQrBase64 }}" alt="QR">
        </div>
        <div class="qr-caption" style="text-align: {{ $generalQrAlign }};">Отсканируйте для перехода</div>
    @endif

    @foreach($groupedProducts as $categoryName => $products)
        <div class="category">
            <div class="category-title">{{ $config->all_products_label }}</div>

            @if($config->layout_type === 'grid')
                <div class="products-grid">
                    @foreach($products->chunk($config->items_per_row) as $chunk)
                        <div class="products-row">
                            @foreach($chunk as $item)
                                <div class="product-card">
                                    @if($item['image_path'])
                                        <img src="{{ $item['image_path'] }}" class="product-image" alt="{{ $item['name'] }}">
                                    @else
                                        <div class="image-placeholder">
                                            <i class="fa-solid fa-image"></i>
                                        </div>
                                    @endif

                                    <div class="product-name">{{ $item['name'] }}</div>
                                    @if($item['description'])<div class="product-desc">{{ $item['description'] }}</div>@endif

                                    @if($item['price'])
                                        <div class="price-row">
                                            @if($item['old_price'] && $item['old_price'] > $item['price'])
                                                <span class="price-old">{{ number_format($item['old_price'], 0, ',', ' ') }} ₽</span>
                                            @endif
                                            <span class="price-current">{{ number_format($item['price'], 0, ',', ' ') }} ₽</span>
                                        </div>
                                    @endif

                                    @if($item['qr_code'])
                                        <div class="product-qr-bottom">
                                            <img src="{{ $item['qr_code'] }}" alt="QR">
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            @else
                @foreach($products as $item)
                    <div class="product-item">
                        <div class="product-item-inner">
                            @if($item['image_path'])
                                <div class="item-image"><img src="{{ $item['image_path'] }}" alt="{{ $item['name'] }}"></div>
                            @else
                                <div class="item-image"><div class="image-placeholder"><i class="fa-solid fa-image"></i></div></div>
                            @endif

                            <div class="item-content">
                                <div class="product-name">{{ $item['name'] }}</div>
                                @if($item['description'])<div class="product-desc">{{ $item['description'] }}</div>@endif
                            </div>

                            @if($item['price'])
                                <div class="item-footer">
                                    @if($item['old_price'] && $item['old_price'] > $item['price'])
                                        <div class="price-old">{{ number_format($item['old_price'], 0, ',', ' ') }} ₽</div>
                                    @endif
                                    <div class="price-current">{{ number_format($item['price'], 0, ',', ' ') }} ₽</div>
                                </div>
                            @endif
                        </div>

                        @if($item['qr_code'])
                            <div class="product-qr-bottom">
                                <img src="{{ $item['qr_code'] }}" alt="QR">
                            </div>
                        @endif
                    </div>
                @endforeach
            @endif
        </div>
    @endforeach

    @if($generalQrBase64 && str_contains($generalQrPosition, 'bottom'))
        <div class="qr-wrapper" style="text-align: {{ $generalQrAlign }};">
            <img src="{{ $generalQrBase64 }}" alt="QR">
        </div>
        <div class="qr-caption" style="text-align: {{ $generalQrAlign }};">Отсканируйте для перехода</div>
    @endif

    @if($config->contacts && $config->contacts_position === 'bottom')
        <div class="contacts">{!! nl2br(e($config->contacts)) !!}</div>
    @endif

    <div class="footer">
        <p>Сгенерировано {{ now()->format('d.m.Y H:i') }}</p>
        <p>{{ $workspace->name }}</p>
    </div>
</div>
</body>
</html>
