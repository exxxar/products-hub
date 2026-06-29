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
        $product = $productId ? $workspace->products()->find($productId) : null;

        $results = [];
        $startTime = microtime(true);

        foreach ($workspace->webhooks as $webhook) {
            $webhookStartTime = microtime(true);

            try {
                $success = $webhook->sync($product);
                $executionTime = round((microtime(true) - $webhookStartTime) * 1000);

                $results[] = [
                    'id' => $webhook->id,
                    'name' => $webhook->name,
                    'url' => $webhook->url,
                    'success' => $success,
                    'status' => $success ? 200 : ($webhook->last_status ?? 'failed'),
                    'execution_time' => $executionTime,
                    'error' => null,
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'id' => $webhook->id,
                    'name' => $webhook->name,
                    'url' => $webhook->url,
                    'success' => false,
                    'status' => 'ERROR',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'results' => $results,
            'total' => count($results),
            'successful' => collect($results)->where('success', true)->count(),
            'failed' => collect($results)->where('success', false)->count(),
        ]);
    }
}
