<?php

namespace App\Console\Commands;

use App\Models\Workspace;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class WorkspaceDump extends Command
{
    protected $signature = 'workspace:dump
                            {--workspace= : UUID конкретного workspace (если не указан — для всех)}
                            {--cleanup-only : Только удалить старые дампы без создания нового}
                            {--list : Показать список существующих дампов}
                            {--delete= : Удалить конкретный дамп по имени файла}';

    protected $description = 'Создание дампов workspace с автоматической очисткой старых';

    protected string $dumpsPath;
    protected int $maxAgeDays;

    public function __construct()
    {
        parent::__construct();
        $this->dumpsPath = config('workspace.dumps_path', 'dumps/workspaces');
        $this->maxAgeDays = config('workspace.dumps_max_age_days', 30);
    }

    public function handle(): int
    {
        $this->info('📦 Workspace Dump Manager');
        $this->line("   Путь: storage/app/{$this->dumpsPath}");
        $this->line("   Макс. возраст: {$this->maxAgeDays} дней");
        $this->newLine();

        // Показ списка дампов
        if ($this->option('list')) {
            return $this->listDumps();
        }

        // Удаление конкретного дампа
        if ($filename = $this->option('delete')) {
            return $this->deleteDump($filename);
        }

        // Только очистка
        if ($this->option('cleanup-only')) {
            $this->cleanup();
            return self::SUCCESS;
        }

        // Создание дампа
        $created = $this->createDump();

        // Всегда чистим старые после создания
        $this->cleanup();

        return $created ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Создание дампа
     */
    protected function createDump(): bool
    {
        $workspaceUuid = $this->option('workspace');

        if ($workspaceUuid) {
            $workspaces = Workspace::where('uuid', $workspaceUuid)->get();
            if ($workspaces->isEmpty()) {
                $this->error("❌ Workspace с UUID '{$workspaceUuid}' не найден");
                return false;
            }
        } else {
            $workspaces = Workspace::all();
        }

        if ($workspaces->isEmpty()) {
            $this->warn('⚠️  Нет workspace для дампа');
            return false;
        }

        $this->info("🔨 Создаём дампы для {$workspaces->count()} workspace...");
        $this->newLine();

        $bar = $this->output->createProgressBar($workspaces->count());
        $bar->start();

        $created = 0;
        foreach ($workspaces as $workspace) {
            try {
                $this->dumpWorkspace($workspace);
                $created++;
            } catch (\Throwable $e) {
                $this->newLine();
                $this->error("❌ Ошибка при дампе workspace '{$workspace->name}': {$e->getMessage()}");
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("✅ Создано дампов: {$created} из {$workspaces->count()}");

        return $created > 0;
    }

    /**
     * Дамп одного workspace
     */
    protected function dumpWorkspace(Workspace $workspace): void
    {
        // Загружаем все связанные данные
        $workspace->load([
            'products.categories',
            'products.attributes',
            'products.ingredients',
            'categories',
            'collections.products',
            'webhooks',
            'ingredientGroups',
        ]);

        $timestamp = now()->format('Y-m-d_H-i-s');
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $workspace->name);
        $filename = "{$timestamp}__{$workspace->uuid}__{$safeName}.json";

        $data = [
            'dump_meta' => [
                'version' => '1.0',
                'created_at' => now()->toIso8601String(),
                'workspace_uuid' => $workspace->uuid,
                'workspace_name' => $workspace->name,
                'products_count' => $workspace->products->count(),
                'categories_count' => $workspace->categories->count(),
                'collections_count' => $workspace->collections->count(),
            ],
            'workspace' => [
                'uuid' => $workspace->uuid,
                'name' => $workspace->name,
                'settings' => $workspace->settings,
                'created_at' => $workspace->created_at?->toIso8601String(),
                'updated_at' => $workspace->updated_at?->toIso8601String(),
            ],
            'categories' => $workspace->categories->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'parent_id' => $c->parent_id,
                'sort_order' => $c->sort_order,
                'created_at' => $c->created_at?->toIso8601String(),
            ])->values()->all(),
            'products' => $workspace->products->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'price' => $p->price,
                'old_price' => $p->old_price,
                'description' => $p->description,
                'dimensions' => $p->dimensions,
                'images' => $p->images,
                'is_active' => $p->is_active,
                'in_stop_list' => $p->in_stop_list,
                'categories' => $p->categories->pluck('id')->values()->all(),
                'attributes' => $p->attributes->map(fn($a) => [
                    'name' => $a->name,
                    'value' => $a->value,
                ])->values()->all(),
                'ingredients' => $p->ingredients->map(fn($i) => [
                    'id' => $i->id,
                    'name' => $i->name,
                ])->values()->all(),
                'created_at' => $p->created_at?->toIso8601String(),
                'updated_at' => $p->updated_at?->toIso8601String(),
            ])->values()->all(),
            'collections' => $workspace->collections->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'description' => $c->description,
                'product_ids' => $c->products->pluck('id')->values()->all(),
                'created_at' => $c->created_at?->toIso8601String(),
            ])->values()->all(),
            'webhooks' => $workspace->webhooks->map(fn($w) => [
                'id' => $w->id,
                'name' => $w->name,
                'url' => $w->url,
                'sync_on_update' => $w->sync_on_update,
                'last_synced_at' => $w->last_synced_at?->toIso8601String(),
                'last_status' => $w->last_status,
            ])->values()->all(),
            'ingredient_groups' => $workspace->ingredientGroups->map(fn($g) => [
                'id' => $g->id,
                'name' => $g->name,
                'ingredients' => $g->ingredients ?? [],
            ])->values()->all(),
        ];

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        Storage::disk('local')->put("{$this->dumpsPath}/{$filename}", $json);
    }

    /**
     * Очистка старых дампов
     */
    protected function cleanup(): void
    {
        $this->newLine();
        $this->info("🧹 Проверяем старые дампы (старше {$this->maxAgeDays} дней)...");

        $disk = Storage::disk('local');

        if (!$disk->exists($this->dumpsPath)) {
            $this->line('   Папка с дампами не существует');
            return;
        }

        $files = $disk->files($this->dumpsPath);
        $cutoff = Carbon::now()->subDays($this->maxAgeDays);

        $deleted = 0;
        $freedBytes = 0;

        foreach ($files as $file) {
            // Только JSON файлы
            if (!str_ends_with($file, '.json')) {
                continue;
            }

            $lastModified = Carbon::createFromTimestamp($disk->lastModified($file));

            if ($lastModified->lt($cutoff)) {
                $size = $disk->size($file);
                $disk->delete($file);
                $deleted++;
                $freedBytes += $size;

                $this->line("   🗑️  Удалён: " . basename($file) . " ({$this->formatBytes($size)}, возраст: {$lastModified->diffForHumans()})");
            }
        }

        if ($deleted === 0) {
            $this->line('   ✅ Старых дампов не найдено');
        } else {
            $this->newLine();
            $this->info("✅ Удалено: {$deleted} дампов, освобождено: {$this->formatBytes($freedBytes)}");
        }
    }

    /**
     * Список существующих дампов
     */
    protected function listDumps(): int
    {
        $disk = Storage::disk('local');

        if (!$disk->exists($this->dumpsPath)) {
            $this->warn('⚠️  Папка с дампами не существует');
            return self::SUCCESS;
        }

        $files = $disk->files($this->dumpsPath);
        $files = array_filter($files, fn($f) => str_ends_with($f, '.json'));

        if (empty($files)) {
            $this->warn('⚠️  Дампов не найдено');
            return self::SUCCESS;
        }

        $cutoff = Carbon::now()->subDays($this->maxAgeDays);

        $rows = [];
        $totalSize = 0;

        foreach ($files as $file) {
            $size = $disk->size($file);
            $modified = Carbon::createFromTimestamp($disk->lastModified($file));
            $isOld = $modified->lt($cutoff);

            $rows[] = [
                basename($file),
                $this->formatBytes($size),
                $modified->format('Y-m-d H:i'),
                $modified->diffForHumans(),
                $isOld ? '<fg=red>СТАРЫЙ</>' : '<fg=green>OK</>',
            ];
            $totalSize += $size;
        }

        // Сортировка по дате (новые сверху)
        usort($rows, fn($a, $b) => strcmp($b[2], $a[2]));

        $this->table(
            ['Файл', 'Размер', 'Создан', 'Возраст', 'Статус'],
            $rows
        );

        $this->newLine();
        $this->info("📊 Всего: " . count($rows) . " дампов, размер: {$this->formatBytes($totalSize)}");

        return self::SUCCESS;
    }

    /**
     * Удаление конкретного дампа
     */
    protected function deleteDump(string $filename): int
    {
        $disk = Storage::disk('local');
        $path = "{$this->dumpsPath}/{$filename}";

        if (!$disk->exists($path)) {
            $this->error("❌ Файл '{$filename}' не найден");
            return self::FAILURE;
        }

        if (!$this->confirm("Удалить дамп '{$filename}'?")) {
            $this->info('Отменено');
            return self::SUCCESS;
        }

        $size = $disk->size($path);
        $disk->delete($path);

        $this->info("✅ Удалён: {$filename} ({$this->formatBytes($size)})");
        return self::SUCCESS;
    }

    /**
     * Форматирование размера файла
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        $size = (float) $bytes;

        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, 2) . ' ' . $units[$i];
    }
}
