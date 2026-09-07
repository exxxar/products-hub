<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('ingredient_groups', function (Blueprint $table) {
            $table->string('selection_rule', 20)->default('single')->after('name');
            // single   — выбрать ровно один
            // multiple — выбрать несколько (ограничено min/max)
            // all      — все обязательны
            // optional — необязательно (можно ничего не выбрать)

            $table->unsignedInteger('min_select')->default(1)->after('selection_rule');
            $table->unsignedInteger('max_select')->default(1)->after('min_select');
            $table->boolean('is_required')->default(true)->after('max_select');
        });
    }

    public function down()
    {
        Schema::table('ingredient_groups', function (Blueprint $table) {
            $table->dropColumn(['selection_rule', 'min_select', 'max_select', 'is_required']);
        });
    }
};
