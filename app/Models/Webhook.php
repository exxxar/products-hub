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

                return true;
            } else {
                // ✅ ИСПРАВЛЕНИЕ 1: Убрали Str::limit, чтобы видеть ПОЛНУЮ ошибку от принимающего сервера
                $errorMessage = "HTTP {$response->status()}: " . $response->body();

                $this->update([
                    'last_status' => 'failed',
                    'last_error' => Str::limit($errorMessage, 500) // Обрезаем только при сохранении в БД, чтобы не раздуть таблицу
                ]);

                Log::error('Webhook sync failed', [
                    'webhook_id' => $this->id,
                    'url' => $this->url,
                    'status' => $response->status(),
                    'full_response_body' => $response->body() // ✅ Логируем полный ответ для отладки
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
            ]);

            return false;
        }
    }

    /**
     * Сформировать payload для вебхука
     * ✅ ИСПРАВЛЕНИЕ 2: Более плоская и стандартная структура, которую принимают большинство API
     */
    protected function buildPayload($product = null)
    {
        try {
            $workspace = $this->workspace;

            if ($product) {
                $product->loadMissing(['categories', 'attributes', 'ingredients']);

                return [
                    'event' => 'product.updated',
                    'timestamp' => now()->toISOString(),
                    'workspace_id' => $workspace->id,          // ✅ Вынесли на верхний уровень
                    'workspace_uuid' => $workspace->uuid,      // ✅ Вынесли на верхний уровень
                    'data' => [                                // ✅ Сами данные теперь в ключе 'data'
                        'product' => $this->buildProductData($product)
                    ]
                ];
            } else {
                $workspace->loadMissing(['products.categories', 'products.attributes', 'products.ingredients']);

                return [
                    'event' => 'workspace.sync',
                    'timestamp' => now()->toISOString(),
                    'workspace_id' => $workspace->id,          // ✅ Вынесли на верхний уровень
                    'workspace_uuid' => $workspace->uuid,      // ✅ Вынесли на верхний уровень
                    'workspace_name' => $workspace->name,
                    'data' => [                                // ✅ Сами данные теперь в ключе 'data'
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
            ]);

            return [
                'event' => $product ? 'product.updated' : 'workspace.sync',
                'timestamp' => now()->toISOString(),
                'error' => 'Failed to build complete payload',
                'workspace_id' => $this->workspace->id,
                'workspace_uuid' => $this->workspace->uuid,
            ];
        }
    }

    /**
     * ✅ Безопасное построение данных товара (оставляем как было, оно отличное)
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
            'updated_at' => $product->updated_at?->toISOString() ?? now()->toISOString(),
        ];
    }

    /**
     * ✅ Безопасный map для отношений (оставляем как было)
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
