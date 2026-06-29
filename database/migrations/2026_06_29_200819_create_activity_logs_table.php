<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();

            // Тип действия
            $table->string('action'); // created, updated, deleted, imported, exported, synced, login_attempt, locked, unlocked

            // Сущность
            $table->string('entity_type'); // product, category, collection, webhook, workspace, master_code, menu
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('entity_name')->nullable(); // Для удобного отображения

            // Описание
            $table->text('description');
            $table->json('metadata')->nullable(); // Дополнительные данные (old/new значения, количество и т.д.)

            // Контекст
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();

            // Индексы для быстрой фильтрации
            $table->index(['workspace_id', 'entity_type']);
            $table->index(['workspace_id', 'action']);
            $table->index(['workspace_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('activity_logs');
    }
};
