<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class ActivityLogger
{
    /**
     * Логирование действия
     */
    public static function log(
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?string $description = null,
        ?array $metadata = null,
        ?string $entityName = null
    ): ActivityLog {
        $workspace = App::make('workspace');
        $request = request();

        return ActivityLog::create([
            'workspace_id' => $workspace->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'entity_name' => $entityName,
            'description' => $description ?? self::generateDescription($action, $entityType, $entityName, $metadata),
            'metadata' => $metadata,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }

    /**
     * Логирование создания с автоматическим описанием
     */
    public static function created(Model $model, ?array $metadata = null): ActivityLog
    {
        return self::log(
            action: 'created',
            entityType: self::getEntityType($model),
            entityId: $model->id,
            entityName: self::getEntityName($model),
            metadata: $metadata
        );
    }

    /**
     * Логирование обновления с отслеживанием изменений
     */
    public static function updated(Model $model, array $changed = [], ?array $metadata = null): ActivityLog
    {
        $changes = [];
        foreach ($changed as $field) {
            if ($model->isDirty($field)) {
                $changes[$field] = [
                    'old' => $model->getOriginal($field),
                    'new' => $model->$field,
                ];
            }
        }

        return self::log(
            action: 'updated',
            entityType: self::getEntityType($model),
            entityId: $model->id,
            entityName: self::getEntityName($model),
            metadata: array_merge(['changes' => $changes], $metadata ?? [])
        );
    }

    /**
     * Логирование удаления
     */
    public static function deleted(Model $model, ?array $metadata = null): ActivityLog
    {
        return self::log(
            action: 'deleted',
            entityType: self::getEntityType($model),
            entityId: $model->id,
            entityName: self::getEntityName($model),
            metadata: $metadata
        );
    }

    /**
     * Логирование массового действия
     */
    public static function bulk(
        string $action,
        string $entityType,
        array $ids,
        ?string $entityNamePlural = null,
        ?array $metadata = null
    ): ActivityLog {
        return self::log(
            action: $action,
            entityType: $entityType,
            entityName: $entityNamePlural,
            description: self::generateBulkDescription($action, $entityType, count($ids), $entityNamePlural),
            metadata: array_merge(['count' => count($ids), 'ids' => $ids], $metadata ?? [])
        );
    }

    /**
     * Определение типа сущности
     */
    protected static function getEntityType(Model $model): string
    {
        return match (true) {
            $model instanceof \App\Models\Product => 'product',
            $model instanceof \App\Models\Category => 'category',
            $model instanceof \App\Models\Collection => 'collection',
            $model instanceof \App\Models\Webhook => 'webhook',
            $model instanceof \App\Models\Workspace => 'workspace',
            $model instanceof \App\Models\MenuConfig => 'menu',
            default => class_basename($model),
        };
    }

    /**
     * Получение имени сущности для отображения
     */
    protected static function getEntityName(Model $model): ?string
    {
        return $model->name ?? $model->title ?? null;
    }

    /**
     * Генерация описания действия
     */
    protected static function generateDescription(
        string $action,
        string $entityType,
        ?string $entityName,
        ?array $metadata
    ): string {
        $entityLabel = match ($entityType) {
            'product' => 'товар',
            'category' => 'категория',
            'collection' => 'коллекция',
            'webhook' => 'вебхук',
            'workspace' => 'workspace',
            'master_code' => 'мастер-код',
            'menu' => 'меню',
            default => $entityType,
        };

        $name = $entityName ? " «{$entityName}»" : '';

        return match ($action) {
            'created' => "Создан{$name}",
            'updated' => "Обновлен{$name}",
            'deleted' => "Удален{$name}",
            'imported' => "Выполнен импорт",
            'exported' => "Выполнен экспорт",
            'synced' => "Выполнена синхронизация",
            default => ucfirst($action) . " {$entityLabel}{$name}",
        };
    }

    /**
     * Генерация описания массового действия
     */
    protected static function generateBulkDescription(
        string $action,
        string $entityType,
        int $count,
        ?string $entityName
    ): string {
        $entityLabel = match ($entityType) {
            'product' => 'товар',
            'category' => 'категория',
            'collection' => 'коллекция',
            default => $entityType,
        };

        $plural = match ($entityType) {
            'product' => self::pluralize($count, 'товар', 'товара', 'товаров'),
            'category' => self::pluralize($count, 'категория', 'категории', 'категорий'),
            'collection' => self::pluralize($count, 'коллекция', 'коллекции', 'коллекций'),
            default => "{$count} {$entityLabel}",
        };

        $actionLabel = match ($action) {
            'deleted' => 'Удалено',
            'added_to_stop_list' => 'Добавлено в стоп-лист',
            'removed_from_stop_list' => 'Убрано из стоп-листа',
            'added_to_collection' => 'Добавлено в коллекцию',
            'removed_from_collection' => 'Убрано из коллекции',
            default => ucfirst($action),
        };

        return "{$actionLabel}: {$count} {$plural}";
    }

    protected static function pluralize(int $count, string $one, string $two, string $five): string
    {
        $n = abs($count) % 100;
        if ($n >= 5 && $n <= 20) return $five;
        $n %= 10;
        if ($n === 1) return $one;
        if ($n >= 2 && $n <= 4) return $two;
        return $five;
    }
}
