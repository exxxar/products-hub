<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    protected $fillable = [
        'workspace_id', 'group_id', 'name', 'image_url', 'is_active'
    ];

    public function workspace() {
        return $this->belongsTo(Workspace::class);
    }

    public function group() {
        return $this->belongsTo(IngredientGroup::class, 'group_id');
    }
}

