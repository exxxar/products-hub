<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MenuDefaultImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;

class MenuDefaultImageController extends Controller
{
    public function index()
    {
        $workspace = App::make('workspace');

        $images = $workspace->menuDefaultImages()
            ->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($images);
    }

    public function store(Request $request)
    {
        $workspace = App::make('workspace');

        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            'name' => 'nullable|string|max:255',
        ]);

        $path = $request->file('image')->store(
            'menu-default-images/' . $workspace->id,
            'public'
        );

        $image = MenuDefaultImage::create([
            'workspace_id' => $workspace->id,
            'name' => $request->name ?? $request->file('image')->getClientOriginalName(),
            'path' => $path,
            'sort_order' => $workspace->menuDefaultImages()->count(),
        ]);

        return response()->json($image, 201);
    }

    public function destroy(MenuDefaultImage $image)
    {
        $workspace = App::make('workspace');

        if ($image->workspace_id !== $workspace->id) {
            abort(403);
        }

        // Если эта картинка используется как дефолтная в меню — сбрасываем
        if ($workspace->menuConfig?->default_image_id === $image->id) {
            $workspace->menuConfig->update(['default_image_id' => null]);
        }

        $image->delete();

        return response()->json(['message' => 'Изображение удалено']);
    }
}
