<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('collections', function (Blueprint $table) {
            // ✅ Тип коллекции (правило формирования)
            $table->string('type')->default('manual')->after('description');
            // manual — ручной выбор товаров
            // category_all — все товары одной категории
            // categories_all — все товары нескольких категорий
            // workspace_all — все товары workspace
            // category_select — выбор N товаров из категории

            // ✅ Параметры правила
            $table->json('rule_config')->nullable()->after('type');
            // Для category_all: { category_id: 1 }
            // Для categories_all: { category_ids: [1, 2, 3] }
            // Для category_select: { category_id: 1, count: 5 }

            // ✅ Ценообразование
            $table->string('pricing_type')->default('sum')->after('rule_config');
            // sum — сумма цен товаров
            // fixed — фиксированная цена

            $table->decimal('fixed_price', 10, 2)->nullable()->after('pricing_type');
            $table->decimal('fixed_old_price', 10, 2)->nullable()->after('fixed_price');

            // ✅ Картинка коллекции
            $table->json('images')->nullable()->after('fixed_old_price');

            // ✅ Активность и стоп-лист
            $table->boolean('is_active')->default(true)->after('images');
            $table->boolean('in_stop_list')->default(false)->after('is_active');

            // ✅ Описание для отображения
            $table->text('short_description')->nullable()->after('in_stop_list');
        });
    }

    public function down()
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->dropColumn([
                'type',
                'rule_config',
                'pricing_type',
                'fixed_price',
                'fixed_old_price',
                'images',
                'is_active',
                'in_stop_list',
                'short_description',
            ]);
        });
    }
};
