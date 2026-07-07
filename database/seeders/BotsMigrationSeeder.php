<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\Workspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BotsMigrationSeeder extends Seeder
{
    private array $categoryMap = [];
    private array $workspaceMap = [];

    public function run(): void
    {
        $this->command->info('🚀 Начинаем миграцию ботов из bcash...');

        // ✅ Отключаем observers для ускорения и избежания ошибок
        $this->disableObservers();

        $bots = DB::connection('bcash')
            ->table('bots')
            ->whereNull('deleted_at')
            ->get();

        $this->command->info("Найдено ботов для миграции: {$bots->count()}");

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

        // ✅ Включаем observers обратно
        $this->enableObservers();

        $this->command->info('✅ Миграция завершена!');
    }

    private function migrateBot(object $bot): void
    {
        $uuid = (string) Str::uuid();

        // ✅ Просто сохраняем внешнюю ссылку на логотип (без скачивания)
        $logoPath = null;
        if (!empty($bot->image) && $this->isValidUrl($bot->image)) {
            $logoPath = $bot->image; // Сохраняем URL как есть
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
            'logo_path' => $logoPath, // ✅ Внешний URL
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
            // ✅ Просто берем внешние ссылки на картинки (без скачивания)
            $images = json_decode($oldProduct->images ?? '[]', true) ?: [];
            $localImages = [];

            foreach ($images as $imgUrl) {
                if ($this->isValidUrl($imgUrl)) {
                    $localImages[] = $imgUrl; // Сохраняем URL как есть
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
                'images' => $localImages, // ✅ Массив внешних URL
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

    private function isValidUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    private function randomColor(): string
    {
        $colors = ['#0d6efd', '#6610f2', '#6f42c1', '#d63384', '#dc3545', '#fd7e14', '#ffc107', '#198754', '#20c997', '#0dcaf0'];
        return $colors[array_rand($colors)];
    }

    /**
     * Отключить observers для всех моделей
     */
    private function disableObservers(): void
    {
        \App\Models\Workspace::flushEventListeners();
        \App\Models\Category::flushEventListeners();
        \App\Models\Product::flushEventListeners();
        \App\Models\ProductAttribute::flushEventListeners();

        $this->command->info('⏸ Observers отключены для ускорения миграции');
    }

    /**
     * Включить observers обратно
     */
    private function enableObservers(): void
    {
        // Перерегистрируем observers (если они есть)
        if (class_exists(\App\Observers\WorkspaceObserver::class)) {
            \App\Models\Workspace::observe(\App\Observers\WorkspaceObserver::class);
        }
        if (class_exists(\App\Observers\CategoryObserver::class)) {
            \App\Models\Category::observe(\App\Observers\CategoryObserver::class);
        }
        if (class_exists(\App\Observers\ProductObserver::class)) {
            \App\Models\Product::observe(\App\Observers\ProductObserver::class);
        }

        $this->command->info('▶ Observers включены обратно');
    }
}
