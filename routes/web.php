<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ManifestController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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
