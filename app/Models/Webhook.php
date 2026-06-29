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
     */
    public function sync($product = null)
    {
        try {
            $payload = $this->buildPayload($product);

            Log::info('Sending webhook', [
                'webhook_id' => $this->id,
                'url' => $this->url,
                'event' => $product ? 'product.updated' : 'workspace.sync',
                'payload_size' => strlen(json_encode($payload))
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

                Log::info('Webhook sent successfully', [
                    'webhook_id' => $this->id,
                    'status_code' => $response->status(),
                    'response_time' => $response->header('X-Response-Time')
                ]);

                return true;
            } else {
                $errorMessage = "HTTP {$response->status()}: " . Str::limit($response->body(), 200);

                $this->update([
                    'last_status' => 'failed',
                    'last_error' => $errorMessage
                ]);

                Log::error('Webhook sync failed', [
                    'webhook_id' => $this->id,
                    'url' => $this->url,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return false;
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
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }

    /**
     * Сформировать payload для вебхука
     */
    /**
     * Сформировать payload для вебхука
     */
    protected function buildPayload($product = null)
    {
        try {
            $workspace = $this->workspace;

            if ($product) {
                // Загружаем отношения если не загружены
                $product->loadMissing(['categories', 'attributes', 'ingredients']);

                return [
                    'event' => 'product.updated',
                    'timestamp' => now()->toISOString(),
                    'workspace' => [
                        'id' => $workspace->id,
                        'uuid' => $workspace->uuid,
                        'name' => $workspace->name
                    ],
                    'product' => $this->buildProductData($product),
                ];
            } else {
                // Полная синхронизация
                $workspace->loadMissing(['products.categories', 'products.attributes', 'products.ingredients']);

                return [
                    'event' => 'workspace.sync',
                    'timestamp' => now()->toISOString(),
                    'workspace' => [
                        'id' => $workspace->id,
                        'uuid' => $workspace->uuid,
                        'name' => $workspace->name,
                        'products' => collect($workspace->products ?? [])->map(function ($p) {
                            return $this->buildProductData($p);
                        })->values()->all()
                    ]
                ];
            }
        } catch (\Exception $e) {
            Log::error('Build payload error', [
                'webhook_id' => $this->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Возвращаем минимальный payload в случае ошибки
            return [
                'event' => $product ? 'product.updated' : 'workspace.sync',
                'timestamp' => now()->toISOString(),
                'error' => 'Failed to build complete payload',
                'workspace' => [
                    'id' => $this->workspace->id,
                    'uuid' => $this->workspace->uuid,
                ]
            ];
        }
    }

    /**
     * ✅ Безопасное построение данных товара
     */
    protected function buildProductData(Product $product): array
    {
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
                return [
                    'id' => $c->id ?? null,
                    'name' => $c->name ?? '',
                ];
            }),
            'images' => $this->safeMapRelation($product->images ?? [], function ($img) {
                if (is_array($img)) {
                    return [
                        'url' => $img['url'] ?? '',
                        'name' => $img['name'] ?? '',
                    ];
                }
                return [
                    'url' => $img->url ?? '',
                    'name' => $img->name ?? '',
                ];
            }),
            'attributes' => $this->safeMapRelation($product->attributes ?? [], function ($a) {
                return [
                    'name' => $a->name ?? '',
                    'value' => $a->value ?? '',
                ];
            }),
            'ingredients' => $this->safeMapRelation($product->ingredients ?? [], function ($i) {
                return [
                    'id' => $i->id ?? null,
                    'name' => $i->name ?? '',
                ];
            }),
            'updated_at' => $product->updated_at?->toISOString() ?? now()->toISOString(),
        ];
    }

    /**
     * ✅ Безопасный map для отношений
     * Обрабатывает: null, массив, коллекцию
     */
    protected function safeMapRelation($data, callable $callback): array
    {
        if (empty($data)) {
            return [];
        }

        try {
            return collect($data)
                ->filter() // убираем null
                ->map($callback)
                ->values()
                ->all();
        } catch (\Throwable $e) {
            Log::warning('Safe map relation failed', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
}
