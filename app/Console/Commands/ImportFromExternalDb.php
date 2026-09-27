<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Models\Workspace;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PDO;
use PDOException;
use Throwable;

class ImportFromExternalDb extends Command
{
    protected $signature = 'import:external-db
                            {--workspace= : UUID целевого workspace (обязательно)}
                            {--host=localhost : Хост внешней БД}
                            {--port=3306 : Порт внешней БД}
                            {--database= : Имя внешней БД (обязательно)}
                            {--username=root : Пользователь внешней БД}
                            {--password= : Пароль внешней БД}
                            {--table-prefix= : Префикс таблиц внешней БД}
                            {--products-table=products : Имя таблицы товаров}
                            {--categories-table=categories : Имя таблицы категорий}
                            {--pivot-table=product_category : Имя pivot-таблицы во внешней БД}
                            {--bot-id= : Фильтр по bot_id}
                            {--sub-shop-id= : Фильтр по sub_shop_id}
                            {--base-url= : (Опционально) Базовый домен, если отличается от https://your-cashman.com}
                            {--update-existing : Обновлять существующие товары по SKU или external_id}
                            {--skip-categories : Не импортировать категории}
                            {--delete-missing : ⚠️ ПОЛНАЯ ОЧИСТКА: удалить ВСЕ товары в workspace перед импортом}
                            {--dry-run : Только показать что будет сделано, без записи в БД}
                            {--batch-size=100 : Размер батча для импорта}
                            {--timeout=30 : Timeout подключения в секундах}';

    protected $description = 'Полный импорт товаров и категорий с обязательной загрузкой изображений в локальный storage';

    protected ?PDO $externalPdo = null;
    protected string $tablePrefix = '';
    protected string $baseUrl = '';
    protected int $importedProducts = 0;
    protected int $updatedProducts = 0;
    protected int $skippedProducts = 0;
    protected int $failedProducts = 0;
    protected int $wipedProducts = 0;
    protected int $importedCategories = 0;
    protected array $categoryMap = [];
    protected array $importedExternalIds = [];

    protected string $errorLogPath;
    protected string $activityLogPath;
    protected int $consoleErrorCount = 0;

    public function handle(): int
    {
        $this->info('🚀 Запуск полного импорта из внешней базы данных');
        $this->line('');

        $date = date('Y-m-d');
        $this->errorLogPath = storage_path("logs/import_errors_{$date}.log");
        $this->activityLogPath = storage_path("logs/import_activity_{$date}.log");

        $this->logActivity('=== НАЧАЛО ИМПОРТА ===');

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

        if ($this->option('delete-missing')) {
            $this->wipeWorkspaceProducts($workspace);
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
            $this->error("❌ КРИТИЧЕСКАЯ ОШИБКА: {$e->getMessage()}");
            $this->logError('КРИТИЧЕСКАЯ ОШИБКА ИМПОРТА', $e);
            return self::FAILURE;
        } finally {
            $this->disconnect();
            $this->logActivity('=== ИМПОРТ ЗАВЕРШЕН ===');
        }

        return self::SUCCESS;
    }

    protected function wipeWorkspaceProducts(Workspace $workspace): void
    {
        $this->warn('⚠️  Активирован режим ПОЛНОЙ ОЧИСТКИ товаров для этого workspace!');

        if ($this->option('dry-run')) {
            $count = Product::where('workspace_id', $workspace->id)->count();
            $this->line("   [DRY-RUN] Было бы удалено товаров: {$count}");
            $this->logActivity("[DRY-RUN ОЧИСТКА] Было бы удалено товаров: {$count}");
            return;
        }

        $this->info('🧹 Удаление существующих товаров и их связей...');

        $productIds = Product::where('workspace_id', $workspace->id)->pluck('id')->toArray();

        if (!empty($productIds)) {
            $deletedPivots = DB::table('product_categories')->whereIn('product_id', $productIds)->delete();
            $this->wipedProducts = Product::where('workspace_id', $workspace->id)->delete();

            $this->info("✅ Удалено связей в категориях: {$deletedPivots}");
            $this->info("✅ Удалено товаров: {$this->wipedProducts}");
            $this->logActivity("[ОЧИСТКА] Удалено связей: {$deletedPivots}, Удалено товаров: {$this->wipedProducts}");
        } else {
            $this->info('✅ Товаров для удаления не найдено (workspace уже пуст).');
        }
        $this->line('');
    }

    protected function logActivity(string $message): void
    {
        file_put_contents($this->activityLogPath, '[' . now()->toDateTimeString() . '] ' . $message . PHP_EOL, FILE_APPEND);
    }

    protected function logError(string $context, Throwable $e): void
    {
        $this->consoleErrorCount++;

        if ($this->consoleErrorCount <= 3) {
            $this->newLine();
            $this->error("❌ ОШИБКА #{$this->consoleErrorCount}: {$context}");
            $this->error("💬 Сообщение: {$e->getMessage()}");
            $this->error("📁 Файл: {$e->getFile()} (строка {$e->getLine()})");
            $this->error("📜 Stack Trace:\n" . $e->getTraceAsString());
            $this->newLine();
        }

        $errorMessage = "[{$context}]\n";
        $errorMessage .= "Сообщение: " . $e->getMessage() . "\n";
        $errorMessage .= "Файл: " . $e->getFile() . " (строка {$e->getLine()})\n";
        $errorMessage .= "Трейс:\n" . $e->getTraceAsString() . "\n";
        $errorMessage .= str_repeat('-', 80) . "\n";

        file_put_contents($this->errorLogPath, $errorMessage, FILE_APPEND);
        Log::error("Import Error [{$context}]: " . $e->getMessage(), ['exception' => $e]);
    }

    protected function validateParams(): bool
    {
        if (!$this->option('workspace') || !$this->option('database')) {
            $this->error('❌ Параметры --workspace и --database обязательны');
            return false;
        }
        $this->tablePrefix = $this->option('table-prefix') ?? '';
        $this->baseUrl = rtrim($this->option('base-url') ?? '', '/');
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
                    $this->failedProducts++;
                    $this->logActivity("[ОШИБКА] Внешний ID: {$cat['id']} | Название: {$cat['title']} | Ошибка: {$e->getMessage()}");
                    $this->logError("Импорт категории ID: {$cat['id']}", $e);
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
                    $extId = $product['id'] ?? 'unknown';
                    $name = $product['title'] ?? 'unknown';
                    $this->logActivity("[ОШИБКА] Внешний ID: {$extId} | Название: {$name} | Ошибка: {$e->getMessage()}");
                    $this->logError("Импорт товара ID: {$extId} ({$name})", $e);
                }
                if ($bar) $bar->advance();
            }
            $offset += $batchSize;
        }

        if ($bar) { $bar->finish(); $this->newLine(2); }
    }

    protected function importSingleProduct(Workspace $workspace, array $product): void
    {
        $externalId = $product['id'];
        $sku = $product['article'] ?? null;
        $name = $product['title'] ?? "Товар #{$externalId}";

        // Отладочный вывод для первого товара, чтобы видеть формат картинок
        if ($this->importedProducts === 0 && $this->updatedProducts === 0) {
            $this->line("   🔍 DEBUG: images data = " . json_encode($product['images'] ?? null, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }

        $imageUrl = $this->extractFirstImageUrl($product['images'] ?? null);
        $this->importedExternalIds[] = $externalId;

        $existing = null;
        if ($this->option('update-existing')) {
            if ($sku) {
                $existing = Product::where('workspace_id', $workspace->id)->where('sku', $sku)->first();
            }
            if (!$existing && $externalId) {
                $existing = Product::where('workspace_id', $workspace->id)->where('external_id', $externalId)->first();
            }
        }

        $data = [
            'workspace_id' => $workspace->id,
            'name' => $name,
            'sku' => $sku,
            'external_id' => $externalId,
            'external_source' => 'external_db_' . $this->option('database'),
            'price' => (float) ($product['current_price'] ?? 0),
            'old_price' => $this->normalizeOldPrice($product['old_price'] ?? null, $product['current_price'] ?? 0),
            'description' => $product['description'] ?? null,
            'is_active' => !((bool) ($product['not_for_delivery'] ?? false)),
            'in_stop_list' => !empty($product['in_stop_list_at']),
        ];

        // 📸 ВСЕГДА обрабатываем и скачиваем картинки, если поле не пустое
        if (!empty($product['images'])) {
            $data['images'] = $this->processAndDownloadImages($product['images'], $workspace, $existing ? $existing->id : null);
        } else {
            $data['images'] = [];
        }

        if (!empty($product['dimension'])) {
            $data['dimensions'] = $this->parseJson($product['dimension']);
        }

        if ($this->option('dry-run')) {
            $this->line("   [DRY] Товар: {$name} (Внешний ID: {$externalId}, SKU: {$sku})");
            $this->logActivity("[DRY-RUN] Внешний ID: {$externalId} | Название: {$name} | Картинка: {$imageUrl} | Статус: Предпросмотр");
            $this->importedProducts++;
            return;
        }

        $action = 'Создан';
        if ($existing) {
            $existing->update($data);
            $productId = $existing->id;
            $action = $this->option('update-existing') ? 'Обновлен' : 'Пропущен (существует)';
            if ($action === 'Обновлен') {
                $this->updatedProducts++;
            } else {
                $this->skippedProducts++;
            }
        } else {
            $newProduct = Product::create($data);
            $productId = $newProduct->id;
            $this->importedProducts++;
        }

        $this->logActivity("[{$action}] Внешний ID: {$externalId} | Название: {$name} | Картинка: {$imageUrl} | Локальный ID: {$productId}");

        $this->attachCategories($productId, $externalId);
    }

    protected function processAndDownloadImages($imagesData, Workspace $workspace, ?int $productId): array
    {
        if (empty($imagesData)) return [];

        $parsed = $this->parseImages($imagesData);
        if (empty($parsed)) return [];

        $localImages = [];
        $targetProductId = $productId ?? 'temp_' . uniqid();

        foreach ($parsed as $index => $imgData) {
            $url = $imgData['url'] ?? null;
            if (!$url) continue;

            $downloadUrl = $url;

            // Если URL не начинается с http:// или https://, считаем его относительным
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                $baseDomain = !empty($this->baseUrl) ? $this->baseUrl : 'https://your-cashman.com';
                $downloadUrl = rtrim($baseDomain, '/') . '/' . ltrim($url, '/');
            }

            try {
                $response = Http::timeout(15)->get($downloadUrl);
                if ($response->successful()) {
                    $directory = storage_path("app/public/workspaces/{$workspace->uuid}/products/{$targetProductId}");
                    if (!is_dir($directory)) {
                        mkdir($directory, 0755, true);
                    }

                    $extension = pathinfo(parse_url($downloadUrl, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
                    $filename = 'img_' . $index . '_' . time() . '.' . $extension;
                    $filePath = "{$directory}/{$filename}";

                    file_put_contents($filePath, $response->body());

                    // ✅ СОХРАНЯЕМ ПОЛНЫЙ АБСОЛЮТНЫЙ URL
                    $fullUrl = url("storage/workspaces/{$workspace->uuid}/products/{$targetProductId}/{$filename}");

                    $localImages[] = [
                        'url' => $fullUrl,
                        'name' => $imgData['name'] ?? basename($downloadUrl)
                    ];
                } else {
                    $localImages[] = $imgData;
                }
            } catch (\Exception $e) {
                $localImages[] = $imgData;
            }
        }

        return $localImages;
    }

    protected function extractFirstImageUrl($imagesData): string
    {
        if (empty($imagesData)) return 'Нет';
        $parsed = $this->parseImages($imagesData);
        return $parsed[0]['url'] ?? 'Не удалось извлечь';
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

        // Пробуем распарсить как JSON
        $decoded = json_decode($imagesData, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return collect($decoded)->map(function ($img) {
                if (is_string($img)) return ['url' => $img, 'name' => basename($img)];
                if (is_array($img)) return ['url' => $img['url'] ?? $img['src'] ?? '', 'name' => $img['name'] ?? basename($img['url'] ?? '')];
                return null;
            })->filter()->values()->all();
        }

        // Если это просто строка
        if (is_string($imagesData)) {
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
        if (empty($this->categoryMap)) {
            return;
        }

        $pivotTable = $this->tablePrefix . $this->option('pivot-table');

        try {
            // 1. Проверяем существование pivot-таблицы
            $stmt = $this->externalPdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$pivotTable]);
            if ($stmt->rowCount() === 0) {
                $this->logActivity("[ПРЕДУПРЕЖДЕНИЕ] Товар ID {$productId}: Pivot-таблица '{$pivotTable}' не найдена.");
                return;
            }

            // 2. ✅ ИСПРАВЛЕНО: используем правильное имя колонки product_category_id
            $stmt = $this->externalPdo->prepare("SELECT product_category_id FROM {$pivotTable} WHERE product_id = ?");
            $stmt->execute([$externalProductId]);
            $externalCategoryIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (empty($externalCategoryIds)) {
                return; // У товара просто нет категорий, это нормально
            }

            // 3. Преобразуем старые ID категорий в новые (локальные)
            $newCategoryIds = [];
            foreach ($externalCategoryIds as $oldCatId) {
                if (isset($this->categoryMap[$oldCatId]) && $this->categoryMap[$oldCatId]) {
                    $newCategoryIds[] = $this->categoryMap[$oldCatId];
                }
            }

            // 4. Сохраняем связи в локальную БД
            if (!empty($newCategoryIds)) {
                $product = Product::find($productId);
                if ($product) {
                    $product->categories()->syncWithoutDetaching($newCategoryIds);
                    $this->logActivity("[УСПЕХ] Товар ID {$productId} (Внешний ID: {$externalProductId}) привязан к локальным категориям: " . implode(', ', $newCategoryIds));
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
        $this->info('══════════════════════════════════════════════');
        $this->info('📊 ОТЧЁТ ОБ ИМПОРТЕ');
        $this->info('══════════════════════════════════════════════');
        $this->line("   ⏱  Время выполнения: {$duration} сек");
        if ($this->wipedProducts > 0) {
            $this->error("   🗑️ УДАЛЕНО старых товаров (очистка): {$this->wipedProducts}");
        }
        $this->line("   📂 Категорий создано/найдено: {$this->importedCategories}");
        $this->line("   📦 Товаров создано: {$this->importedProducts}");
        if ($this->updatedProducts > 0) $this->line("   🔄 Товаров обновлено: {$this->updatedProducts}");
        if ($this->skippedProducts > 0) $this->line("   ⏭️ Товаров пропущено: {$this->skippedProducts}");
        if ($this->failedProducts > 0) $this->error("   ❌ Ошибок: {$this->failedProducts}");
        $this->info('══════════════════════════════════════════════');

        if ($this->failedProducts > 0) {
            $this->newLine();
            $this->warn("⚠️ Произошли ошибки. Полный стек первых 3 ошибок выведен выше.");
        }

        $this->newLine();
        $this->info("📝 Детальный лог всех операций сохранен в:");
        $this->line("   " . $this->activityLogPath);

        if ($this->failedProducts > 0) {
            $this->warn("📝 Лог ошибок сохранен в:");
            $this->line("   " . $this->errorLogPath);
        }
        $this->newLine();
    }
}
