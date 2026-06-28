<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MenuDefaultImage extends Model
{
    protected $fillable = [
        'workspace_id',
        'name',
        'path',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    protected $appends = ["url", "full_path"];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function getUrlAttribute()
    {
        return Storage::url($this->path);
    }

    public function getFullPathAttribute()
    {
        return public_path('storage/' . $this->path);
    }

    public function delete()
    {
        // Удаляем файл при удалении записи
        if ($this->path) {
            Storage::disk('public')->delete($this->path);
        }

        return parent::delete();
    }
}
