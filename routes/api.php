<?php


use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\MenuDefaultImageController;
use App\Http\Controllers\MenuGeneratorController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WebhookController;

use App\Http\Controllers\WorkspaceTokenController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WorkspaceController;

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\IngredientController;


/*
|--------------------------------------------------------------------------
| Workspace
|--------------------------------------------------------------------------
*/
Route::prefix('workspaces/{uuid}')
    ->group(function () {
// Основная загрузка панели
        Route::get('/', [WorkspaceController::class, 'show']);
    });

Route::prefix('workspaces/{uuid}')
    ->middleware(["workspace.auth"])
    ->group(function () {

        Route::post("/refresh-session", [HomeController::class, "refreshSession"]);
        // ->middleware('throttle:refresh-sessions');

        // Настройки
        Route::prefix('settings')->group(function () {
            Route::post('/', [SettingsController::class, 'save'])->middleware('master.unlocked');
            Route::post('/webhook/test', [SettingsController::class, 'test']);

        });


        // Авторизация по паролю
        Route::post('/auth', [WorkspaceController::class, 'auth']);

        Route::prefix('import')
            ->group(function () {
                Route::post('/', [ImportController::class, 'store']);
                Route::get('/template', [ImportController::class, 'downloadTemplate']);
            });

        Route::prefix('vk')
            ->group(function () {
                Route::get('/auth-link', [\App\Http\Controllers\VKProductController::class, 'getVKAuthLink']);
            });

        // Экспорт
        Route::prefix('export')->group(function () {
            Route::get('/', [ImportController::class, 'export']);
            Route::get('/vk', [WorkspaceController::class, 'exportVk']);
        });

        // === Мастер-код ===
        Route::prefix('master')->group(function () {

            Route::post('/set', [\App\Http\Controllers\MasterCodeController::class, 'set']);
            Route::post('/verify', [\App\Http\Controllers\MasterCodeController::class, 'verify']);
            Route::post('/change', [\App\Http\Controllers\MasterCodeController::class, 'change']);
            Route::post('/reset', [\App\Http\Controllers\MasterCodeController::class, 'reset']);
        });


        Route::prefix('activity-logs')->group(function () {
            // Activity Logs
            Route::get('/', [ActivityLogController::class, 'index']);
            Route::get('/stats', [ActivityLogController::class, 'stats']);
            Route::delete('/', [ActivityLogController::class, 'clear'])->middleware('master.unlocked');
        });


        // Товары
        Route::prefix('products')->group(function () {
            // Products
            Route::get('/', [ProductController::class, 'index']);
            Route::post('/', [ProductController::class, 'store'])->middleware('master.unlocked');

            // В api.php добавить:
            Route::post('/add-to-stop-list', [ProductController::class, 'addToStopList'])->middleware('master.unlocked');
            Route::post('/remove-from-stop-list', [ProductController::class, 'removeFromStopList'])->middleware('master.unlocked');
            Route::delete('/bulk', [ProductController::class, 'destroyMultiple'])->middleware('master.unlocked');
            Route::get('/{product}', [ProductController::class, 'show']);
            Route::put('/{product}', [ProductController::class, 'update'])->middleware('master.unlocked');
            Route::delete('/{product}', [ProductController::class, 'destroy'])->middleware('master.unlocked');
        });

        // Category presets

        Route::prefix('category-presets')->group(function () {
            Route::get('/', [\App\Http\Controllers\CategoryPresetController::class, 'index']);
            Route::get('/{preset}', [\App\Http\Controllers\CategoryPresetController::class, 'show']);
            Route::post('/{preset}/apply', [\App\Http\Controllers\CategoryPresetController::class, 'apply']);
        });

        Route::prefix('collections')->group(function () {
            Route::get('/', [CollectionController::class, 'index']);
            Route::post('/', [CollectionController::class, 'store'])->middleware('master.unlocked');
            Route::get('/{collection}', [CollectionController::class, 'show']);
            Route::put('/{collection}', [CollectionController::class, 'update'])->middleware('master.unlocked');
            Route::delete('/{collection}', [CollectionController::class, 'destroy'])->middleware('master.unlocked');

            Route::post('/{collection}/products', [CollectionController::class, 'addProducts'])->middleware('master.unlocked');
            Route::delete('/{collection}/products', [CollectionController::class, 'removeProducts'])->middleware('master.unlocked');
            Route::put('/{collection}/reorder', [CollectionController::class, 'reorderProducts'])->middleware('master.unlocked');
        });

        Route::prefix('token')->group(function () {
            // Token management (требует авторизации)
            Route::get('/', [WorkspaceTokenController::class, 'show']);
            Route::post('/regenerate', [WorkspaceTokenController::class, 'regenerate']);
            Route::post('/initialize', [WorkspaceTokenController::class, 'initialize']);
        });

        // Menu Generator
        Route::prefix('menu')->group(function () {


            Route::get('/default-images', [MenuDefaultImageController::class, 'index']);
            Route::post('/default-images', [MenuDefaultImageController::class, 'store']);
            Route::delete('/default-images/{image}', [MenuDefaultImageController::class, 'destroy']);

            Route::get('/config', [MenuGeneratorController::class, 'getConfig']);
            Route::post('/config', [MenuGeneratorController::class, 'saveConfig'])->middleware('master.unlocked');
            Route::post('/logo', [MenuGeneratorController::class, 'uploadLogo'])->middleware('master.unlocked');
            Route::post('/background-image', [MenuGeneratorController::class, 'uploadBackgroundImage'])->middleware('master.unlocked');
            Route::delete('/background-image', [MenuGeneratorController::class, 'removeBackgroundImage'])->middleware('master.unlocked');
            Route::get('/preview', [MenuGeneratorController::class, 'preview']);
            Route::get('/generate', [MenuGeneratorController::class, 'generatePdf']);
        });
        // Категории
        Route::prefix('categories')->group(function () {
            // === Categories ===
            Route::get('/', [CategoryController::class, 'index']);
            Route::post('/', [CategoryController::class, 'store'])->middleware('master.unlocked');
            // НОВОЕ: Товары категории
            Route::get('/{category_id}/products', [CategoryController::class, 'products']);
            Route::get('/{category_id}', [CategoryController::class, 'show']);
            Route::put('/{category_id}', [CategoryController::class, 'update'])->middleware('master.unlocked');
            Route::delete('/{category_id}', [CategoryController::class, 'destroy'])->middleware('master.unlocked');
        });

        // Подборки
        Route::prefix('collections')->group(function () {
            Route::get('/', [CollectionController::class, 'index']);

            Route::post('/', [CollectionController::class, 'store'])->middleware('master.unlocked');
            Route::put('/{collection}', [CollectionController::class, 'update'])->middleware('master.unlocked');
            Route::delete('/{collection}', [CollectionController::class, 'destroy'])->middleware('master.unlocked');
            Route::post('/{collection}/image', [CollectionController::class, 'uploadImage'])->middleware('master.unlocked');
            Route::get('/{collection}/preview', [CollectionController::class, 'previewProducts']);
            Route::get('/{collection}/show', [CollectionController::class, 'show']);
        });

        // Ингредиенты (если нужны отдельные CRUD)
        Route::prefix('ingredients')->group(function () {
            Route::post('/', [IngredientController::class, 'store'])->middleware('master.unlocked');
        });

        Route::prefix('webhooks')->group(function () {

            // Webhooks
            Route::get('/', [WebhookController::class, 'index']);
            Route::post('/', [WebhookController::class, 'store'])->middleware('master.unlocked');
            Route::put('/{webhook}', [WebhookController::class, 'update'])->middleware('master.unlocked');
            Route::delete('/{webhook}', [WebhookController::class, 'destroy'])->middleware('master.unlocked');
            Route::post('/{webhook}/sync', [WebhookController::class, 'sync'])->middleware('master.unlocked');
            Route::post('/sync-all', [WebhookController::class, 'syncAll'])->middleware('master.unlocked');

        });
    });


