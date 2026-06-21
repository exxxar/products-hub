<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Webhook extends Model
{
    protected $fillable = [
        'workspace_id',
        'name',
        'url',
        'sync_on_update',
        'last_synced_at',
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
                'event' => $product ? 'product.updated' : 'workspace.sync'
            ]);

            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'X-Webhook-Source' => 'workspace-platform',
                    'X-Webhook-Event' => $product ? 'product.updated' : 'workspace.sync'
                ])
                ->post($this->url, $payload);

            if ($response->successful()) {
                $this->update([
                    'last_synced_at' => now(),
                    'last_status' => 'success'
                ]);

                Log::info('Webhook sent successfully', [
                    'webhook_id' => $this->id,
                    'status_code' => $response->status()
                ]);

                return true;
            } else {
                $this->update([
                    'last_status' => 'failed'
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
            $this->update([
                'last_status' => 'failed'
            ]);

            Log::error('Webhook sync error', [
                'webhook_id' => $this->id,
                'url' => $this->url,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Сформировать payload для вебхука
     */
    protected function buildPayload($product = null)
    {
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
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'price' => $product->price,
                    'old_price' => $product->old_price,
                    'description' => $product->description,
                    'categories' => collect($product->categories ?? [])->map(fn($c) => [
                        'id' => $c->id,
                        'name' => $c->name
                    ]),
                    'images' => $product->images,
                    'attributes' => collect($product->attributes ?? [])->map(fn($a) => [
                        'name' => $a->name,
                        'value' => $a->value
                    ]),
                    'ingredients' => collect($product->ingredients ?? [])->map(fn($i) => [
                        'id' => $i->id,
                        'name' => $i->name
                    ]),
                    'updated_at' => $product->updated_at->toISOString()
                ]
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
                    'products' => collect($workspace->products ?? [])->map(fn($p) => [
                        'id' => $p->id,
                        'name' => $p->name,
                        'sku' => $p->sku,
                        'price' => $p->price,
                        'old_price' => $p->old_price,
                        'description' => $p->description,
                        'categories' => collect($p->categories ?? [])->map(fn($c) => [
                            'id' => $c->id,
                            'name' => $c->name
                        ]),
                        'images' => $p->images,
                        'attributes' => collect($p->attributes ?? [])->map(fn($a) => [
                            'name' => $a->name,
                            'value' => $a->value
                        ]),
                        'ingredients' => collect($p->ingredients ?? [])->map(fn($i) => [
                            'id' => $i->id,
                            'name' => $i->name
                        ]),
                        'updated_at' => $p->updated_at->toISOString()
                    ])
                ]
            ];
        }
    }
}
