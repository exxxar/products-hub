<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class WorkspaceTokenController extends Controller
{
    /**
     * Получить текущий токен и ссылку
     */
    public function show()
    {
        $workspace = App::make('workspace');

        return response()->json([
            'access_token' => $workspace->access_token,
            'access_url' => $workspace->getAccessUrl()
        ]);
    }

    /**
     * Перегенерировать токен
     */
    public function regenerate()
    {
        $workspace = App::make('workspace');

        $newToken = $workspace->generateAccessToken();

        return response()->json([
            'access_token' => $newToken,
            'access_url' => $workspace->getAccessUrl(),
            'message' => 'Ссылка доступа обновлена'
        ]);
    }

    /**
     * Создать первую сессию (если токена еще нет)
     */
    public function initialize()
    {

        $workspace = App::make('workspace');

        if (!$workspace->access_token) {
            $workspace->generateAccessToken();
        }

        return response()->json([
            'access_token' => $workspace->access_token,
            'access_url' => $workspace->getAccessUrl()
        ]);
    }
}
