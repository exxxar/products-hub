<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;

class MasterCodeController extends Controller
{

    public function set(Request $request)
    {
        $workspace = App::make('workspace');

        if ($workspace->has_master_code) {
            return response()->json([
                'error' => 'Мастер-код уже установлен. Используйте метод change для смены.'
            ], 400);
        }

        $validated = $request->validate([
            'code' => 'required|string|min:4|max:32',
            'confirm_code' => 'required|string|same:code',
        ], [
            'code.min' => 'Код должен содержать минимум 4 символа',
            'code.max' => 'Код должен содержать максимум 32 символа',
            'confirm_code.same' => 'Коды не совпадают',
        ]);


        $workspace->setMasterCode($validated['code']);

        ActivityLogger::log(
            action: 'locked',
            entityType: 'master_code',
            description: 'Установлен мастер-код. Редактирование защищено.'
        );


        return response()->json([
            'message' => 'Мастер-код установлен',
            'has_code' => true,
            'unlock_token' => $this->generateUnlockToken($workspace),
        ]);
    }

    public function verify(Request $request)
    {
        $workspace = App::make('workspace');

        if (!$workspace->has_master_code) {
            return response()->json(['error' => 'Мастер-код не установлен'], 400);
        }

        $validated = $request->validate(['code' => 'required|string']);

        $result = $workspace->verifyMasterCode($validated['code']);

        ActivityLogger::log(
            action: $result['success'] ? 'login_success' : 'login_failed',
            entityType: 'master_code',
            description: $result['success']
                ? 'Успешная разблокировка мастер-кодом'
                : 'Неверный мастер-код',
            metadata: [
                'ip' => $request->ip(),
                'attempts_left' => $result['attempts_left'] ?? null,
            ]
        );
        
        if ($result['success']) {
            $result['unlock_token'] = $this->generateUnlockToken($workspace);
        }


        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function change(Request $request)
    {
        $workspace = App::make('workspace');

        $validated = $request->validate([
            'current_code' => 'required|string',
            'new_code' => 'required|string|min:4|max:32',
            'confirm_new_code' => 'required|string|same:new_code',
        ]);

        if (!Hash::check($validated['current_code'], $workspace->master_code_hash)) {
            $result = $workspace->verifyMasterCode($validated['current_code']);
            return response()->json($result, 422);
        }

        $workspace->setMasterCode($validated['new_code']);

        return response()->json([
            'message' => 'Мастер-код изменён',
            'has_code' => true,
        ]);
    }

    public function reset(Request $request)
    {
        $workspace = App::make('workspace');

        $validated = $request->validate(['code' => 'required|string']);

        if (!Hash::check($validated['code'], $workspace->master_code_hash)) {
            $result = $workspace->verifyMasterCode($validated['code']);
            return response()->json($result, 422);
        }

        $workspace->clearMasterCode();

        return response()->json([
            'message' => 'Мастер-код удалён',
            'has_code' => false,
        ]);
    }

    protected function generateUnlockToken($workspace): string
    {
        return hash('sha256', $workspace->id . '|' . $workspace->master_code_hash . '|unlocked');
    }
}
