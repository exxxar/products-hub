<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use App\Models\WorkspaceGroup;
use App\Models\Webhook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class WorkspaceGroupController extends Controller
{
    public function index()
    {
        $workspace = App::make('workspace');
        $groups = $workspace->groups()->with('workspaces:id,name,uuid,color,label')->get();


        return response()->json($groups);
    }

    public function updateWorkspaces(Request $request, WorkspaceGroup $group)
    {
        $workspace = App::make('workspace');

        // Проверка прав
        if ($group->workspaces()->where('workspaces.id', $workspace->id)->doesntExist()) {
            abort(403);
        }

        $validated = $request->validate([
            'workspace_ids' => 'required|array|min:2',
            'workspace_ids.*' => 'integer'
        ]);

        // Синхронизируем состав группы
        $group->syncWorkspaces($validated['workspace_ids']);

        return response()->json($group->load('workspaces:id,name,uuid,color,label'));
    }

    public function store(Request $request)
    {
        $workspace = App::make('workspace');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string',
            'workspace_ids' => 'required|array|min:2',
            'workspace_ids.*' => 'integer'
        ]);

        $group = WorkspaceGroup::create([
            'name' => $validated['name'],
            'color' => $validated['color'] ?? '#0d6efd',
        ]);



        $group->addWorkspaces($validated['workspace_ids']);
        // Добавляем и текущий workspace, если его нет в списке
        if (!in_array($workspace->id, $validated['workspace_ids'])) {
            $group->addWorkspaces([$workspace->id]);
        }

        return response()->json($group->load('workspaces'), 201);
    }

    /**
     * Обновление группы и её состава
     */
    public function update(Request $request,$workspaceUuid, WorkspaceGroup $group)
    {
        $workspace = App::make('workspace');

        // 1. Проверка прав: текущий workspace должен быть участником этой группы
        if ($group->workspaces()->where('workspaces.id', $workspace->id)->doesntExist()) {
            abort(403, 'У вас нет прав на редактирование этой группы');
        }

        // 2. Валидация данных (аналогично store)
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string',
            'workspace_ids' => 'required|array|min:2',
            'workspace_ids.*' => 'integer'
        ]);

        // 3. Обновление основных полей группы
        $group->update([
            'name' => $validated['name'],
            'color' => $validated['color'] ?? '#0d6efd',
        ]);

        // 4. Синхронизация состава группы
        $workspaceIds = $validated['workspace_ids'];

        // Гарантируем, что текущий workspace остается в группе (как в методе store)
        if (!in_array($workspace->id, $workspaceIds)) {
            $workspaceIds[] = $workspace->id;
        }

        // Используем sync для обновления связей (добавит новые, удалит лишние, обновит sort_order)
        $group->workspaces()->sync(
            collect($workspaceIds)->mapWithKeys(function ($id, $index) {
                return [$id => ['sort_order' => $index]];
            })->toArray()
        );

        // 5. Возвращаем обновленную группу с загруженными связями
        return response()->json($group->load('workspaces'));
    }
    /**
     * Массовое обновление/создание вебхуков для досок в группе
     */
    public function updateWebhooks(Request $request, $workspaceUuid, WorkspaceGroup $group)
    {
        $currentWorkspace = App::make('workspace');

        // Проверка прав: текущий пользователь должен состоять в этой группе
        if ($group->workspaces()->where('workspaces.id', $currentWorkspace->id)->doesntExist()) {
            abort(403, 'У вас нет прав на управление вебхуками этой группы');
        }

        $validated = $request->validate([
            'webhooks' => 'required|array',
            'webhooks.*.workspace_id' => 'required|integer',
            'webhooks.*.id' => 'nullable|integer', // ID существующего вебхука
            'webhooks.*.name' => 'nullable|string|max:255',
            'webhooks.*.url' => 'required|url',
            'webhooks.*.sync_on_update' => 'boolean',
        ]);

        $results = [];

        foreach ($validated['webhooks'] as $data) {
            // Проверяем, что workspace действительно входит в эту группу
            $ws = $group->workspaces()->find($data['workspace_id']);
            if (!$ws) {
                continue; // Пропускаем, если workspace не из этой группы (защита)
            }

            $webhookPayload = [
                'name' => $data['name'] ?? 'Групповой вебхук',
                'url' => $data['url'],
                'sync_on_update' => $data['sync_on_update'] ?? true,
            ];

            if (!empty($data['id'])) {
                // Пытаемся найти существующий вебхук
                $webhook = Webhook::where('id', $data['id'])
                    ->where('workspace_id', $ws->id)
                    ->first();

                if ($webhook) {
                    // Обновляем существующий
                    $webhook->update($webhookPayload);
                    $results[] = [
                        'workspace_id' => $ws->id,
                        'webhook_id' => $webhook->id,
                        'action' => 'updated'
                    ];
                } else {
                    // Если ID не совпал с workspace, создаем новый (защита от подмены ID)
                    $newWebhook = $ws->webhooks()->create($webhookPayload);
                    $results[] = [
                        'workspace_id' => $ws->id,
                        'webhook_id' => $newWebhook->id,
                        'action' => 'created'
                    ];
                }
            } else {
                // Создаем новый вебхук
                $newWebhook = $ws->webhooks()->create($webhookPayload);
                $results[] = [
                    'workspace_id' => $ws->id,
                    'webhook_id' => $newWebhook->id,
                    'action' => 'created'
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Вебхуки успешно обновлены',
            'results' => $results
        ]);
    }
    /**
     * Пакетная синхронизация
     */
    /**
     * Пакетная синхронизация досок в группе
     */
    public function sync(Request $request, WorkspaceGroup $group)
    {
        $currentWorkspace = App::make('workspace');

        // Проверка прав: текущий пользователь должен состоять в этой группе
        if ($group->workspaces()->where('workspaces.id', $currentWorkspace->id)->doesntExist()) {
            abort(403, 'У вас нет прав на синхронизацию этой группы');
        }

        $validated = $request->validate([
            'workspace_ids' => 'required|array',
            'workspace_ids.*' => 'integer'
        ]);

        $results = [];

        // Получаем только те доски, которые входят в группу И выбраны пользователем
        $targetWorkspaces = $group->workspaces()
            ->whereIn('workspaces.id', $validated['workspace_ids'])
            ->get();

        foreach ($targetWorkspaces as $ws) {
            // Ищем вебхук, который мы настроили через модалку группы
            // Если его нет, берем первый активный вебхук этой доски
            $webhook = $ws->webhooks()
                ->where(function($q) {
                    $q->where('name', 'Групповой вебхук')
                        ->orWhere('sync_on_update', true);
                })
                ->whereNotNull('url')
                ->first();

            if (!$webhook) {
                $results[] = [
                    'workspace_id' => $ws->id,
                    'workspace_name' => $ws->name,
                    'success' => false,
                    'products_synced' => 0,
                    'error' => 'Вебхук не настроен или URL пуст',
                ];
                continue;
            }

            // Вызываем РЕАЛЬНЫЙ метод синхронизации
            $syncResult = $webhook->syncProducts();

            $results[] = [
                'workspace_id' => $ws->id,
                'workspace_name' => $ws->name,
                'success' => $syncResult['success'],
                'products_synced' => $syncResult['products_synced'],
                'error' => $syncResult['error'],
            ];
        }

        return response()->json([
            'success' => true,
            'results' => $results
        ]);
    }

    /**
     * Удаление группы
     */
    public function destroy($workspaceUuid, WorkspaceGroup $group)
    {
        $workspace = App::make('workspace');

        // Проверка прав: текущий workspace должен состоять в этой группе
        if ($group->workspaces()->where('workspaces.id', $workspace->id)->doesntExist()) {
            abort(403, 'У вас нет прав на удаление этой группы');
        }

        $groupName = $group->name;

        // Удаляем группу (связи в workspace_group_members удалятся каскадно)
        $group->delete();

        return response()->json([
            'success' => true,
            'message' => "Группа «{$groupName}» удалена",
        ]);
    }

}
