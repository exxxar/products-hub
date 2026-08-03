<?php

// database/migrations/2026_xx_xx_create_collection_category_product_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('collection_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('category_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('category_name'); // денормализация (на случай удаления категории)
            $table->string('selection_rule')->default('one'); // one | multiple | all
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['collection_id', 'sort_order']);
        });


        Schema::create('collection_category_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_category_id')
                ->constrained('collection_categories')
                ->cascadeOnDelete();
            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_categories');
        Schema::dropIfExists('collection_category_product');
    }
};
