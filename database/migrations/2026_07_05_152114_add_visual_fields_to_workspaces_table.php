<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->string('name')->nullable()->after('uuid');
            $table->string('logo_path')->nullable();
            $table->string('label')->nullable()->after('logo_path'); // Короткая метка (2-3 символа)
            $table->string('color')->default('#0d6efd')->after('label');
            $table->boolean('is_archived')->default(false)->after('color');
        });
    }

    public function down()
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->dropColumn(['logo_path', 'label', 'color', 'is_archived']);
        });
    }
};
