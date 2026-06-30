<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Models\Workspace;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDO;
use PDOException;

class ImportFromExternalDb extends Command
{
    protected $signature = 'import:external-db
                            {--workspace= : UUID целевого workspace (обязательно)}
                            {--host=localhost : Хост внешней БД}
                            {--port=3306 : Порт внешней БД}
                            {--database= : Имя внешней БД (обязательно)}
                            {--username=root : Пользователь внешней БД}
                            {--password= : Пароль внешней БД}
                            {--table-prefix= : Префикс таблиц (например, "wp_")}
                            {--products-table=products : Имя таблицы товаров}
                            {--categories-table=categories : Имя таблицы категорий}
                            {--pivot-table=product_category : Имя pivot-таблицы}
                            {--bot-id= : Фильтр по bot_id (если нужно импортировать только определённого бота)}
                            {--sub-shop-id= : Фильтр по sub_shop_id}
                            {--update-existing : Обновлять существующие товары по SKU}
                            {--skip-images : Не импортировать изображения}
                            {--skip-categories : Не импортировать категории}
                            {--dry-run : Только показать что будет импортировано, без записи в БД}
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
    protected array $categoryMap = []; // old_id => new_id
    protected array $errors = [];

    public function handle(): int
    {
        $this->info('🚀 Импорт из внешней базы данных');
        $this->line('');

        // 1. Валидация параметров
        if (!$this->validateParams()) {
            return self::FAILURE;
        }

        // 2. Подключение к внешней БД
        if (!$this->connectToExternalDb()) {
            return self::FAILURE;
        }

        // 3. Получение целевого workspace
        $workspace = Workspace::where('uuid', $this->option('workspace'))->first();
        if (!$workspace) {
            $this->error("❌ Workspace с UUID '{$this->option('workspace')}' не найден");
            return self::FAILURE;
        }

        $this->info("📁 Целевой workspace: {$workspace->name} ({$workspace->uuid})");
        $this->line('');

        // Dry-run режим
        if ($this->option('dry-run')) {
            $this->warn('⚠️  Режим DRY-RUN — изменения не будут сохранены');
            $this->line('');
        }

        $startTime = microtime(true);

        try {
            // 4. Импорт категорий
            if (!$this->option('skip-categories')) {
                $this->importCategories($workspace);
            }

            // 5. Импорт товаров
            $this->importProducts($workspace);

            // 6. Отчёт
            $this->showReport($startTime);

        } catch (\Throwable $e) {
            $this->newLine();
            $this->error("❌ Критическая ошибка: {$e->getMessage()}");
            Log::error('External DB import failed', [
                'workspace' => $workspace->uuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return self::FAILURE;
        } finally {
            $this->disconnect();
        }

        return self::SUCCESS;
    }

    /**
     * Валидация параметров
     */
    protected function validateParams(): bool
    {
        if (!$this->option('workspace')) {
            $this->error('❌ Параметр --workspace обязателен');
            return false;
        }

        if (!$this->option('database')) {
            $this->error('❌ Параметр --database обязателен');
            return false;
        }

        $this->tablePrefix = $this->option('table-prefix');

        $this->info('🔧 Параметры подключения:');
        $this->line("   Host: {$this->option('host')}:{$this->option('port')}");
        $this->line("   Database: {$this->option('database')}");
        $this->line("   Username: {$this->option('username')}");
        $this->line("   Table prefix: " . ($this->tablePrefix ?: '(нет)'));
        $this->line('');

        return true;
    }

    /**
     * Подключение к внешней БД
     */
    protected function connectToExternalDb(): bool
    {
        $this->info('🔌 Подключение к внешней БД...');

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $this->option('host'),
            $this->option('port'),
            $this->option('database')
        );

        try {
            $this->externalPdo = new PDO(
                $dsn,
                $this->option('username'),
                $this->option('password'),
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => (int) $this->option('timeout'),
                ]
            );

            // Проверяем что таблицы существуют
            $productsTable = $this->tablePrefix . $this->option('products-table');
            $stmt = $this->externalPdo->query("SHOW TABLES LIKE '{$productsTable}'");

            if ($stmt->rowCount() === 0) {
                $this->error("❌ Таблица '{$productsTable}' не найдена в внешней БД");
                return false;
            }

            $this->info('✅ Подключение успешно');
            $this->line('');
            return true;

        } catch (PDOException $e) {
            $this->error("❌ Ошибка подключения: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Отключение от внешней БД
     */
    protected function disconnect(): void
    {
        $this->externalPdo = null;
    }

    /**
     * Импорт категорий
     */
    protected function importCategories(Workspace $workspace): void
    {
        $table = $this->tablePrefix . $this->option('categories-table');

        $this->info("📂 Импорт категорий из таблицы '{$table}'...");

        $query = "SELECT * FROM {$table}";
        $params = [];

        // Фильтр по bot_id
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
                $this->warn('   ⚠️  Категории не найдены');
                $this->line('');
                return;
            }

            $this->line("   Найдено категорий: " . count($categories));

            if (!$this->option('dry-run')) {
                $bar = $this->output->createProgressBar(count($categories));
                $bar->start();
            }

            foreach ($categories as $cat) {
                try {
                    if ($this->option('dry-run')) {
                        $this->line("   [DRY] Категория: {$cat['title']} (id: {$cat['id']})");
                        $this->categoryMap[$cat['id']] = null;
                        continue;
                    }

                    // Ищем существующую категорию по названию
                    $existing = $workspace->categories()
                        ->where('name', $cat['title'])
                        ->first();

                    if ($existing) {
                        $this->categoryMap[$cat['id']] = $existing->id;
                        if (!$this->option('dry-run')) {
                            $bar->advance();
                        }
                        continue;
                    }

                    // Создаём новую
                    $newCategory = Category::create([
                        'workspace_id' => $workspace->id,
                        'name' => $cat['title'],
                        'sort_order' => $cat['order_position'] ?? 0,
                    ]);

                    $this->categoryMap[$cat['id']] = $newCategory->id;
                    $this->importedCategories++;

                } catch (\Throwable $e) {
                    $this->errors[] = "Категория '{$cat['title']}': {$e->getMessage()}";
                }

                if (!$this->option('dry-run')) {
                    $bar->advance();
                }
            }

            if (!$this->option('dry-run')) {
                $bar->finish();
                $this->newLine(2);
            }

            $this->info("✅ Импортировано категорий: {$this->importedCategories}");
            $this->line('');

        } catch (PDOException $e) {
            $this->error("❌ Ошибка чтения категорий: {$e->getMessage()}");
        }
    }

    /**
     * Импорт товаров
     */
    protected function importProducts(Workspace $workspace): void
    {
        $table = $this->tablePrefix . $this->option('products-table');

        $this->info("📦 Импорт товаров из таблицы '{$table}'...");

        // Получаем общее количество
        $countQuery = "SELECT COUNT(*) as cnt FROM {$table} WHERE deleted_at IS NULL";
        $countParams = [];

        if ($this->option('bot-id')) {
            $countQuery .= ' AND bot_id = ?';
            $countParams[] = $this->option('bot-id');
        }

        if ($this->option('sub-shop-id')) {
            $countQuery .= ' AND sub_shop_id = ?';
            $countParams[] = $this->option('sub-shop-id');
        }

        $stmt = $this->externalPdo->prepare($countQuery);
        $stmt->execute($countParams);
        $totalCount = (int) $stmt->fetchColumn();

        if ($totalCount === 0) {
            $this->warn('   ⚠️  Товары не найдены');
            return;
        }

        $this->line("   Найдено товаров: {$totalCount}");

        $batchSize = (int) $this->option('batch-size');
        $offset = 0;

        if (!$this->option('dry-run')) {
            $bar = $this->output->createProgressBar($totalCount);
            $bar->start();
        }

        // Основной запрос
        $selectQuery = "SELECT * FROM {$table} WHERE deleted_at IS NULL";
        $selectParams = [];

        if ($this->option('bot-id')) {
            $selectQuery .= ' AND bot_id = ?';
            $selectParams[] = $this->option('bot-id');
        }

        if ($this->option('sub-shop-id')) {
            $selectQuery .= ' AND sub_shop_id = ?';
            $selectParams[] = $this->option('sub-shop-id');
        }

        $selectQuery .= ' ORDER BY id ASC LIMIT ? OFFSET ?';

        while ($offset < $totalCount) {
            $stmt = $this->externalPdo->prepare($selectQuery);
            $params = array_merge($selectParams, [$batchSize, $offset]);
            $stmt->execute($params);
            $products = $stmt->fetchAll();

            if (empty($products)) {
                break;
            }

            foreach ($products as $product) {
                try {
                    $this->importSingleProduct($workspace, $product);
                } catch (\Throwable $e) {
                    $this->failedProducts++;
                    $this->errors[] = "Товар ID {$product['id']} ({$product['title']}): {$e->getMessage()}";
                }

                if (!$this->option('dry-run')) {
                    $bar->advance();
                }
            }

            $offset += $batchSize;
        }

        if (!$this->option('dry-run')) {
            $bar->finish();
            $this->newLine(2);
        }

        $this->info("✅ Импортировано товаров: {$this->importedProducts}");
        if ($this->updatedProducts > 0) {
            $this->info("🔄 Обновлено товаров: {$this->updatedProducts}");
        }
        if ($this->skippedProducts > 0) {
            $this->warn("⏭️  Пропущено товаров: {$this->skippedProducts}");
        }
        if ($this->failedProducts > 0) {
            $this->error("❌ Ошибок при импорте: {$this->failedProducts}");
        }
    }

    /**
     * Импорт одного товара
     */
    protected function importSingleProduct(Workspace $workspace, array $product): void
    {
        $sku = $product['article'] ?? null;
        $name = $product['title'] ?? "Товар #{$product['id']}";

        // Проверяем существование по SKU
        $existing = null;
        if ($sku) {
            $existing = $workspace->products()->where('sku', $sku)->first();
        }

        // Маппинг полей
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

        // Изображения
        if (!$this->option('skip-images') && !empty($product['images'])) {
            $data['images'] = $this->parseImages($product['images']);
        } else {
            $data['images'] = [];
        }

        // Размеры
        if (!empty($product['dimension'])) {
            $data['dimensions'] = $this->parseJson($product['dimension']);
        }

        if ($this->option('dry-run')) {
            $this->line("   [DRY] Товар: {$name} (SKU: {$sku}, Цена: {$data['price']})");
            $this->importedProducts++;
            return;
        }

        if ($existing && $this->option('update-existing')) {
            // Обновляем существующий
            $existing->update($data);
            $this->updatedProducts++;
            $productId = $existing->id;
        } elseif ($existing) {
            // Пропускаем существующий
            $this->skippedProducts++;
            $productId = $existing->id;
        } else {
            // Создаём новый
            $newProduct = Product::create($data);
            $this->importedProducts++;
            $productId = $newProduct->id;
        }

        // Связываем с категориями
        $this->attachCategories($productId, $product['id']);
    }

    /**
     * Нормализация старой цены
     */
    protected function normalizeOldPrice($oldPrice, $currentPrice): ?float
    {
        $oldPrice = (float) $oldPrice;
        $currentPrice = (float) $currentPrice;

        // Если old_price = 0 или меньше current_price — возвращаем null
        if ($oldPrice <= 0 || $oldPrice <= $currentPrice) {
            return null;
        }

        return $oldPrice;
    }

    /**
     * Парсинг изображений
     */
    protected function parseImages($imagesData): array
    {
        if (empty($imagesData)) {
            return [];
        }

        // Пытаемся распарсить JSON
        $decoded = json_decode($imagesData, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            // Если это массив URL
            if (is_array($decoded)) {
                return collect($decoded)->map(function ($img) {
                    if (is_string($img)) {
                        return ['url' => $img, 'name' => basename($img)];
                    }
                    if (is_array($img)) {
                        return [
                            'url' => $img['url'] ?? $img['src'] ?? $img['path'] ?? '',
                            'name' => $img['name'] ?? $img['alt'] ?? basename($img['url'] ?? ''),
                        ];
                    }
                    return null;
                })->filter()->values()->all();
            }
        }

        // Если это просто URL (строка)
        if (is_string($imagesData) && filter_var($imagesData, FILTER_VALIDATE_URL)) {
            return [['url' => $imagesData, 'name' => basename($imagesData)]];
        }

        return [];
    }

    /**
     * Парсинг JSON
     */
    protected function parseJson($data)
    {
        if (empty($data)) {
            return null;
        }

        $decoded = json_decode($data, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }

    /**
     * Привязка товара к категориям
     */
    protected function attachCategories(int $productId, int $externalProductId): void
    {
        if (empty($this->categoryMap)) {
            return;
        }

        $pivotTable = $this->tablePrefix . $this->option('pivot-table');

        try {
            // Проверяем существование pivot-таблицы
            $stmt = $this->externalPdo->query("SHOW TABLES LIKE '{$pivotTable}'");
            if ($stmt->rowCount() === 0) {
                return;
            }

            // Получаем категории товара
            $stmt = $this->externalPdo->prepare(
                "SELECT category_id FROM {$pivotTable} WHERE product_id = ?"
            );
            $stmt->execute([$externalProductId]);
            $categoryIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (empty($categoryIds)) {
                return;
            }

            // Маппим старые ID на новые
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

        } catch (PDOException $e) {
            // Не критично — просто логируем
            $this->errors[] = "Привязка категорий товара #{$productId}: {$e->getMessage()}";
        }
    }

    /**
     * Отчёт о результатах
     */
    protected function showReport(float $startTime): void
    {
        $duration = round(microtime(true) - $startTime, 2);

        $this->newLine();
        $this->info('═══════════════════════════════════════');
        $this->info('📊 ОТЧЁТ ОБ ИМПОРТЕ');
        $this->info('═══════════════════════════════════════');
        $this->line("   ⏱  Время выполнения: {$duration} сек");
        $this->line("   📂 Категорий импортировано: {$this->importedCategories}");
        $this->line("   📦 Товаров импортировано: {$this->importedProducts}");

        if ($this->updatedProducts > 0) {
            $this->line("   🔄 Товаров обновлено: {$this->updatedProducts}");
        }
        if ($this->skippedProducts > 0) {
            $this->line("   ⏭️  Товаров пропущено: {$this->skippedProducts}");
        }
        if ($this->failedProducts > 0) {
            $this->line("   ❌ Ошибок: {$this->failedProducts}");
        }
        $this->info('═══════════════════════════════════════');

        // Сохраняем ошибки в лог
        if (!empty($this->errors)) {
            $this->newLine();
            $this->warn('⚠️  Первые 10 ошибок:');
            foreach (array_slice($this->errors, 0, 10) as $error) {
                $this->line("   • {$error}");
            }

            if (count($this->errors) > 10) {
                $this->line('   ... и ещё ' . (count($this->errors) - 10) . ' ошибок');
            }

            Log::warning('External DB import completed with errors', [
                'errors_count' => count($this->errors),
                'errors' => $this->errors,
            ]);
        }

        $this->newLine();
    }
}
