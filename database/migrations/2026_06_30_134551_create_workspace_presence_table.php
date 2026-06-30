<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('workspace_presence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('user_key', 100);      // user_id или session_id
            $table->string('user_name')->nullable();
            $table->timestamp('last_seen')->index();

            $table->unique(['workspace_id', 'user_key']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('workspace_presence');
    }
};
