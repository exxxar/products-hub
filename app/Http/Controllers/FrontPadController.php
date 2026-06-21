<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class FrontPadController extends Controller
{
    //

    public function importProducts(Request $request){
        $workspace = $request->workspace;

        $secret = $workspace->settings["frontpad"]["secret"] ?? '';

        $result = Http::asForm()->post(config("frontpad.api_url") . "?get_products", [
            'secret' => trim($secret)
        ]);

        $frProducts = $result->json("result") ?? "error";

        Product::query()
            ->where("workspace_id", $workspace->id)
            ->chunk(500, function ($products) {
                foreach ($products as $product) {
                    $product->update([
                        'in_stop_list' => true,
                        'deleted_at' => now()
                    ]);
                }
            });

        foreach ($frProducts->product_id as $key => $value) {
            $product = Product::query()
                ->withTrashed()
                ->where("external_id", $value)
                ->where("workspace_id", $workspace->id)
                ->first();

            $tmpProduct = [
                'sku'=>null,
                'external_id' => $value,
                'external_source' => 'frontpad',
                'name' => $frProducts->name[$key],
                'description' => $frProducts->name[$key],
                'images' => [],
                'type' => 0,
                'old_price' => 0,
                'price' => $frProducts->price[$key],
                'deleted_at' => null,
                'in_stop_list' => false,
                'workspace_id' => $workspace->id,
            ];

            if (is_null($product))
                Product::query()->create($tmpProduct);
            else
                $product->update($tmpProduct);
        }

        return response()->noContent();

    }
}
