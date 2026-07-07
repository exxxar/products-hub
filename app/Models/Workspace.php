<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class Workspace extends Model
{

    protected $table = 'workspaces';

    protected $fillable = [
        'uuid', 'name', 'settings',
        'logo_path', 'label', 'color', 'is_archived', // ✅ Новые поля
        'master_code_hash', 'master_code_attempts', 'master_code_locked_until', "description" ,"url"
    ];

    protected $casts = [
        'settings' => 'array',
        'master_code_locked_until' => 'datetime',
    ];

    protected $hidden = [
        'master_code_hash',
    ];

    protected $appends = [
        'has_master_code',
        'is_master_rate_limited',
        'master_locked_until',
        'master_attempts_left',
        'logo_url',
        'display_name',
        'initials',
    ];

    public function menuConfig()
    {
        return $this->hasOne(MenuConfig::class);
    }

    public function menuDefaultImages()
    {
        return $this->hasMany(MenuDefaultImage::class);
    }

    public function webhooks()
    {
        return $this->hasMany(Webhook::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function collections()
    {
        return $this->hasMany(Collection::class);
    }

    public function ingredientGroups()
    {
        return $this->hasMany(IngredientGroup::class);
    }

    public function ingredients()
    {
        return $this->hasMany(Ingredient::class);
    }



    // === Работа с settings ===

    /**
     * Получить настройку по ключу (поддержка вложенности через точку)
     */
    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Установить настройку
     */
    public function setSetting(string $key, $value): void
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->update(['settings' => $settings]);
    }

    /**
     * Установить несколько настроек сразу
     */
    public function setSettings(array $data): void
    {
        $settings = $this->settings ?? [];

        foreach ($data as $key => $value) {
            data_set($settings, $key, $value);
        }

        $this->update(['settings' => $settings]);
    }

    // === Визуальные поля (через settings) ===


    public function getLogoUrlAttribute(): ?string
    {
        $path = $this->logo_path;

        if (!$path) {
            return null;
        }

        // ✅ Если это внешний URL (начинается с http), возвращаем как есть
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        // Иначе это локальный путь — генерируем URL через Storage
        return Storage::url($path);
    }

    public function getLabelAttribute(): ?string
    {
        return $this->getSetting('visual.label');
    }

    public function getColorAttribute(): string
    {
        return $this->getSetting('visual.color', '#0d6efd');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->label ?: mb_substr($this->name, 0, 2);
    }

    public function getInitialsAttribute(): string
    {
        return mb_strtoupper($this->label ?: mb_substr($this->name, 0, 2));
    }




    // === Мастер-код (без изменений) ===

    public function generateAccessToken()
    {
        $this->access_token = Str::random(64);
        $this->save();
        return $this->access_token;
    }

    public function getAccessUrl()
    {
        return url("/workspace/{$this->uuid}?token={$this->access_token}");
    }

    public function isValidToken($token)
    {
        return $this->access_token === $token;
    }

    public function getHasMasterCodeAttribute(): bool
    {
        return !empty($this->master_code_hash);
    }

    public function getIsMasterRateLimitedAttribute(): bool
    {
        if (!$this->master_code_locked_until) {
            return false;
        }
        return $this->master_code_locked_until->isFuture();
    }

    public function getMasterLockedUntilAttribute()
    {
        if (!$this->master_code_locked_until) {
            return null;
        }
        return $this->master_code_locked_until->isFuture()
            ? $this->master_code_locked_until->toIso8601String()
            : null;
    }

    public function getMasterAttemptsLeftAttribute(): int
    {
        return max(0, 5 - ($this->master_code_attempts ?? 0));
    }

    public function setMasterCode(string $code): void
    {
        $this->update([
            'master_code_hash' => bcrypt($code),
            'master_code_attempts' => 0,
            'master_code_locked_until' => null,
        ]);
    }

    public function clearMasterCode(): void
    {
        $this->update([
            'master_code_hash' => null,
            'master_code_attempts' => 0,
            'master_code_locked_until' => null,
        ]);
    }

    public function verifyMasterCode(string $code): array
    {
        if ($this->is_master_rate_limited) {
            return [
                'success' => false,
                'locked' => true,
                'message' => 'Ввод заблокирован',
                'locked_until' => $this->master_locked_until,
                'retry_after_seconds' => now()->diffInSeconds($this->master_code_locked_until, false),
                'attempts_left' => 0,
            ];
        }

        if (Hash::check($code, $this->master_code_hash)) {
            $this->update([
                'master_code_attempts' => 0,
                'master_code_locked_until' => null,
            ]);
            return ['success' => true, 'message' => 'Код верный'];
        }

        $attempts = ($this->master_code_attempts ?? 0) + 1;
        $updateData = ['master_code_attempts' => $attempts];

        if ($attempts >= 5) {
            $lockedUntil = now()->addHour();
            $updateData['master_code_locked_until'] = $lockedUntil;
            $this->update($updateData);

            return [
                'success' => false,
                'locked' => true,
                'message' => 'Превышено количество попыток. Повторите через 1 час.',
                'attempts_left' => 0,
                'locked_until' => $lockedUntil->toIso8601String(),
                'retry_after_seconds' => 3600,
            ];
        }

        $this->update($updateData);

        return [
            'success' => false,
            'locked' => false,
            'message' => 'Неверный код',
            'attempts_left' => 5 - $attempts,
        ];
    }

    public function getLinkedWorkspaces()
    {
        $linkedUuids = $this->getSetting('linked_workspaces', []);

        if (empty($linkedUuids)) {
            return collect();
        }

        return Workspace::whereIn('uuid', $linkedUuids)
            ->where('id', '!=', $this->id)
            ->withCount(['products', 'categories', 'collections']) // ✅ Добавили счётчики
            ->orderBy('name')
            ->get()
            ->map(fn($w) => [
                'id' => $w->id,
                'uuid' => $w->uuid,
                'name' => $w->name,
                'label' => $w->label,
                'color' => $w->color,
                'logo_url' => $w->logo_url,
                'initials' => $w->initials,
                'stats' => [ // ✅ Добавили статистику
                    'products_count' => $w->products_count,
                    'categories_count' => $w->categories_count,
                    'collections_count' => $w->collections_count,
                ],
            ]);
    }

    /**
     * Добавить доску в связанные (двусторонняя связь)
     */
    public function linkWorkspace(string $uuid): void
    {
        if ($uuid === $this->uuid) return;

        $target = Workspace::where('uuid', $uuid)->first();
        if (!$target) return;

        // Добавляем в текущую
        $current = $this->getSetting('linked_workspaces', []);
        if (!in_array($uuid, $current)) {
            $current[] = $uuid;
            $this->setSetting('linked_workspaces', $current);
        }

        // Добавляем в целевую (двусторонняя связь)
        $targetLinked = $target->getSetting('linked_workspaces', []);
        if (!in_array($this->uuid, $targetLinked)) {
            $targetLinked[] = $this->uuid;
            $target->setSetting('linked_workspaces', $targetLinked);
        }
    }

    /**
     * Удалить доску из связанных (двусторонняя связь)
     */
    public function unlinkWorkspace(string $uuid): void
    {
        $target = Workspace::where('uuid', $uuid)->first();
        if (!$target) return;

        // Удаляем из текущей
        $current = $this->getSetting('linked_workspaces', []);
        $current = array_values(array_filter($current, fn($u) => $u !== $uuid));
        $this->setSetting('linked_workspaces', $current);

        // Удаляем из целевой
        $targetLinked = $target->getSetting('linked_workspaces', []);
        $targetLinked = array_values(array_filter($targetLinked, fn($u) => $u !== $this->uuid));
        $target->setSetting('linked_workspaces', $targetLinked);
    }

    /**
     * Проверить связана ли доска
     */
    public function isLinkedTo(string $uuid): bool
    {
        $linked = $this->getSetting('linked_workspaces', []);
        return in_array($uuid, $linked);
    }

    // В модели Workspace.php



    public function getDescriptionAttribute(): ?string
    {
        return $this->attributes['description'] ?? null;
    }

    public function getUrlAttribute(): ?string
    {
        return $this->attributes['url'] ?? null;
    }
}
