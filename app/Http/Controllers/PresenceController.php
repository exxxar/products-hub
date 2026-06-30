<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\WorkspacePresence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class PresenceController extends Controller
{
    /**
     * Heartbeat — клиент шлёт каждые 15 сек
     */
    public function heartbeat(Request $request)
    {
        $workspace = App::make('workspace');

        $browserId = $request->input('browser_id');

        if (!$browserId) {
            // Fallback — если браузер_id не пришёл
            $browserId = 'ip_' . md5($request->ip() . $request->userAgent());
        }

        // ✅ Удаляем старые записи с тем же browser_id (могли остаться)
        WorkspacePresence::where('workspace_id', $workspace->id)
            ->where('user_key', $browserId)
            ->delete();

        $ipAddress = $request->ip();

        WorkspacePresence::updateOrCreate(
            [
                'workspace_id' => $workspace->id,
                'user_key' => $browserId,
            ],
            [
                'user_name' => $request->user()?->name ?? 'Гость',
                'last_seen' => now(),
                'ip_address' => $ipAddress,
                'user_agent' => $request->userAgent(),
            ]
        );

        // ✅ Чистим мёртвые записи при каждом heartbeat
        WorkspacePresence::where('last_seen', '<', now()->subMinutes(2))
            ->delete();

        $onlineCount = WorkspacePresence::where('workspace_id', $workspace->id)
            ->where('last_seen', '>=', now()->subSeconds(60))
            ->count();

        return response()->json([
            'online_count' => $onlineCount,
        ]);
    }

    /**
     * Список онлайн-пользователей
     */
    public function users(Request $request)
    {
        $workspace = App::make('workspace');

        $currentUserKey = $request->input('browser_id') ?? 'ip_' . md5(request()->ip() . request()->userAgent());

        $users = WorkspacePresence::where('workspace_id', $workspace->id)
            ->where('last_seen', '>=', now()->subSeconds(60))
            ->orderByDesc('last_seen')
            ->get()
            ->map(fn($p) => [
                'key' => $p->user_key,
                'name' => $p->user_name,
                'ip_address' => $p->ip_address,
                'user_agent' => $p->user_agent,
                'device_type' => $p->device_type,
                'browser' => $p->browser,
                'last_seen' => $p->last_seen->toIso8601String(),
                'idle_seconds' => $p->last_seen->diffInSeconds(now()),
                'is_you' => $p->user_key === $currentUserKey,
            ]);

        return response()->json([
            'count' => $users->count(),
            'users' => $users,
        ]);
    }

    /**
     * Leave — при закрытии вкладки
     */
    public function leave()
    {
        $workspace = App::make('workspace');
        $browserId = request()->input('browser_id');

        WorkspacePresence::where('workspace_id', $workspace->id)
            ->where('user_key', $browserId)
            ->delete();

        return response()->json(['success' => true]);
    }
}
