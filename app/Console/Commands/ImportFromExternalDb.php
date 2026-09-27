<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Models\Workspace;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PDO;
use PDOException;
use Throwable;

class ImportFromExternalDb extends Command
{
    protected $signature = 'import:external-db
                            {--workspace= : UUID целевого workspace (об обязательно)}
                            {--host=localhost : Хост внешней БД}
                            {--port=3306 : Порт внешней БД}
                            {--database= : Имя внешней БД (обязательно)}
                            {--username=root : Пользователь внешней БД}
                            {--password= : Пароль внешней БД}
                            {--table-prefix= : Префикс таблиц}
                            {--products-table=products : Имя таблицы товаров}
                            {--categories-table=categories : Имя таблицы категорий}
                            {--pivot-table=product_category : Имя pivot-таблицы}
                            {--bot-id= : Фильтр по bot_id}
                            {--sub-shop-id= : Фильтр по sub_shop_id}
                            {--update-existing : Обновлять существующие товары по SKU}
                            {--skip-images : Не импортировать изображения}
                            {--skip-categories : Не импортировать категории}
                            {--dry-run : Только показать что будет импортировано}
                            {--batch-size=100 : Размер батча для импорта}
                            {--timeout=30 : Timeout подключения в секундах}';

    protected $description = 'Импорт товаров и категорий из внешней базы данных';

    protected ?PDO $externalPdo = null;
    protected string $tablePrefix = '';
    protected int $importedProducts = 0;
    protected int $updatedProducts = 0;
    protected int $skippedProducts = 0;
    protected int $failedProducts = 0;
    protected int $importedCategories = 0;
    protected array $categoryMap = [];

    // Путь к простому текстовому логу
    protected string $errorLogPath;

    public function handle(): int
    {
        $this->info('🚀 Импорт из внешней базы данных');
        $this->line('');

        // Инициализация простого текстового лога на сегодня
        $this->errorLogPath = storage_path('logs/import_errors_' . date('Y-m-d') . '.log');
        file_put_contents($this->errorLogPath, "\n" . str_repeat('=', 80) . "\n", FILE_APPEND);
        file_put_contents($this->errorLogPath, "Начало импорта: " . now()->toDateTimeString() . "\n", FILE_APPEND);
        file_put_contents($this->errorLogPath, "Workspace: " . $this->option('workspace') . "\n", FILE_APPEND);
        file_put_contents($this->errorLogPath, str_repeat('=', 80) . "\n", FILE_APPEND);

        if (!$this->validateParams()) return self::FAILURE;
        if (!$this->connectToExternalDb()) return self::FAILURE;

        $workspace = Workspace::where('uuid', $this->option('workspace'))->first();
        if (!$workspace) {
            $this->error("❌ Workspace с UUID '{$this->option('workspace')}' не найден");
            return self::FAILURE;
        }

        $this->info("📁 Целевой workspace: {$workspace->name} ({$workspace->uuid})");
        $this->line('');

        if ($this->option('dry-run')) {
            $this->warn('⚠️  Режим DRY-RUN — изменения не будут сохранены');
            $this->line('');
        }

        $startTime = microtime(true);

        try {
            if (!$this->option('skip-categories')) {
                $this->importCategories($workspace);
            }
            $this->importProducts($workspace);
            $this->showReport($startTime);
        } catch (Throwable $e) {
            $this->newLine();
            $this->error("❌ Критическая ошибка: {$e->getMessage()}");
            $this->logError('КРИТИЧЕСКАЯ ОШИБКА ИМПОРТА', $e);
            return self::FAILURE;
        } finally {
            $this->disconnect();
        }

        return self::SUCCESS;
    }

