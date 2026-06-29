<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'workspace_id',
        'action',
        'entity_type',
        'entity_id',
        'entity_name',
        'description',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    // === Отношения к сущностям (полиморфные, опционально) ===

    public function entity()
    {
        $modelClass = $this->getEntityModelClass();
        if (!$modelClass) return null;

        return $this->belongsTo($modelClass, 'entity_id');
    }

    protected function getEntityModelClass(): ?string
    {
        return match ($this->entity_type) {
            'product' => Product::class,
            'category' => Category::class,
            'collection' => Collection::class,
            'webhook' => Webhook::class,
            'workspace' => Workspace::class,
            default => null,
        };
    }

    // === Хелперы для отображения ===

    public function getIconAttribute(): string
    {
        return match ($this->action) {
            'created' => 'fa-plus',
            'updated' => 'fa-pen',
            'deleted' => 'fa-trash',
            'restored' => 'fa-rotate-left',
            'imported' => 'fa-file-import',
            'exported' => 'fa-file-export',
            'synced' => 'fa-arrows-rotate',
            'login_attempt' => 'fa-key',
            'login_success' => 'fa-right-to-bracket',
            'login_failed' => 'fa-ban',
            'locked' => 'fa-lock',
            'unlocked' => 'fa-lock-open',
            'added_to_stop_list' => 'fa-hand',
            'removed_from_stop_list' => 'fa-circle-check',
            'added_to_collection' => 'fa-folder-plus',
            'removed_from_collection' => 'fa-folder-minus',
            default => 'fa-circle-info',
        };
    }

    public function getColorAttribute(): string
    {
        return match ($this->action) {
            'created' => '#198754',
            'updated' => '#0d6efd',
            'deleted' => '#dc3545',
            'restored' => '#6f42c1',
            'imported' => '#0dcaf0',
            'exported' => '#20c997',
            'synced' => '#fd7e14',
            'login_success', 'unlocked' => '#198754',
            'login_failed', 'locked' => '#dc3545',
            'login_attempt' => '#ffc107',
            'added_to_stop_list' => '#dc3545',
            'removed_from_stop_list' => '#198754',
            'added_to_collection' => '#0d6efd',
            'removed_from_collection' => '#6c757d',
            default => '#6c757d',
        };
    }

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'created' => 'Создано',
            'updated' => 'Обновлено',
            'deleted' => 'Удалено',
            'restored' => 'Восстановлено',
            'imported' => 'Импорт',
            'exported' => 'Экспорт',
            'synced' => 'Синхронизация',
            'login_attempt' => 'Попытка входа',
            'login_success' => 'Успешный вход',
            'login_failed' => 'Ошибка входа',
            'locked' => 'Заблокировано',
            'unlocked' => 'Разблокировано',
            'added_to_stop_list' => 'В стоп-лист',
            'removed_from_stop_list' => 'Из стоп-листа',
            'added_to_collection' => 'В коллекцию',
            'removed_from_collection' => 'Из коллекции',
            default => ucfirst($this->action),
        };
    }

    public function getEntityTypeLabelAttribute(): string
    {
        return match ($this->entity_type) {
            'product' => 'Товар',
            'category' => 'Категория',
            'collection' => 'Коллекция',
            'webhook' => 'Вебхук',
            'workspace' => 'Workspace',
            'master_code' => 'Мастер-код',
            'menu' => 'Меню',
            'import' => 'Импорт',
            'export' => 'Экспорт',
            default => ucfirst($this->entity_type),
        };
    }

    // === Скоупы для фильтрации ===

    public function scopeOfType($query, string $type)
    {
        return $query->where('entity_type', $type);
    }

    public function scopeWithAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
