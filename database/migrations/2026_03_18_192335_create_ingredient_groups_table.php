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
        Schema::create('ingredient_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces');
            $table->string('name');
            $table->string('selection_type')->default('single'); // single/multiple/all/optional
            $table->integer('min')->default(0);
            $table->integer('max')->default(1);
            $table->timestamps();
        });

        Schema::create('product_ingredient_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_group_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_required')->default(false);
            $table->string('selection_type')->nullable(); // override
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingredient_groups');
    }
};
