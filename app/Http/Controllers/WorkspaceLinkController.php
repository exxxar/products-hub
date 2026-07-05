<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class WorkspaceLinkController extends Controller
{

    /**
     * Поиск доски по UUID (для добавления по ссылке)
     */
    public function findByUuid(Request $request)
    {
        $workspace = App::make('workspace');

        $validated = $request->validate([
            'uuid' => 'required|string|uuid',
        ]);

        $targetWorkspace = Workspace::where('uuid', $validated['uuid'])->first();

        if (!$targetWorkspace) {
            return response()->json([
                'success' => false,
                'error' => 'Доска не найдена',
            ], 404);
        }

        // Проверяем не связана ли уже
        $isAlreadyLinked = $workspace->isLinkedTo($validated['uuid']);

        return response()->json([
            'success' => true,
            'workspace' => [
                'id' => $targetWorkspace->id,
                'uuid' => $targetWorkspace->uuid,
                'name' => $targetWorkspace->name,
                'label' => $targetWorkspace->label,
                'color' => $targetWorkspace->color,
                'logo_url' => $targetWorkspace->logo_url,
                'initials' => $targetWorkspace->initials,
            ],
            'is_already_linked' => $isAlreadyLinked,
        ]);
    }

    /**
     * Получить все связанные доски
     */
    public function index()
    {
        $workspace = App::make('workspace');

        $linked = $workspace->getLinkedWorkspaces();

        return response()->json([
            'current' => [
                'id' => $workspace->id,
                'uuid' => $workspace->uuid,
                'name' => $workspace->name,
                'label' => $workspace->label,
                'color' => $workspace->color,
                'logo_url' => $workspace->logo_url,
                'initials' => $workspace->initials,
            ],
            'linked' => $linked,
        ]);
    }

    /**
     * Добавить доску в связанные
     */
    public function link(Request $request)
    {
        $workspace = App::make('workspace');

        $validated = $request->validate([
            'uuid' => 'required|string|exists:workspaces,uuid',
        ]);

        $workspace->linkWorkspace($validated['uuid']);

        return response()->json([
            'success' => true,
            'linked' => $workspace->getLinkedWorkspaces(),
        ]);
    }

    /**
     * Удалить доску из связанных
     */
    public function unlink(Request $request)
    {
        $workspace = App::make('workspace');

        $validated = $request->validate([
            'uuid' => 'required|string|exists:workspaces,uuid',
        ]);

        $workspace->unlinkWorkspace($validated['uuid']);

        return response()->json([
            'success' => true,
            'linked' => $workspace->getLinkedWorkspaces(),
        ]);
    }

    /**
     * Создать новую доску и сразу связать
     */
    public function createAndLink(Request $request)
    {
        $workspace = App::make('workspace');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'label' => 'nullable|string|max:3',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        // Создаём новую доску
        $newWorkspace = Workspace::create([
            'uuid' => Str::uuid()->toString(),
            'name' => $validated['name'],
            'settings' => [
                'visual' => [
                    'label' => $validated['label'] ?? null,
                    'color' => $validated['color'] ?? '#0d6efd',
                ],
            ],
        ]);

        // Связываем
        $workspace->linkWorkspace($newWorkspace->uuid);

        return response()->json([
            'success' => true,
            'workspace' => [
                'id' => $newWorkspace->id,
                'uuid' => $newWorkspace->uuid,
                'name' => $newWorkspace->name,
                'label' => $newWorkspace->label,
                'color' => $newWorkspace->color,
                'initials' => $newWorkspace->initials,
            ],
            'linked' => $workspace->getLinkedWorkspaces(),
        ], 201);
    }

    /**
     * Список всех досок (для добавления)
     */
    public function allWorkspaces()
    {
        $workspace = App::make('workspace');
        $linkedUuids = $workspace->getSetting('linked_workspaces', []);

        $workspaces = Workspace::where('id', '!=', $workspace->id)
            ->orderBy('name')
            ->get()
            ->map(fn($w) => [
                'id' => $w->id,
                'uuid' => $w->uuid,
                'name' => $w->name,
                'label' => $w->label,
                'color' => $w->color,
                'logo_url' => $w->logo_url,
                'initials' => $w->initials,
                'is_linked' => in_array($w->uuid, $linkedUuids),
            ]);

        return response()->json($workspaces);
    }
}
