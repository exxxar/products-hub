<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('workspace_presence', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->after('user_name');
            $table->text('user_agent')->nullable()->after('ip_address');
        });
    }

    public function down()
    {
        Schema::table('workspace_presence', function (Blueprint $table) {
            $table->dropColumn(['ip_address', 'user_agent']);
        });
    }
};
