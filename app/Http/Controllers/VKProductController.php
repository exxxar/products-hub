<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use VK\Client\VKApiClient;
use VK\OAuth\VKOAuth;
use VK\OAuth\VKOAuthDisplay;
use VK\OAuth\VKOAuthResponseType;

class VKProductController extends Controller
{
    protected $workspace;

    /**
     * Получить ссылку для авторизации VK
     */
    public function getVKAuthLink(Request $request)
    {
        if (!$request->workspace) {
            return response()->json(['error' => 'Workspace not provided'], 400);
        }

        $workspace = $request->workspace;
        $oauth = new VKOAuth();
        $client_id = config('services.vk.client_id');
        $redirect_uri = config('app.url') . '/workspace/vk-callback';
        $display = VKOAuthDisplay::PAGE;
        $scope = [4096]; // VKOAuthUserScope::MARKET может не существовать
        $state = $workspace->uuid;



        try {
            $browser_url = $oauth->getAuthorizeUrl(
                VKOAuthResponseType::CODE,
                (int)$client_id,
                $redirect_uri,
                $display,
                $scope,
                $state
            );

            return response()->json(['url' => $browser_url]);
        } catch (\Exception $e) {
            Log::error('VK auth link generation failed', [
                'error' => $e->getMessage(),
                'workspace' => $workspace->uuid
            ]);
            return response()->json(['error' => 'Failed to generate auth link'], 500);
        }
    }

