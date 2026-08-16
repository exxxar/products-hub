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
        Schema::dropIfExists('product_ingredients');
        Schema::dropIfExists('product_ingredient_groups');
        Schema::dropIfExists('ingredients');
        Schema::dropIfExists('ingredient_groups');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
