<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {

        Schema::create('menu_default_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('path');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });


        Schema::create('menu_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name')->default('Меню');

            // Тема и цвета
            $table->string('theme')->default('light');
            $table->string('accent_color')->default('#0d6efd');


            $table->string('bg_color')->default('#ffffff');
            $table->string('background_image_path')->nullable();

            // Логотип и контент
            $table->string('logo_path')->nullable();
            $table->text('description')->nullable();
            $table->text('contacts')->nullable();
            $table->string('contacts_position')->default('bottom'); // top, bottom

            // Макет
            $table->integer('items_per_row')->default(3);
            $table->string('layout_type')->default('grid');
            $table->string('orientation')->default('portrait');
            $table->json('category_ids')->nullable();

            // Отображение элементов
            $table->boolean('show_prices')->default(true);
            $table->boolean('show_descriptions')->default(true);
            $table->boolean('show_images')->default(true);

            $table->string('text_color')->default('#212529');
            $table->string('description_color')->default('#6c757d');
            $table->integer('product_image_height')->default(100);
            $table->string('all_products_label')->default('Все товары');
            $table->string('card_background_color')->default('#ffffff');
            $table->float('card_background_opacity')->default(0.85);

            // QR код общий
            $table->boolean('qr_enabled')->default(false);
            $table->string('qr_url')->nullable();
            $table->string('qr_position')->default('bottom-right'); // top-left, top-center, top-right, bottom-left, bottom-center, bottom-right
            $table->integer('qr_size')->default(100);

            // QR код для товаров
            $table->boolean('product_qr_enabled')->default(false);
            $table->string('product_qr_url_template')->nullable(); // Шаблон: {uuid} будет заменён на SKU/id товара

            $table->foreignId('default_image_id')
                ->nullable()
                ->constrained('menu_default_images')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('menu_configs');

        Schema::dropIfExists('menu_default_images');
    }
};