/*
|--------------------------------------------------------------------------
| Products (вне workspace prefix)
|--------------------------------------------------------------------------
*/

Route::prefix('products')->group(function () {
    Route::post('/{id}', [ProductController::class, 'update']);
    Route::delete('/{id}', [ProductController::class, 'destroy']);
});


/*
|--------------------------------------------------------------------------
| Categories
|--------------------------------------------------------------------------
*/

Route::prefix('categories')->group(function () {
    Route::put('/{id}', [CategoryController::class, 'update']);
    Route::delete('/{id}', [CategoryController::class, 'destroy']);
});


/*
|--------------------------------------------------------------------------
| Collections
|--------------------------------------------------------------------------
*/

Route::prefix('collections')->group(function () {
    Route::put('/{id}', [CollectionController::class, 'update']);
    Route::delete('/{id}', [CollectionController::class, 'destroy']);
});


/*
|--------------------------------------------------------------------------
| Ingredients
|--------------------------------------------------------------------------
*/

Route::prefix('ingredients')->group(function () {
    Route::put('/{id}', [IngredientController::class, 'update']);
    Route::delete('/{id}', [IngredientController::class, 'destroy']);
});


/*
|--------------------------------------------------------------------------
| Public API
|--------------------------------------------------------------------------
*/

Route::prefix('public/{uuid}')->group(function () {
    Route::get('/products', [PublicApiController::class, 'products']);
    Route::get('/products/{id}', [PublicApiController::class, 'product']);
});
