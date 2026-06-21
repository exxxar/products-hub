<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'board_id',
        'token',
        'abilities',
        'is_active',
    ];

    protected $casts = [
        'board_id'=>"integer",
        'abilities'=>"array",
        'is_active'=>"boolean",
    ];
}
