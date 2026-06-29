<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->string('master_code_hash')->nullable()->after('settings');
            $table->integer('master_code_attempts')->default(0)->after('master_code_hash');
            $table->timestamp('master_code_locked_until')->nullable()->after('master_code_attempts');
        });
    }

    public function down()
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->dropColumn([
                'master_code_hash',
                'master_code_attempts',
                'master_code_locked_until',
            ]);
        });
    }
};
