<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('webhooks', function (Blueprint $table) {
            $table->text('last_error')->nullable()->after('last_status');
        });
    }

    public function down()
    {
        Schema::table('webhooks', function (Blueprint $table) {
            $table->dropColumn('last_error');
        });
    }
};
