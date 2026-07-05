<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;

class WorkspaceVisualController extends Controller
{
    /**
     * Обновить визуальные настройки (label, color)
     */
    public function updateVisual(Request $request)
    {
        $workspace = App::make('workspace');

        $validated = $request->validate([
            'label' => 'nullable|string|max:3',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        // ✅ Обновляем через settings
        $workspace->setSettings([
            'visual.label' => $validated['label'] ?? null,
            'visual.color' => $validated['color'] ?? '#0d6efd',
        ]);

        return response()->json([
            'label' => $workspace->label,
            'color' => $workspace->color,
            'initials' => $workspace->initials,
        ]);
    }

    /**
     * Загрузка логотипа
     */
    public function uploadLogo(Request $request, $worksapceUuid)
    {
        $workspace = Workspace::query()
            ->where("uuid", $worksapceUuid)
            ->firstOrFail();

        $request->validate(['logo' => 'required|image|mimes:jpeg,png,jpg,svg|max:1024']);

        // Удаляем старый логотип
        $oldPath = $workspace->logo_path;
        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        $path = $request->file('logo')->store('workspace-logos/' . $workspace->id, 'public');

        $workspace->logo_path = $path;
        $workspace->save();

        return response()->json([
            'logo_url' => Storage::url($path),
            'logo_path' => $path,
        ]);
    }

    /**
     * Удаление логотипа
     */
    public function removeLogo()
    {
        $workspace = App::make('workspace');

        $oldPath = $workspace->logo_path;
        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        $workspace->setSetting('visual.logo_path', null);

        return response()->json(['success' => true]);
    }

    /**
     * Переключение группировки
     */
    public function toggleGroups(Request $request)
    {
        $workspace = App::make('workspace');

        $validated = $request->validate([
            'enabled' => 'required|boolean',
        ]);

        $workspace->setSetting('groups.enabled', $validated['enabled']);

        return response()->json([
            'groups_enabled' => $workspace->isGroupsEnabled(),
        ]);
    }
}