    public function callback(Request $request)
    {
        ini_set('max_execution_time', '300');

        if (!$request->has('code') || !$request->has('state')) {
            return response()->json(['error' => 'Missing code or state'], 400);
        }

        $code = $request->input('code');
        $state = $request->input('state');

        try {
            $this->workspace = Workspace::query()
                ->where('uuid', $state)
                ->firstOrFail();
        } catch (\Exception $e) {
            Log::error('Workspace not found for VK callback', ['state' => $state]);
            return response()->json(['error' => 'Workspace not found'], 404);
        }

        $settings = $this->workspace->settings ?? [];
        $links = $settings['vk_shop_links'] ?? null;

        if (empty($links)) {
            return response()->json([
                'error' => 'Не указаны группы для выгрузки товаров. Укажите их в настройках workspace.'
            ], 400);
        }

        $links = trim($links);
        $links = str_contains($links, ',')
            ? array_map('trim', explode(',', $links))
            : [$links];

        $links = array_filter($links, fn($link) => !empty($link));

        if (empty($links)) {
            return response()->json(['error' => 'Нет валидных ссылок'], 400);
        }

        $importedProductIds = [];

        foreach ($links as $link) {
            try {
                $ids = $this->vkHandler($link, $code);
                $importedProductIds = array_merge($importedProductIds, $ids);
            } catch (\Exception $e) {
                Log::error('VK import failed for link', [
                    'link' => $link,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Помечаем товары, которых нет в VK
        if (!empty($importedProductIds)) {
            Product::query()
                ->where('workspace_id', $this->workspace->id)
                ->where('external_source', 'vk')
                ->whereNotIn('external_id', $importedProductIds)
                ->update([
                    'in_stop_list' => true,
                    'deleted_at' => now()
                ]);
        }

        return response()->json([
            'message' => 'ok',
            'imported_count' => count($importedProductIds)
        ]);
    }

    /**
     * Импорт товаров из VK
     */
    protected function importProducts($vkProducts, $album): array
    {
        $importedIds = [];

        foreach ($vkProducts as $vkProduct) {
            try {
                $vkProduct = (object)$vkProduct;
                $tmpCategoryForSync = [];

                // === Обработка альбома как категории ===
                if ($album && !empty($album->title)) {
                    $albumCategory = $this->findOrCreateCategory($album->title);
                    $tmpCategoryForSync[] = $albumCategory->id;
                }

                // === Обработка категории VK ===
                if (!empty($vkProduct->category)) {
                    $vkCategory = (object)$vkProduct->category;

                    if (!empty($vkCategory->name)) {
                        $productCategory = $this->findOrCreateCategory($vkCategory->name);
                        $tmpCategoryForSync[] = $productCategory->id;
                    }

                    // === Обработка секции категории ===
                    if (!empty($vkCategory->section) && !empty($vkCategory->section->name)) {
                        $sectionCategory = $this->findOrCreateCategory($vkCategory->section->name);
                        $tmpCategoryForSync[] = $sectionCategory->id;
                    }
                }

                // === Поиск существующего товара ===
                $product = Product::query()
                    ->withTrashed()
                    ->where('external_source', 'vk')
                    ->where('external_id', $vkProduct->id)
                    ->where('workspace_id', $this->workspace->id)
                    ->first();

                // === Обработка изображений ===
                $images = [];
                if (!empty($vkProduct->thumb_photo)) {
                    $images[] = $vkProduct->thumb_photo;
                }

                if (!empty($vkProduct->photos) && is_array($vkProduct->photos)) {
                    foreach ($vkProduct->photos as $photo) {
                        $photo = (object)$photo;
                        $sizes = $photo->sizes ?? [];

                        if (!empty($sizes)) {
                            $last = end($sizes);
                            if (isset($last['url'])) {
                                $images[] = $last['url'];
                            }
                        }
                    }
                }

                // === Обработка габаритов ===
                $dimensions = [
                    'width' => 0,
                    'height' => 0,
                    'length' => 0,
                    'weight' => 0
                ];

                if (!empty($vkProduct->dimensions) && is_array($vkProduct->dimensions)) {
                    foreach ($vkProduct->dimensions as $key => $value) {
                        if (array_key_exists($key, $dimensions)) {
                            $dimensions[$key] = (float)$value;
                        }
                    }
                }

                if (!empty($vkProduct->weight)) {
                    $dimensions['weight'] = (float)$vkProduct->weight;
                }

                // === Формирование данных товара ===
                $productData = [
                    'sku' => $vkProduct->sku ?? $vkProduct->id,
                    'external_id' => $vkProduct->id ?? '-',
                    'external_source' => 'vk',
                    'price' => isset($vkProduct->price['amount'])
                        ? $vkProduct->price['amount'] / 100
                        : 0,
                    'old_price' => isset($vkProduct->price['old_amount'])
                        ? $vkProduct->price['old_amount'] / 100
                        : 0,
                    'name' => $vkProduct->title ?? '-',
                    'description' => $vkProduct->description ?? '-',
                    'images' => $images,
                    'dimensions' => $dimensions,
                    'in_stop_list' => ($vkProduct->availability ?? 1) == 0, // availability == 0 означает недоступен
                    'workspace_id' => $this->workspace->id,
                ];

                // === Создание или обновление товара ===
                if (is_null($product)) {
                    $product = Product::query()->create($productData);
                } else {
                    $product->update($productData);
                    if ($product->trashed()) {
                        $product->restore();
                    }
                }

                // === Синхронизация категорий ===
                if (!empty($tmpCategoryForSync)) {
                    $product->categories()->sync($tmpCategoryForSync);
                }

                $importedIds[] = $vkProduct->id;

            } catch (\Exception $e) {
                Log::error('Failed to import VK product', [
                    'product_id' => $vkProduct->id ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
                continue; // Пропускаем проблемный товар и идём дальше
            }
        }

        return $importedIds;
    }

    /**
     * Найти или создать категорию
     */
    protected function findOrCreateCategory(string $name): \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model
    {
        return Category::query()
            ->firstOrCreate(
                [
                    'name' => $name,
                    'workspace_id' => $this->workspace->id
                ],
                [
                    'name' => $name,
                    'workspace_id' => $this->workspace->id
                ]
            );
    }

    /**
     * Обработчик импорта из одной группы VK
     */
    protected function vkHandler(string $link, string $code): array
    {
        $oauth = new VKOAuth();
        $client_id = config('services.vk.client_id');
        $client_secret = config('services.vk.client_secret');
        $redirect_uri = config('app.url') . '/workspace/vk-callback';

        // Извлекаем screen_name из ссылки
        $screenName = $this->extractScreenName($link);
        if (!$screenName) {
            throw new \Exception("Invalid VK link: {$link}");
        }

        // Получаем access token
        try {
            $response = $oauth->getAccessToken($client_id, $client_secret, $redirect_uri, $code);
            $access_token = $response['access_token'] ?? null;

            if (!$access_token) {
                throw new \Exception('Failed to get access token');
            }
        } catch (\Exception $e) {
            Log::error('VK access token error', ['error' => $e->getMessage()]);
            throw $e;
        }

        $vk = new VKApiClient();

        // Получаем ID группы
        try {
            $response = $vk->utils()->resolveScreenName($access_token, [
                'screen_name' => $screenName,
            ]);

            $data = (object)$response;

            if (!isset($data->type) || !in_array($data->type, ['group', 'page'])) {
                throw new \Exception("Invalid group type: {$data->type}");
            }

            $ownerId = $data->object_id;
        } catch (\Exception $e) {
            Log::error('Failed to resolve VK screen name', [
                'screen_name' => $screenName,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }

        $importedIds = [];

        try {
            // Получаем альбомы (подборки)
            $response = $vk->market()->getAlbums($access_token, [
                'owner_id' => "-{$ownerId}",
            ]);

            $vkAlbums = $response['items'] ?? [];

            if (!empty($vkAlbums)) {
                foreach ($vkAlbums as $album) {
                    $album = (object)$album;
                    $ids = $this->importAlbumProducts($vk, $access_token, $ownerId, $album);
                    $importedIds = array_merge($importedIds, $ids);
                }
            } else {
                // Если альбомов нет - импортируем все товары
                $ids = $this->importAlbumProducts($vk, $access_token, $ownerId, null);
                $importedIds = array_merge($importedIds, $ids);
            }
        } catch (\Exception $e) {
            Log::error('VK market API error', [
                'owner_id' => $ownerId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }

        return $importedIds;
    }

    /**
     * Импорт товаров из альбома (или всех товаров если альбома нет)
     */
    protected function importAlbumProducts($vk, $access_token, $ownerId, $album): array
    {
        $importedIds = [];
        $offset = 0;
        $count = 200;

        do {
            $params = [
                'owner_id' => "-{$ownerId}",
                'need_variants' => 1,
                'count' => $count,
                'offset' => $offset,
                'with_disabled' => 1,
                'extended' => 1
            ];

            if ($album) {
                $params['album_id'] = $album->id;
            }

            $response = $vk->market()->get($access_token, $params);

            $items = $response['items'] ?? [];
            $total = $response['count'] ?? 0;

            if (!empty($items)) {
                $ids = $this->importProducts($items, $album);
                $importedIds = array_merge($importedIds, $ids);
            }

            $offset += $count;

            // Освобождаем память
            unset($items, $response);

        } while ($offset < $total);

        return $importedIds;
    }

    /**
     * Извлечь screen_name из ссылки VK
     */
    protected function extractScreenName(string $link): ?string
    {
        $pattern = '/https?:\/\/vk\.com\/([a-zA-Z0-9_.]+)/';
        if (preg_match($pattern, $link, $matches)) {
            return $matches[1];
        }
        return null;
    }
}
