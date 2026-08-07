<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Webhook extends Model
{
    protected $fillable = [
        'workspace_id',
        'name',
        'url',
        'sync_on_update',
        'last_synced_at',
        'last_error',
        'last_status'
    ];

    protected $casts = [
        'sync_on_update' => 'boolean',
        'last_synced_at' => 'datetime'
    ];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Отправить синхронизацию
     *
     * @return array{success: bool, products_count: int, collections_count: int, error: string|null}
     */
    public function sync($product = null): array
    {
        // ✅ Сначала считаем статистику ДО отправки
        $stats = $this->calculateSyncStats($product);

        try {
            $payload = $this->buildPayload($product);

            Log::info('Sending webhook', [
                'webhook_id' => $this->id,
                'url' => $this->url,
                'event' => $product ? 'product.updated' : 'workspace.sync',
                'stats' => $stats,
            ]);

            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'X-Webhook-Source' => 'workspace-platform',
                    'X-Webhook-Event' => $product ? 'product.updated' : 'workspace.sync',
                    'X-Webhook-Timestamp' => now()->toISOString()
                ])
                ->post($this->url, $payload);

            if ($response->successful()) {
                $this->update([
                    'last_synced_at' => now(),
                    'last_status' => 'success',
                    'last_error' => null
                ]);

                return array_merge($stats, [
                    'success' => true,
                    'error' => null,
                ]);
            } else {
                $errorMessage = "HTTP {$response->status()}: " . $response->body();

                $this->update([
                    'last_status' => 'failed',
                    'last_error' => Str::limit($errorMessage, 500)
                ]);

                Log::error('Webhook sync failed', [
                    'webhook_id' => $this->id,
                    'url' => $this->url,
                    'status' => $response->status(),
                    'full_response_body' => $response->body()
                ]);

                return array_merge($stats, [
                    'success' => false,
                    'error' => $errorMessage,
                ]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();

            $this->update([
                'last_status' => 'failed',
                'last_error' => Str::limit($errorMessage, 500)
            ]);

            Log::error('Webhook sync error', [
                'webhook_id' => $this->id,
                'url' => $this->url,
                'error' => $errorMessage,
            ]);

            return array_merge($stats, [
                'success' => false,
                'error' => $errorMessage,
            ]);
        }
    }


    /**
     * ✅ Подсчёт статистики: сколько товаров и коллекций будет синхронизировано
     */
    protected function calculateSyncStats($product = null): array
    {
        $workspace = $this->workspace;

        if ($product) {
            // Обновление одного товара: 1 товар + все коллекции, к которым он привязан
            $product->loadMissing('collections');

            return [
                'products_count' => 1,
                'collections_count' => $product->collections->count(), // свойство, не метод
                'event' => 'product.updated',
            ];
        }

        // Полная синхронизация воркспейса
        // Загружаем коллекции явно, чтобы получить Collection, а не Query Builder
        $workspace->loadMissing('collections');

        return [
            'products_count' => $workspace->products_count ?? $workspace->products()->count(),
            'collections_count' => $workspace->collections->count(), // свойство, не метод
            'event' => 'workspace.sync',
        ];
    }
    /**
     * Сформировать payload для вебхука
     */
    protected function buildPayload($product = null)
    {
        try {
            $workspace = $this->workspace;

            if ($product) {
                $product->loadMissing(['categories', 'attributes', 'ingredients', 'collections']);

                return [
                    'event' => 'product.updated',
                    'timestamp' => now()->toISOString(),
                    'workspace' => [
                        'id' => $workspace->id,
                        'uuid' => $workspace->uuid,
                        'name' => $workspace->name
                    ],
                    'data' => [
                        'product' => $this->buildProductData($product),
                        // ✅ Передаем коллекции, к которым привязан обновленный товар,
                        // чтобы принимающая сторона могла пересчитать цены наборов
                        'collections' => collect($product->collections ?? [])->map(function ($c) {
                            return $this->buildCollectionData($c);
                        })->values()->all()
                    ]
                ];
            } else {
                // Предзагружаем связи для товаров и коллекций (избегаем N+1)
                $workspace->loadMissing([
                    'products.categories',
                    'products.attributes',
                    'products.ingredients',
                    'products.collections',
                    'collections.collectionCategories.products',
                    'collections.collectionCategories.category',
                    'collections.products'
                ]);

                return [
                    'event' => 'workspace.sync',
                    'timestamp' => now()->toISOString(),
                    'workspace' => [
                        'id' => $workspace->id,
                        'uuid' => $workspace->uuid,
                        'name' => $workspace->name,
                        'products' => collect($workspace->products ?? [])->map(function ($p) {
                            return $this->buildProductData($p);
                        })->values()->all(),
                        // ✅ Передаем ВСЕ коллекции воркспейса
                        'collections' => collect($workspace->collections ?? [])->map(function ($c) {
                            return $this->buildCollectionData($c);
                        })->values()->all()
                    ]
                ];
            }
        } catch (\Exception $e) {
            Log::error('Build payload error', [
                'webhook_id' => $this->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'event' => $product ? 'product.updated' : 'workspace.sync',
                'timestamp' => now()->toISOString(),
                'error' => 'Failed to build complete payload',
                'workspace' => [
                    'id' => $this->workspace->id,
                    'uuid' => $this->workspace->uuid,
                    'name' => $this->workspace->name,
                ]
            ];
        }
    }

    /**
     * ✅ Безопасное построение данных товара
     */
    protected function buildProductData(Product $product): array
    {
        $product->loadMissing('collections');

        return [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku ?? '',
            'price' => (float) ($product->price ?? 0),
            'old_price' => $product->old_price ? (float) $product->old_price : null,
            'description' => $product->description ?? '',
            'is_active' => (bool) $product->is_active,
            'in_stop_list' => (bool) $product->in_stop_list,
            'categories' => $this->safeMapRelation($product->categories ?? [], function ($c) {
                return ['id' => $c->id ?? null, 'name' => $c->name ?? ''];
            }),
            'images' => $this->safeMapRelation($product->images ?? [], function ($img) {
                if (is_array($img)) {
                    return ['url' => $img['url'] ?? '', 'name' => $img['name'] ?? ''];
                }
                return ['url' => $img->url ?? '', 'name' => $img->name ?? ''];
            }),
            'attributes' => $this->safeMapRelation($product->attributes ?? [], function ($a) {
                return ['name' => $a->name ?? '', 'value' => $a->value ?? ''];
            }),
            'ingredients' => $this->safeMapRelation($product->ingredients ?? [], function ($i) {
                return ['id' => $i->id ?? null, 'name' => $i->name ?? ''];
            }),
            // ✅ Добавили список коллекций, в которых состоит товар
            'collections' => $this->safeMapRelation($product->collections ?? [], function ($c) {
                return [
                    'id' => $c->id ?? null,
                    'name' => $c->name ?? '',
                    'sort_order' => $c->pivot->sort_order ?? null
                ];
            }),
            'updated_at' => $product->updated_at?->toISOString() ?? now()->toISOString(),
        ];
    }

    /**
     * ✅ Построение данных коллекции
     */
    protected function buildCollectionData(Collection $collection): array
    {
        try {
            // Предзагружаем связи для корректной работы аксессоров (calculated_price, final_price и т.д.)
            $collection->loadMissing([
                'collectionCategories.products',
                'products',
                'collectionCategories.category'
            ]);

            return [
                'id' => $collection->id,
                'name' => $collection->name,
                'description' => $collection->description ?? '',
                'short_description' => $collection->short_description ?? '',
                'type' => $collection->type,
                'pricing_type' => $collection->pricing_type,
                'fixed_price' => $collection->fixed_price !== null ? (float) $collection->fixed_price : null,
                'discount_percent' => $collection->discount_percent,
                'image_url' => $collection->image_url ?? '',
                'is_active' => (bool) $collection->is_active,
                'in_stop_list' => (bool) $collection->in_stop_list,
                'sort_order' => $collection->sort_order,
                'calculated_price' => (float) $collection->calculated_price,
                'calculated_old_price' => $collection->calculated_old_price ? (float) $collection->calculated_old_price : null,
                'products_count' => (int) $collection->products_count,
                'discount_amount' => (float) $collection->discount_amount,
                'final_price' => (float) $collection->final_price,

                // Категории внутри custom-коллекций
                'collection_categories' => $this->safeMapRelation($collection->collectionCategories ?? [], function ($cc) {
                    return [
                        'id' => $cc->id,
                        'category_id' => $cc->category_id,
                        'category_name' => $cc->category_name ?? '',
                        'selection_rule' => $cc->selection_rule,
                        'sort_order' => $cc->sort_order,
                        'rule_label' => $cc->rule_label ?? '',
                        'subtotal' => (float) $cc->subtotal,
                        // Передаем только ID товаров и порядок, чтобы не дублировать полные объекты товаров
                        'products' => $this->safeMapRelation($cc->products ?? [], function ($p) {
                            return [
                                'id' => $p->id,
                                'sort_order' => $p->pivot->sort_order ?? null
                            ];
                        })
                    ];
                }),

                // Прямые товары (для all_products)
                'direct_products' => $collection->type === Collection::TYPE_ALL_PRODUCTS
                    ? $this->safeMapRelation($collection->products ?? [], function ($p) {
                        return [
                            'id' => $p->id,
                            'sort_order' => $p->pivot->sort_order ?? null
                        ];
                    })
                    : []
            ];
        } catch (\Throwable $e) {
            Log::warning('Build collection data failed', [
                'collection_id' => $collection->id ?? null,
                'error' => $e->getMessage()
            ]);

            // Возвращаем минимальный безопасный fallback
            return [
                'id' => $collection->id ?? null,
                'name' => $collection->name ?? '',
                'error' => 'Failed to build collection data'
            ];
        }
    }

    /**
     * Безопасный map для отношений
     */
    protected function safeMapRelation($data, callable $callback): array
    {
        if (empty($data)) {
            return [];
        }

        try {
            return collect($data)
                ->filter()
                ->map($callback)
                ->values()
                ->all();
        } catch (\Throwable $e) {
            Log::warning('Safe map relation failed', ['error' => $e->getMessage()]);
            return [];
        }
    }
}
