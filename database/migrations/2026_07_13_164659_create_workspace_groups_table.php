<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('workspace_groups', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('color')->default('#0d6efd');
            $table->timestamps();
        });

        Schema::create('workspace_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['workspace_group_id', 'workspace_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('workspace_group_members');
        Schema::dropIfExists('workspace_groups');
    }
};
