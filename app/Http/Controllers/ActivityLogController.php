<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $workspace = App::make('workspace');

        $query = ActivityLog::where('workspace_id', $workspace->id)
            ->orderBy('created_at', 'desc');

        // Фильтр по типу сущности
        if ($entityType = $request->input('entity_type')) {
            $query->ofType($entityType);
        }

        // Фильтр по действию
        if ($action = $request->input('action')) {
            $query->withAction($action);
        }

        // Фильтр по периоду
        if ($days = $request->input('days')) {
            $query->recent((int) $days);
        }

        // Поиск по описанию
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('entity_name', 'like', "%{$search}%");
            });
        }

        $perPage = min((int) $request->input('per_page', 30), 100);
        $logs = $query->paginate($perPage);

        // Добавляем computed атрибуты
        $logs->getCollection()->transform(function ($log) {
            return [
                'id' => $log->id,
                'action' => $log->action,
                'action_label' => $log->action_label,
                'entity_type' => $log->entity_type,
                'entity_type_label' => $log->entity_type_label,
                'entity_id' => $log->entity_id,
                'entity_name' => $log->entity_name,
                'description' => $log->description,
                'metadata' => $log->metadata,
                'icon' => $log->icon,
                'color' => $log->color,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at->toIso8601String(),
                'created_at_human' => $log->created_at->diffForHumans(),
            ];
        });

        return response()->json($logs);
    }

    /**
     * Статистика для дашборда
     */
    public function stats()
    {
        $workspace = App::make('workspace');

        $stats = [
            'total' => ActivityLog::where('workspace_id', $workspace->id)->count(),
            'today' => ActivityLog::where('workspace_id', $workspace->id)
                ->whereDate('created_at', today())->count(),
            'by_type' => ActivityLog::where('workspace_id', $workspace->id)
                ->selectRaw('entity_type, COUNT(*) as count')
                ->groupBy('entity_type')
                ->pluck('count', 'entity_type'),
            'by_action' => ActivityLog::where('workspace_id', $workspace->id)
                ->selectRaw('action, COUNT(*) as count')
                ->groupBy('action')
                ->pluck('count', 'action'),
            'recent_failed_logins' => ActivityLog::where('workspace_id', $workspace->id)
                ->where('action', 'login_failed')
                ->recent(7)
                ->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Очистка старых логов
     */
    public function clear(Request $request)
    {
        $workspace = App::make('workspace');

        $days = (int) $request->input('older_than_days', 30);

        $deleted = ActivityLog::where('workspace_id', $workspace->id)
            ->where('created_at', '<', now()->subDays($days))
            ->delete();

        return response()->json([
            'message' => "Удалено {$deleted} записей старше {$days} дней",
            'deleted' => $deleted,
        ]);
    }
}
