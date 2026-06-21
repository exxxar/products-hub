<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IngredientGroup extends Model
{
    protected $fillable = [
        'workspace_id', 'name', 'selection_type', 'min', 'max'
    ];

    public function workspace() {
        return $this->belongsTo(Workspace::class);
    }

    public function ingredients() {
        return $this->hasMany(Ingredient::class, 'group_id');
    }
}