    /**
     * Записывает полный текст ошибки и трейс в обычный текстовый файл
     */
    protected function logError(string $context, Throwable $e): void
    {
        $errorMessage = "[{$context}]\n";
        $errorMessage .= "Сообщение: " . $e->getMessage() . "\n";
        $errorMessage .= "Файл: " . $e->getFile() . " (строка {$e->getLine()})\n";
        $errorMessage .= "Трейс (Stack Trace):\n" . $e->getTraceAsString() . "\n";
        $errorMessage .= str_repeat('-', 80) . "\n";

        // Пишем в наш специальный файл
        file_put_contents($this->errorLogPath, $errorMessage, FILE_APPEND);

        // Дублируем в стандартный лог Laravel для надёжности
        Log::error("Import Error [{$context}]: " . $e->getMessage(), ['exception' => $e]);
    }

    protected function validateParams(): bool
    {
        if (!$this->option('workspace') || !$this->option('database')) {
            $this->error('❌ Параметры --workspace и --database обязательны');
            return false;
        }

        $this->tablePrefix = $this->option('table-prefix') ?? '';
        return true;
    }

    protected function connectToExternalDb(): bool
    {
        $this->info('🔌 Подключение к внешней БД...');
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $this->option('host'), $this->option('port'), $this->option('database')
        );

        try {
            $this->externalPdo = new PDO($dsn, $this->option('username'), $this->option('password'), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => (int) $this->option('timeout'),
            ]);

