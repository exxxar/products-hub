<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ManifestController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;


use Illuminate\Http\Request;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::any("/webhook-test", function (Request $request){
    // Формируем полный объект для логирования
    $logData = [
        'timestamp' => now()->toDateTimeString(),
        'method' => $request->method(),
        'url' => $request->fullUrl(),
        'path' => $request->path(),
        'ip' => $request->ip(),

        // Заголовки (очищаем от чувствительных данных)
        'headers' => collect($request->headers->all())
            ->map(function ($value) {
                return is_array($value) ? implode(', ', $value) : $value;
            })
            ->toArray(),

        // Query параметры (?foo=bar)
        'query' => $request->query(),

        // Тело запроса (JSON / form-data)
        'body' => $request->all(),

        // Сырой body (если JSON)
        'raw_body' => $request->getContent(),

        // Загруженные файлы
        'files' => collect($request->allFiles())
            ->map(fn($file) => [
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime' => $file->getMimeType(),
            ])
            ->toArray(),
    ];

    // Логируем в отдельный файл webhook-test.log
    Log::channel('single')->info('========== WEBHOOK TEST ==========', $logData);

    // Также логируем в общий лог
    Log::info('Webhook test received', [
        'method' => $request->method(),
        'ip' => $request->ip(),
        'body_count' => count($request->all()),
    ]);

    // Возвращаем 200 OK с зеркальным ответом (удобно для отладки)
    return response()->json([
        'success' => true,
        'message' => 'Webhook received and logged',
        'received_at' => now()->toIso8601String(),
        'echo' => [
            'method' => $request->method(),
            'headers_count' => count($logData['headers']),
            'body_count' => count($logData['body']),
        ]
    ], 200);
});


Route::get('/manifest.json', [ManifestController::class, 'show']);


Route::get('/sw.js', function () {
    $path = public_path('sw.js');

    if (!file_exists($path)) {
        abort(404);
    }

    return response(file_get_contents($path), 200, [
        'Content-Type' => 'application/javascript',
        'Service-Worker-Allowed' => '/pwa/',
        'Cache-Control' => 'no-cache, no-store, must-revalidate', // Важно! Чтобы обновления подхватывались
    ]);
});


Route::any('/workspace/vk-callback',[\App\Http\Controllers\VKProductController::class,"callback"]);

Route::get('/workspace/{uuid}', [\App\Http\Controllers\WorkspaceController::class, 'show'])
    ->name('workspace.show');


Route::get('/create-session', [HomeController::class, 'newSession'])
    ->middleware('throttle:create-sessions');

Route::get('/', [HomeController::class, 'index']);


require __DIR__.'/auth.php';
