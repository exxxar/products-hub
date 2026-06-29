<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class Workspace extends Model
{
    protected $fillable = [
        'uuid', 'password_hash',  'settings', 'access_token',

        'master_code_hash',
        'master_code_attempts',
        'master_code_locked_until'
    ];

    protected $casts = [
        'settings' => 'array',
        'master_code_locked_until' => 'datetime',
    ];

// ✅ ВАЖНО: скрываем хэш от фронтенда
    protected $hidden = [
        'master_code_hash',
    ];

    // ✅ Добавляем виртуальные поля в ответ
    protected $appends = [
        'has_master_code',
        'is_master_rate_limited',
        'master_locked_until',
        'master_attempts_left',
    ];

    public function menuConfig()
    {
        return $this->hasOne(MenuConfig::class);
    }

    // В Workspace.php добавить:
    public function menuDefaultImages()
    {
        return $this->hasMany(MenuDefaultImage::class);
    }

    public function webhooks()
    {
        return $this->hasMany(Webhook::class);
    }


    public function products() {
        return $this->hasMany(Product::class);
    }

    public function categories() {
        return $this->hasMany(Category::class);
    }

    public function collections() {
        return $this->hasMany(Collection::class);
    }

    public function ingredientGroups() {
        return $this->hasMany(IngredientGroup::class);
    }

    public function ingredients() {
        return $this->hasMany(Ingredient::class);
    }

    /**
     * Генерация нового токена доступа
     */
    public function generateAccessToken()
    {
        $this->access_token = Str::random(64);
        $this->save();

        return $this->access_token;
    }

    /**
     * Получить полную ссылку для доступа
     */
    public function getAccessUrl()
    {
        return url("/w/{$this->uuid}?token={$this->access_token}");
    }

    /**
     * Проверка валидности токена
     */
    public function isValidToken($token)
    {
        return $this->access_token === $token;
    }

    /**
     * Установлен ли мастер-код
     */
    public function getHasMasterCodeAttribute(): bool
    {
        return !empty($this->master_code_hash);
    }

    /**
     * ✅ Переименовано: заблокирован ли ввод из-за rate limit (5 неудачных попыток)
     */
    public function getIsMasterRateLimitedAttribute(): bool
    {
        if (!$this->master_code_locked_until) {
            return false;
        }
        return $this->master_code_locked_until->isFuture();
    }

    /**
     * Дата/время до которой действует блокировка
     */
    public function getMasterLockedUntilAttribute()
    {
        if (!$this->master_code_locked_until) {
            return null;
        }
        // Возвращаем только если блокировка ещё активна
        return $this->master_code_locked_until->isFuture()
            ? $this->master_code_locked_until->toIso8601String()
            : null;
    }

    /**
     * Сколько попыток осталось до блокировки
     */
    public function getMasterAttemptsLeftAttribute(): int
    {
        return max(0, 5 - ($this->master_code_attempts ?? 0));
    }

    /**
     * Установить мастер-код
     */
    public function setMasterCode(string $code): void
    {


        $this->update([
            'master_code_hash' => bcrypt($code),
            'master_code_attempts' => 0,
            'master_code_locked_until' => null,
        ]);

    }

    /**
     * Сбросить мастер-код
     */
    public function clearMasterCode(): void
    {
        $this->update([
            'master_code_hash' => null,
            'master_code_attempts' => 0,
            'master_code_locked_until' => null,
        ]);
    }

    /**
     * Проверить мастер-код
     */
    public function verifyMasterCode(string $code): array
    {
        // Проверка rate limit
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

        // Успешная проверка
        if (Hash::check($code, $this->master_code_hash)) {
            $this->update([
                'master_code_attempts' => 0,
                'master_code_locked_until' => null,
            ]);
            return ['success' => true, 'message' => 'Код верный'];
        }

        // Неверный код — увеличиваем счётчик
        $attempts = ($this->master_code_attempts ?? 0) + 1;
        $updateData = ['master_code_attempts' => $attempts];

        // Блокировка на 1 час после 5 попыток
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
}
