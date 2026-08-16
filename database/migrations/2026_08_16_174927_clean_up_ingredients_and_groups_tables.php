<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Таблица ingredients
        Schema::table('ingredients', function (Blueprint $table) {
            // Добавляем extra_price
            if (!Schema::hasColumn('ingredients', 'extra_price')) {
                $table->decimal('extra_price', 10, 2)->default(0)->after('name');
            }

            // Удаляем лишнее
            if (Schema::hasColumn('ingredients', 'image_url')) {
                $table->dropColumn('image_url');
            }
            if (Schema::hasColumn('ingredients', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });

        // 2. Таблица ingredient_groups
        Schema::table('ingredient_groups', function (Blueprint $table) {
            if (Schema::hasColumn('ingredient_groups', 'min')) {
                $table->dropColumn('min');
            }
            if (Schema::hasColumn('ingredient_groups', 'max')) {
                $table->dropColumn('max');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropColumn('extra_price');
            $table->string('image_url')->nullable();
            $table->boolean('is_active')->default(true);
        });

        Schema::table('ingredient_groups', function (Blueprint $table) {
            $table->integer('min')->default(0);
            $table->integer('max')->default(0);
        });
    }
};
