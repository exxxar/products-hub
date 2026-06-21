<?php

use App\Http\Controllers\ImportController;
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
            Route::post('/', [SettingsController::class, 'save']);
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


        // Товары
        Route::prefix('products')->group(function () {
            // Products
            Route::get('/', [ProductController::class, 'index']);
            Route::post('/', [ProductController::class, 'store']);
            Route::delete('/bulk', [ProductController::class, 'destroyMultiple']);
            Route::get('/{product}', [ProductController::class, 'show']);
            Route::put('/{product}', [ProductController::class, 'update']);
            Route::delete('/{product}', [ProductController::class, 'destroy']);
        });

        // Category presets

        Route::prefix('category-presets')->group(function () {
            Route::get('/', [\App\Http\Controllers\CategoryPresetController::class, 'index']);
            Route::get('/{preset}', [\App\Http\Controllers\CategoryPresetController::class, 'show']);
            Route::post('/{preset}/apply', [\App\Http\Controllers\CategoryPresetController::class, 'apply']);
        });

        Route::prefix('collections')->group(function () {
            Route::get('/', [CollectionController::class, 'index']);
            Route::post('/', [CollectionController::class, 'store']);
            Route::get('/{collection}', [CollectionController::class, 'show']);
            Route::put('/{collection}', [CollectionController::class, 'update']);
            Route::delete('/{collection}', [CollectionController::class, 'destroy']);

            Route::post('/{collection}/products', [CollectionController::class, 'addProducts']);
            Route::delete('/{collection}/products', [CollectionController::class, 'removeProducts']);
            Route::put('/{collection}/reorder', [CollectionController::class, 'reorderProducts']);
        });

        Route::prefix('token')->group(function () {
            // Token management (требует авторизации)
            Route::get('/', [WorkspaceTokenController::class, 'show']);
            Route::post('/regenerate', [WorkspaceTokenController::class, 'regenerate']);
            Route::post('/initialize', [WorkspaceTokenController::class, 'initialize']);
        });

        // Категории
        Route::prefix('categories')->group(function () {
            // === Categories ===
            Route::get('/', [CategoryController::class, 'index']);
            Route::post('/', [CategoryController::class, 'store']);
            // НОВОЕ: Товары категории
            Route::get('/{category_id}/products', [CategoryController::class, 'products']);
            Route::get('/{category_id}', [CategoryController::class, 'show']);
            Route::put('/{category_id}', [CategoryController::class, 'update']);
            Route::delete('/{category_id}', [CategoryController::class, 'destroy']);
        });

        // Подборки
        Route::prefix('collections')->group(function () {
            Route::post('/', [CollectionController::class, 'store']);
        });

        // Ингредиенты (если нужны отдельные CRUD)
        Route::prefix('ingredients')->group(function () {
            Route::post('/', [IngredientController::class, 'store']);
        });

        Route::prefix('webhooks')->group(function () {

            // Webhooks
            Route::get('/', [WebhookController::class, 'index']);
            Route::post('/', [WebhookController::class, 'store']);
            Route::put('/{webhook}', [WebhookController::class, 'update']);
            Route::delete('/{webhook}', [WebhookController::class, 'destroy']);
            Route::post('/{webhook}/sync', [WebhookController::class, 'sync']);
            Route::post('/sync-all', [WebhookController::class, 'syncAll']);

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
