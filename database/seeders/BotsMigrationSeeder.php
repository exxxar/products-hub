<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\Workspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BotsMigrationSeeder extends Seeder
{
    private array $categoryMap = [];
    private array $workspaceMap = [];

    public function run(): void
    {
        $this->command->info('🚀 Начинаем миграцию ботов из bcash...');

        $bots = DB::connection('bcash')
            ->table('bots')
            ->whereNull('deleted_at')
            ->get();

        $this->command->info("Найдено ботов для миграции: {$bots->count()}");

        // ✅ ПРАВИЛЬНОЕ создание прогресс-бара
        $bar = $this->command->getOutput()->createProgressBar($bots->count());
        $bar->start();

        $companyGroups = $bots->groupBy('company_id');

        foreach ($bots as $bot) {
            try {
                $this->migrateBot($bot);
            } catch (\Throwable $e) {
                $this->command->error("Ошибка миграции бота #{$bot->id} ({$bot->title}): " . $e->getMessage());
                Log::error("Migration bot #{$bot->id} failed", [
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine(2);

        $this->linkWorkspacesByCompany($companyGroups);
        $this->outputWorkspaceLinks();

        $this->command->info('✅ Миграция завершена!');
    }

    private function migrateBot(object $bot): void
    {
        $uuid = (string) Str::uuid();

        $logoPath = null;
        if (!empty($bot->image) && $this->isValidUrl($bot->image)) {
            $logoPath = $this->downloadImage($bot->image, "workspaces/{$uuid}");
        }

        $settings = [
            'visual' => [
                'label' => $bot->title,
                'color' => $this->randomColor(),
            ],
            'welcome_message' => $bot->welcome_message,
            'social_links' => json_decode($bot->social_links ?? '{}', true),
            'commands' => json_decode($bot->commands ?? '[]', true),
            'cashback_config' => json_decode($bot->cashback_config ?? '{}', true),
            'menu' => json_decode($bot->menu ?? '{}', true),
            'old_bot_id' => $bot->id,
            'old_company_id' => $bot->company_id,
        ];

        $workspace = Workspace::create([
            'uuid' => $uuid,
            'name' => $bot->title ?: $bot->bot_domain,
            'label' => $bot->title,
            'logo_path' => $logoPath,
            'description' => $bot->long_description ?: $bot->description,
            'url' => $bot->info_link,
            'settings' => $settings,
        ]);

        $this->workspaceMap[$bot->id] = $workspace;
        $this->categoryMap[$bot->id] = [];

        Log::info("Workspace created: {$workspace->name} (UUID: {$uuid})");

        $this->migrateCategories($bot->id, $workspace);
        $this->migrateProducts($bot->id, $workspace);
    }

    private function migrateCategories(int $botId, Workspace $workspace): void
    {
        $categories = DB::connection('bcash')
            ->table('product_categories')
            ->where('bot_id', $botId)
            ->orderBy('order_position')
            ->get();

        foreach ($categories as $cat) {
            $newCategory = Category::create([
                'workspace_id' => $workspace->id,
                'parent_id' => null,
                'name' => $cat->title,
                'sort_order' => $cat->order_position,
            ]);

            $this->categoryMap[$botId][$cat->id] = $newCategory->id;
        }
    }

    private function migrateProducts(int $botId, Workspace $workspace): void
    {
        $products = DB::connection('bcash')
            ->table('products')
            ->where('bot_id', $botId)
            ->get();

        foreach ($products as $oldProduct) {
            $images = json_decode($oldProduct->images ?? '[]', true) ?: [];
            $localImages = [];
            foreach ($images as $imgUrl) {
                // ✅ Проверяем валидность URL
                if ($this->isValidUrl($imgUrl)) {
                    $path = $this->downloadImage($imgUrl, "products/{$oldProduct->id}");
                    if ($path) {
                        $localImages[] = $path;
                    }
                }
            }

            $config = [
                'variants' => json_decode($oldProduct->variants ?? '{}', true),
                'weight_config' => json_decode($oldProduct->weight_config ?? '{}', true),
                'delivery_terms' => $oldProduct->delivery_terms,
                'not_for_delivery' => (bool) $oldProduct->not_for_delivery,
                'is_weight_product' => (bool) $oldProduct->is_weight_product,
            ];

            $product = Product::create([
                'workspace_id' => $workspace->id,
                'name' => $oldProduct->title,
                'price' => $oldProduct->current_price,
                'old_price' => $oldProduct->old_price ?: 0,
                'sku' => $oldProduct->article,
                'description' => $oldProduct->description,
                'images' => $localImages,
                'dimensions' => json_decode($oldProduct->dimension ?? '{}', true),
                'config' => $config,
                'is_active' => is_null($oldProduct->deleted_at),
                'in_stop_list' => !is_null($oldProduct->in_stop_list_at),
            ]);

            $this->migrateProductAttributes($oldProduct->id, $product->id);
            $this->attachCategories($oldProduct->id, $product->id, $botId);
        }
    }

    private function migrateProductAttributes(int $oldProductId, int $newProductId): void
    {
        $options = DB::connection('bcash')
            ->table('product_options')
            ->where('product_id', $oldProductId)
            ->get();

        foreach ($options as $opt) {
            ProductAttribute::create([
                'product_id' => $newProductId,
                'name' => $opt->title ?: $opt->key ?: 'Атрибут',
                'value' => $opt->value,
            ]);
        }
    }

    private function attachCategories(int $oldProductId, int $newProductId, int $botId): void
    {
        $oldCategoryIds = DB::connection('bcash')
            ->table('product_product_category')
            ->where('product_id', $oldProductId)
            ->pluck('product_category_id');

        $newCategoryIds = [];
        foreach ($oldCategoryIds as $oldCatId) {
            if (isset($this->categoryMap[$botId][$oldCatId])) {
                $newCategoryIds[] = $this->categoryMap[$botId][$oldCatId];
            }
        }

        if (!empty($newCategoryIds)) {
            $product = Product::find($newProductId);
            if ($product) {
                $product->categories()->attach($newCategoryIds);
            }
        }
    }

    private function linkWorkspacesByCompany($companyGroups): void
    {
        $this->command->info('🔗 Связываем воркспейсы по company_id...');

        foreach ($companyGroups as $companyId => $groupBots) {
            if ($groupBots->count() < 2) {
                continue;
            }

            $uuids = [];
            foreach ($groupBots as $bot) {
                if (isset($this->workspaceMap[$bot->id])) {
                    $uuids[] = $this->workspaceMap[$bot->id]->uuid;
                }
            }

            for ($i = 0; $i < count($uuids); $i++) {
                for ($j = $i + 1; $j < count($uuids); $j++) {
                    $ws1 = Workspace::where('uuid', $uuids[$i])->first();
                    if ($ws1) {
                        $ws1->linkWorkspace($uuids[$j]);
                    }
                }
            }

            $this->command->info("  Связано {$groupBots->count()} воркспейсов для company_id={$companyId}");
        }
    }

    private function outputWorkspaceLinks(): void
    {
        $this->command->newLine();
        $this->command->info('📋 Ссылки на созданные воркспейсы:');
        $this->command->line(str_repeat('-', 80));

        foreach ($this->workspaceMap as $botId => $workspace) {
            $url = $workspace->getAccessUrl();
            $this->command->line("  [Bot #{$botId}] {$workspace->name}");
            $this->command->line("    URL: {$url}");
            $this->command->line("    UUID: {$workspace->uuid}");

            Log::info("Workspace link: Bot #{$botId} -> {$url}");
        }

        $this->command->line(str_repeat('-', 80));
    }

    /**
     * ✅ НОВАЯ МЕТОД: Проверка валидности URL
     */
    private function isValidUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    private function downloadImage(string $url, string $directory): ?string
    {
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 15,
                    'user_agent' => 'Mozilla/5.0 (compatible; MigrationBot/1.0)',
                ],
                'https' => [
                    'timeout' => 15,
                    'user_agent' => 'Mozilla/5.0 (compatible; MigrationBot/1.0)',
                ],
            ]);

            $content = @file_get_contents($url, false, $context);
            if ($content === false) {
                $this->command->warn("  ⚠ Не удалось скачать: {$url}");
                return null;
            }

            $pathInfo = pathinfo(parse_url($url, PHP_URL_PATH) ?? '');
            $ext = strtolower($pathInfo['extension'] ?? 'jpg');
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $ext = 'jpg';
            }

            $filename = Str::uuid() . '.' . $ext;
            $fullPath = "{$directory}/{$filename}";

            Storage::disk('public')->put($fullPath, $content);

            return $fullPath;
        } catch (\Throwable $e) {
            $this->command->warn("  ⚠ Ошибка скачивания {$url}: " . $e->getMessage());
            return null;
        }
    }

    private function randomColor(): string
    {
        $colors = ['#0d6efd', '#6610f2', '#6f42c1', '#d63384', '#dc3545', '#fd7e14', '#ffc107', '#198754', '#20c997', '#0dcaf0'];
        return $colors[array_rand($colors)];
    }
}