            $productsTable = $this->tablePrefix . $this->option('products-table');
            $stmt = $this->externalPdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$productsTable]);

            if ($stmt->rowCount() === 0) {
                $this->error("❌ Таблица '{$productsTable}' не найдена");
                return false;
            }

            $this->info('✅ Подключение успешно');
            return true;
        } catch (PDOException $e) {
            $this->error("❌ Ошибка подключения: {$e->getMessage()}");
            $this->logError('Подключение к БД', $e);
            return false;
        }
    }

    protected function disconnect(): void { $this->externalPdo = null; }

    protected function importCategories(Workspace $workspace): void
    {
        $table = $this->tablePrefix . $this->option('categories-table');
        $this->info("📂 Импорт категорий из таблицы '{$table}'...");

        $stmtCheck = $this->externalPdo->prepare("SHOW TABLES LIKE ?");
        $stmtCheck->execute([$table]);
        if ($stmtCheck->rowCount() === 0) {
            $this->warn("⚠️ Таблица '{$table}' не найдена. Пропускаем категории.");
            return;
        }

        $query = "SELECT * FROM {$table}";
        $params = [];
        if ($this->option('bot-id')) {
            $query .= ' WHERE bot_id = ?';
            $params[] = $this->option('bot-id');
        }
        $query .= ' ORDER BY order_position ASC, id ASC';

        try {
            $stmt = $this->externalPdo->prepare($query);
            $stmt->execute($params);
            $categories = $stmt->fetchAll();

            if (empty($categories)) {
                $this->warn('   ⚠️ Категории не найдены');
                return;
            }

            $this->line("   Найдено категорий: " . count($categories));
            $bar = $this->option('dry-run') ? null : $this->output->createProgressBar(count($categories));
            if ($bar) $bar->start();

            foreach ($categories as $cat) {
                try {
                    if ($this->option('dry-run')) {
                        $this->line("   [DRY] Категория: {$cat['title']} (id: {$cat['id']})");
                        $this->categoryMap[$cat['id']] = null;
                        continue;
                    }

                    $existing = $workspace->categories()->where('name', $cat['title'])->first();
                    if ($existing) {
                        $this->categoryMap[$cat['id']] = $existing->id;
                    } else {
                        $newCategory = Category::create([
                            'workspace_id' => $workspace->id,
                            'name' => $cat['title'],
                            'sort_order' => $cat['order_position'] ?? 0,
                        ]);
                        $this->categoryMap[$cat['id']] = $newCategory->id;
                        $this->importedCategories++;
                    }
                } catch (Throwable $e) {
                    $this->failedProducts++; // считаем как общую ошибку
                    $this->logError("Импорт категории ID: {$cat['id']} ({$cat['title']})", $e);
                }
                if ($bar) $bar->advance();
            }

            if ($bar) { $bar->finish(); $this->newLine(2); }
            $this->info("✅ Импортировано категорий: {$this->importedCategories}");

        } catch (PDOException $e) {
            $this->error("❌ Ошибка чтения категорий: {$e->getMessage()}");
            $this->logError('Запрос категорий из внешней БД', $e);
        }
    }

    protected function importProducts(Workspace $workspace): void
    {
        $table = $this->tablePrefix . $this->option('products-table');
        $this->info("📦 Импорт товаров из таблицы '{$table}'...");

        $countQuery = "SELECT COUNT(*) as cnt FROM {$table} WHERE deleted_at IS NULL";
        $countParams = [];
        if ($this->option('bot-id')) { $countQuery .= ' AND bot_id = ?'; $countParams[] = $this->option('bot-id'); }
        if ($this->option('sub-shop-id')) { $countQuery .= ' AND sub_shop_id = ?'; $countParams[] = $this->option('sub-shop-id'); }

        $stmt = $this->externalPdo->prepare($countQuery);
        $stmt->execute($countParams);
        $totalCount = (int) $stmt->fetchColumn();

        if ($totalCount === 0) {
            $this->warn('   ⚠️ Товары не найдены');
            return;
        }

        $this->line("   Найдено товаров: {$totalCount}");
        $batchSize = (int) $this->option('batch-size');
        $offset = 0;

        $bar = $this->option('dry-run') ? null : $this->output->createProgressBar($totalCount);
        if ($bar) $bar->start();

        $selectQuery = "SELECT * FROM {$table} WHERE deleted_at IS NULL";
        $selectParams = [];
        if ($this->option('bot-id')) { $selectQuery .= ' AND bot_id = ?'; $selectParams[] = $this->option('bot-id'); }
        if ($this->option('sub-shop-id')) { $selectQuery .= ' AND sub_shop_id = ?'; $selectParams[] = $this->option('sub-shop-id'); }
        $selectQuery .= ' ORDER BY id ASC';

        while ($offset < $totalCount) {
            $pagedQuery = $selectQuery . " LIMIT {$batchSize} OFFSET {$offset}";
            $stmt = $this->externalPdo->prepare($pagedQuery);
            $stmt->execute($selectParams);
            $products = $stmt->fetchAll();

            if (empty($products)) break;

            foreach ($products as $product) {
                try {
                    $this->importSingleProduct($workspace, $product);
                } catch (Throwable $e) {
                    $this->failedProducts++;
                    $this->logError("Импорт товара ID: {$product['id']} ({$product['title']})", $e);
                }
                if ($bar) $bar->advance();
            }
            $offset += $batchSize;
        }

        if ($bar) { $bar->finish(); $this->newLine(2); }

        $this->info("✅ Импортировано товаров: {$this->importedProducts}");
        if ($this->updatedProducts > 0) $this->info("🔄 Обновлено товаров: {$this->updatedProducts}");
        if ($this->skippedProducts > 0) $this->warn("⏭️ Пропущено товаров: {$this->skippedProducts}");
        if ($this->failedProducts > 0) $this->error("❌ Ошибок при импорте: {$this->failedProducts}");
    }

    protected function importSingleProduct(Workspace $workspace, array $product): void
    {
        $sku = $product['article'] ?? null;
        $name = $product['title'] ?? "Товар #{$product['id']}";

        $existing = null;
        if ($sku) {
            $existing = Product::where('workspace_id', $workspace->id)->where('sku', $sku)->first();
        }

        $data = [
            'workspace_id' => $workspace->id,
            'name' => $name,
            'sku' => $sku,
            'price' => (float) ($product['current_price'] ?? 0),
            'old_price' => $this->normalizeOldPrice($product['old_price'] ?? null, $product['current_price'] ?? 0),
            'description' => $product['description'] ?? null,
            'is_active' => !((bool) ($product['not_for_delivery'] ?? false)),
            'in_stop_list' => !empty($product['in_stop_list_at']),
        ];

        if (!$this->option('skip-images') && !empty($product['images'])) {
            $data['images'] = $this->parseImages($product['images']);
        } else {
            $data['images'] = [];
        }

        if (!empty($product['dimension'])) {
            $data['dimensions'] = $this->parseJson($product['dimension']);
        }

        if ($this->option('dry-run')) {
            $this->line("   [DRY] Товар: {$name} (SKU: {$sku})");
            $this->importedProducts++;
            return;
        }

        if ($existing && $this->option('update-existing')) {
            $existing->update($data);
            $this->updatedProducts++;
            $productId = $existing->id;
        } elseif ($existing) {
            $this->skippedProducts++;
            $productId = $existing->id;
        } else {
            $newProduct = Product::create($data);
            $this->importedProducts++;
            $productId = $newProduct->id;
        }

        $this->attachCategories($productId, $product['id']);
    }

    protected function normalizeOldPrice($oldPrice, $currentPrice): ?float
    {
        $oldPrice = (float) $oldPrice;
        $currentPrice = (float) $currentPrice;
        return ($oldPrice > 0 && $oldPrice > $currentPrice) ? $oldPrice : null;
    }

    protected function parseImages($imagesData): array
    {
        if (empty($imagesData)) return [];
        $decoded = json_decode($imagesData, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return collect($decoded)->map(function ($img) {
                if (is_string($img)) return ['url' => $img, 'name' => basename($img)];
                if (is_array($img)) return ['url' => $img['url'] ?? $img['src'] ?? '', 'name' => $img['name'] ?? basename($img['url'] ?? '')];
                return null;
            })->filter()->values()->all();
        }
        if (is_string($imagesData) && filter_var($imagesData, FILTER_VALIDATE_URL)) {
            return [['url' => $imagesData, 'name' => basename($imagesData)]];
        }
        return [];
    }

    protected function parseJson($data)
    {
        if (empty($data)) return null;
        $decoded = json_decode($data, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }

    protected function attachCategories(int $productId, int $externalProductId): void
    {
        if (empty($this->categoryMap)) return;

        $pivotTable = $this->tablePrefix . $this->option('pivot-table');
        try {
            $stmt = $this->externalPdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$pivotTable]);
            if ($stmt->rowCount() === 0) return;

            $stmt = $this->externalPdo->prepare("SELECT category_id FROM {$pivotTable} WHERE product_id = ?");
            $stmt->execute([$externalProductId]);
            $categoryIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $newCategoryIds = [];
            foreach ($categoryIds as $oldCatId) {
                if (isset($this->categoryMap[$oldCatId]) && $this->categoryMap[$oldCatId]) {
                    $newCategoryIds[] = $this->categoryMap[$oldCatId];
                }
            }

            if (!empty($newCategoryIds)) {
                $product = Product::find($productId);
                if ($product) {
                    $product->categories()->syncWithoutDetaching($newCategoryIds);
                }
            }
        } catch (Throwable $e) {
            $this->logError("Привязка категорий к товару ID: {$productId}", $e);
        }
    }

    protected function showReport(float $startTime): void
    {
        $duration = round(microtime(true) - $startTime, 2);
        $this->newLine();
        $this->info('═══════════════════════════════════════');
        $this->info('📊 ОТЧЁТ ОБ ИМПОРТЕ');
        $this->info('═══════════════════════════════════════');
        $this->line("   ⏱  Время: {$duration} сек");
        $this->line("   📂 Категорий: {$this->importedCategories}");
        $this->line("   📦 Товаров создано: {$this->importedProducts}");
        if ($this->updatedProducts > 0) $this->line("   🔄 Товаров обновлено: {$this->updatedProducts}");
        if ($this->skippedProducts > 0) $this->line("   ⏭️ Товаров пропущено: {$this->skippedProducts}");
        if ($this->failedProducts > 0) $this->line("   ❌ Ошибок: {$this->failedProducts}");
        $this->info('═══════════════════════════════════════');

        if ($this->failedProducts > 0) {
            $this->newLine();
            $this->warn("⚠️ Произошли ошибки. Полный текст всех ошибок с трейсом записан в файл:");
            $this->line("📄 " . $this->errorLogPath);
        }
        $this->newLine();
    }
}
