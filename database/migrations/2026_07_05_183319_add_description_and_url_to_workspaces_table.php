<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->string('url')->nullable()->after('description');
        });
    }

    public function down()
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->dropColumn(['description', 'url']);
        });
    }
};
