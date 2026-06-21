<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function save(Request $request){

        $data = $request->validate([
            'url' => 'nullable|string',
            'vk_shop_links' => 'nullable|string',
            'iiko.api_login' => 'nullable|string',
            'iiko.organization_id' => 'nullable|string',
            'iiko.terminal_group_id' => 'nullable|string',
            'frontpad.secret' => 'nullable|string',
        ]);

        // нормализация структуры (чтобы всегда были ключи)
        $settings = [
            'url' => $data['url'] ?? '',
            'vk_shop_links' => $data['vk_shop_links'] ?? '',
            'iiko' => [
                'api_login' => data_get($data, 'iiko.api_login', ''),
                'organization_id' => data_get($data, 'iiko.organization_id', ''),
                'terminal_group_id' => data_get($data, 'iiko.terminal_group_id', ''),
            ],
            'frontpad' => [
                'secret' => data_get($data, 'frontpad.secret', ''),
            ],
        ];

        $workspace = $request->workspace;
        $workspace->settings = $settings;
        $workspace->save();

        return response()->json([
            'success' => true,
            'settings' => $workspace->settings
        ]);
    }
}
