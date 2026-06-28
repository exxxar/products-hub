<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Workspace extends Model
{
    protected $fillable = [
        'uuid', 'password_hash',  'settings', 'access_token'
    ];

    protected $casts = [
        'settings' => 'array'
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
}
