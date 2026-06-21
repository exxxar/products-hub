<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Workspace;
use Illuminate\Support\Str;

class WorkspaceSeeder extends Seeder
{
    public function run()
    {
        Workspace::create([
            'uuid' => Str::uuid(),
            'password_hash' => null,
            'settings' => [
                'viewMode' => 'grid'
            ]
        ]);
    }
}
