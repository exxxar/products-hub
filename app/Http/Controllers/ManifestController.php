<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

class ManifestController extends Controller
{
    public function show(Request $request) {

        // Берём workspace_uuid из сессии или параметров
        $workspaceUuid =  $request->query('workspace_uuid');

        $manifest = [
            "id" => $workspaceUuid ?? Str::uuid(),
            "name" => "Товары",
            "short_name" => "Товары",
            "start_url" => $workspaceUuid
                ? "/workspace/$workspaceUuid?source=pwa"
                : "/?source=pwa",
            "scope" =>  $workspaceUuid
                ? "/workspace/"
                : "/",
            "display" => "standalone",
            "background_color" => "#ffffff",
            "theme_color" => "#1976d2",
            "orientation" => "portrait",
            "icons" => [
                [
                    "src" => "/icons/icon-192x192.png",
                    "sizes" => "192x192",
                    "type" => "image/png"
                ],
                [
                    "src" => "/icons/icon-512x512.png",
                    "sizes" => "512x512",
                    "type" => "image/png"
                ],
                [
                    "src" => "/icons/icon-192x192.png",
                    "sizes" => "192x192",
                    "type" => "image/png",
                    "purpose" => "maskable"
                ],
                [
                    "src" => "/icons/icon-512x512.png",
                    "sizes" => "512x512",
                    "type" => "image/png",
                    "purpose" => "maskable"
                ]
            ],
            "screenshots" => [
                [
                    "src" => "/screenshots/workspace-wide.png",
                    "sizes" => "1280x720",
                    "type" => "image/png",
                    "form_factor" => "wide"
                ],
                [
                    "src" => "/screenshots/workspace-mobile.png",
                    "sizes" => "375x667",
                    "type" => "image/png"
                ]
            ],
            "description" => "Управляй своими товарами.",
            "categories" => ["productivity", "organization", "project management"],
            "lang" => "ru"
        ];

        return response()->json($manifest)
            ->header('Cache-Control', 'public, max-age=3600') // Кэшируем на 1 час
            ->header('Content-Type', 'application/manifest+json');
    }


}
