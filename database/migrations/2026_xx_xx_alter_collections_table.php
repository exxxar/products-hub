<?php
// database/migrations/2026_xx_xx_alter_collections_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            // Удаляем старые поля
            $table->dropColumn([
                'rule_config',
                'fixed_old_price',
            ]);

            // Меняем images на одно image_url
            $table->dropColumn('images');
            $table->string('image_url')->nullable()->after('fixed_price');

            // Добавляем процент скидки
            $table->integer('discount_percent')->default(0)->after('fixed_price');

            // Порядок сортировки
            $table->integer('sort_order')->default(0)->after('in_stop_list');
        });

        // Меняем значения type: старые → custom
        DB::table('collections')
            ->whereIn('type', ['manual', 'category_all', 'categories_all', 'workspace_all', 'category_select'])
            ->update(['type' => 'custom']);
    }

    public function down(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->dropColumn(['image_url', 'discount_percent', 'sort_order']);
            $table->json('images')->nullable();
            $table->decimal('fixed_old_price', 10, 2)->nullable();
            $table->json('rule_config')->nullable();
        });
    }
};
