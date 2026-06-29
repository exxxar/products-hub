<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Webhook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function index()
    {
        $workspace = App::make('workspace');
        return response()->json($workspace->webhooks);
    }

    public function store(Request $request, $workspaceUuid)
    {
        $workspace = App::make('workspace');

        $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url',
            'sync_on_update' => 'boolean'
        ]);

        $webhook = $workspace->webhooks()->create([
            'name' => $request->name,
            'url' => $request->url,
            'sync_on_update' => $request->sync_on_update ?? false
        ]);

        return response()->json($webhook, 201);
    }

    public function update(Request $request, $workspaceUuid, Webhook $webhook)
    {
        $workspace = App::make('workspace');

        if ($webhook->workspace_id !== $workspace->id) {
            abort(403);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'url' => 'sometimes|url',
            'sync_on_update' => 'boolean'
        ]);

        $webhook->update($request->only(['name', 'url', 'sync_on_update']));

        return response()->json($webhook);
    }

    public function destroy(Webhook $webhook)
    {
        $workspace = App::make('workspace');

        if ($webhook->workspace_id !== $workspace->id) {
            abort(403);
        }

        $webhook->delete();

        return response()->json(['message' => 'Webhook deleted']);
    }

    public function sync(Request $request, $workspaceUuid, Webhook $webhook)
    {
        $workspace = App::make('workspace');

        if ($webhook->workspace_id !== $workspace->id) {
            abort(403);
        }

        $productId = $request->query('product_id');
        $product = $productId ? $workspace->products()->find($productId) : null;

        $success = $webhook->sync($product);

        return response()->json([
            'success' => $success,
            'webhook' => $webhook->fresh()
        ]);
    }

    public function syncAll(Request $request)
    {
        $workspace = App::make('workspace');

        $productId = $request->query('product_id');
        $product = $productId ? $workspace->products()->with(['categories', 'attributes', 'ingredients'])->find($productId) : null;

        $webhooks = $workspace->webhooks;
        $results = [];
        $totalSuccess = 0;
        $totalFailed = 0;

        Log::info('Starting webhook sync', [
            'workspace' => $workspace->uuid,
            'webhooks_count' => $webhooks->count(),
            'product_id' => $productId
        ]);

        foreach ($webhooks as $webhook) {
            try {
                $startTime = microtime(true);

                $success = $webhook->sync($product);

                $responseTime = round((microtime(true) - $startTime) * 1000); // в мс

                $results[] = [
                    'id' => $webhook->id,
                    'name' => $webhook->name,
                    'url' => $webhook->url,
                    'success' => $success,
                    'response_time_ms' => $responseTime,
                    'last_synced_at' => $webhook->fresh()->last_synced_at,
                    'last_status' => $webhook->fresh()->last_status
                ];

                if ($success) {
                    $totalSuccess++;
                } else {
                    $totalFailed++;
                }

            } catch (\Exception $e) {
                Log::error('Webhook sync exception', [
                    'webhook_id' => $webhook->id,
                    'error' => $e->getMessage()
                ]);

                $results[] = [
                    'id' => $webhook->id,
                    'name' => $webhook->name,
                    'url' => $webhook->url,
                    'success' => false,
                    'error' => $e->getMessage(),
                    'last_synced_at' => $webhook->last_synced_at,
                    'last_status' => 'failed'
                ];

                $totalFailed++;
            }
        }

        Log::info('Webhook sync completed', [
            'workspace' => $workspace->uuid,
            'total' => count($results),
            'success' => $totalSuccess,
            'failed' => $totalFailed
        ]);

        return response()->json([
            'success' => true,
            'total' => count($results),
            'successful' => $totalSuccess,
            'failed' => $totalFailed,
            'results' => $results
        ]);
    }
}
