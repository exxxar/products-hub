<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('composite_product_id')->constrained('products')->cascadeOnDelete(); // Главный товар
            $table->foreignId('component_product_id')->constrained('products')->cascadeOnDelete(); // Товар в составе
            $table->integer('quantity')->default(1); // Количество в составе
            $table->integer('sort_order')->default(0);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            // Запрещаем добавлять один и тот же товар в состав дважды
            $table->unique(['composite_product_id', 'component_product_id'], 'product_components_unique');
        });

        // Добавляем флаг составного товара
        if (!Schema::hasColumn('products', 'is_composite')) {
            Schema::table('products', function (Blueprint $table) {
                $table->boolean('is_composite')->default(false)->after('in_stop_list');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_components');
    }
};
