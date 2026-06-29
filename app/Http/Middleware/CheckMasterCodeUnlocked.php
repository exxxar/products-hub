<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class CheckMasterCodeUnlocked
{
    public function handle(Request $request, Closure $next)
    {
        $workspace = App::make('workspace');

        // Если мастер-код не установлен — пропускаем
        if (!$workspace->has_master_code) {
            return $next($request);
        }
        // Проверяем токен разблокировки в заголовке
        $unlockToken = $request->header('X-Master-Unlocked');

        if (!$unlockToken) {
            return response()->json([
                'error' => 'Редактирование заблокировано мастер-кодом',
                'locked' => true,
            ], 403);
        }

        // Проверяем валидность токена
        $expectedToken = $this->generateUnlockToken($workspace);

        if (!hash_equals($expectedToken, $unlockToken)) {
            return response()->json([
                'error' => 'Неверный токен разблокировки',
                'locked' => true,
            ], 403);
        }

        return $next($request);
    }

    /**
     * Генерирует токен на основе workspace_id и master_code_hash
     * Токен валиден пока не изменится мастер-код
     */
    protected function generateUnlockToken($workspace): string
    {
        return hash('sha256', $workspace->id . '|' . $workspace->master_code_hash . '|unlocked');
    }
}
