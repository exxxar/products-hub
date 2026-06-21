<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Workspace;
use Illuminate\Support\Facades\App;

class WorkspaceAuth
{
    public function handle(Request $request, Closure $next)
    {
        $uuid = $request->route('uuid');

        if ($uuid) {
            $workspace = Workspace::where('uuid', $uuid)->firstOrFail();

            // Приоритет: header > query > cookie
            $token = $request->header('X-Workspace-Token')
                ?? $request->query('token')
                ?? $request->cookie('workspace_token_' . $uuid);

            // Если токена нет вообще - пропускаем (для публичных маршрутов)
            if (!$token) {
                // Можно разрешить доступ без токена для некоторых маршрутов
                // или вернуть 403
                if ($request->is('api/*')) {
                    abort(403, 'Требуется токен доступа');
                }

                App::instance('workspace', $workspace);
                $request->workspace = $workspace;
                return $next($request);
            }

            // Проверяем валидность токена
            if (!$workspace->isValidToken($token)) {
                abort(403, 'Недействительная ссылка доступа');
            }

            // Устанавливаем cookie если токен пришёл из query
            if ($request->query('token')) {
                cookie()->queue(
                    'workspace_token_' . $uuid,
                    $token,
                    60 * 24 * 30 // 30 дней
                );
            }

            // Делаем workspace доступным
            App::instance('workspace', $workspace);
            $request->workspace = $workspace;
        }

        return $next($request);
    }
}
