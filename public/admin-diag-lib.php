<?php

declare(strict_types=1);

/**
 * Shared helpers for admin-diag.php and admin-diag-deep.php
 */

const ADMIN_DIAG_SECRET = 'eternity-admin-2026';

function adminDiagAuth(): void
{
    if (($_GET['key'] ?? '') !== ADMIN_DIAG_SECRET) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        exit('Forbidden. Use ?key=' . ADMIN_DIAG_SECRET);
    }
}

function adminDiagInit(string $basePath): string
{
    $logDir = $basePath . '/storage/logs';
    if (! is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }

    $logFile = $logDir . '/admin-diag.log';
    diagLog($logFile, 'info', '=== Admin diagnostic run started ===', [
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'cli',
        'uri' => $_SERVER['REQUEST_URI'] ?? '',
    ]);

    return $logFile;
}

function diagLog(string $logFile, string $level, string $message, array $context = []): void
{
    $line = sprintf(
        '[%s] %s: %s',
        date('Y-m-d H:i:s'),
        strtoupper($level),
        $message
    );

    if ($context !== []) {
        $line .= ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    @file_put_contents($logFile, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
}

function diagMark(string $logFile, bool $ok, string $label, string $detail = ''): array
{
    global $hasFailure;

    if (! $ok) {
        $hasFailure = true;
        diagLog($logFile, 'error', $label, ['detail' => substr($detail, 0, 2000)]);
    } else {
        diagLog($logFile, 'info', $label, ['detail' => substr($detail, 0, 500)]);
    }

    return ['ok' => $ok, 'label' => $label, 'detail' => $detail];
}

function diagSection(string $title, array $items): void
{
    echo '<section class="block"><h2>' . htmlspecialchars($title) . '</h2>';
    foreach ($items as $item) {
        $class = $item['ok'] ? 'ok' : 'fail';
        echo '<div class="row ' . $class . '">';
        echo '<span class="badge">' . ($item['ok'] ? 'OK' : 'FAIL') . '</span>';
        echo '<div><strong>' . htmlspecialchars($item['label']) . '</strong>';
        if ($item['detail'] !== '') {
            echo '<div class="detail">' . nl2br(htmlspecialchars($item['detail'])) . '</div>';
        }
        echo '</div></div>';
    }
    echo '</section>';
}

function tableHasColumn(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([$table, $column]);

    return (int) $stmt->fetchColumn() > 0;
}

function tableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
    );
    $stmt->execute([$table]);

    return (int) $stmt->fetchColumn() > 0;
}

/**
 * Expected tables + columns from app migrations (admin + storefront).
 *
 * @return array<string, list<string>>
 */
function adminDiagExpectedSchema(): array
{
    return [
        'users' => ['id', 'name', 'email', 'phone', 'avatar', 'role', 'phone_verified_at', 'email_verified_at', 'password', 'remember_token', 'marketing_opt_in', 'is_admin', 'deleted_at', 'created_at', 'updated_at'],
        'categories' => ['id', 'parent_id', 'name', 'slug', 'image', 'description', 'meta_title', 'meta_description', 'sort_order', 'is_featured', 'is_active', 'created_at', 'updated_at'],
        'products' => ['id', 'category_id', 'name', 'slug', 'sku', 'short_description', 'description', 'price', 'sale_price', 'sale_starts_at', 'sale_ends_at', 'stock', 'allow_backorder', 'is_preorder', 'is_active', 'is_featured', 'is_new', 'is_clearance', 'weight', 'video_url', 'meta_title', 'meta_description', 'deleted_at', 'created_at', 'updated_at'],
        'product_images' => ['id', 'product_id', 'image', 'alt_text', 'sort_order', 'is_primary', 'created_at', 'updated_at'],
        'product_options' => ['id', 'product_id', 'name', 'value', 'price_adjustment', 'stock', 'sort_order', 'created_at', 'updated_at'],
        'product_reviews' => ['id', 'product_id', 'user_id', 'name', 'email', 'rating', 'title', 'body', 'image', 'is_approved', 'created_at', 'updated_at'],
        'orders' => ['id', 'order_number', 'user_id', 'customer_name', 'customer_email', 'customer_phone', 'shipping_address', 'payment_method', 'payment_status', 'shipping_method', 'tracking_number', 'carrier', 'estimated_delivery', 'status', 'subtotal', 'discount', 'coupon_code', 'shipping_cost', 'tax_amount', 'total', 'notes', 'delivered_at', 'deleted_at', 'created_at', 'updated_at'],
        'order_items' => ['id', 'order_id', 'product_id', 'product_name', 'option_summary', 'quantity', 'unit_price', 'total', 'created_at', 'updated_at'],
        'contact_buttons' => ['id', 'label', 'type', 'url', 'sort_order', 'is_active', 'created_at', 'updated_at'],
        'contact_messages' => ['id', 'name', 'email', 'phone', 'subject', 'message', 'is_read', 'created_at', 'updated_at'],
        'coupons' => ['id', 'code', 'type', 'value', 'min_order', 'max_uses', 'used_count', 'starts_at', 'ends_at', 'is_stackable', 'is_active', 'created_at', 'updated_at'],
        'gift_cards' => ['id', 'code', 'balance', 'initial_balance', 'user_id', 'expires_at', 'is_active', 'created_at', 'updated_at'],
        'home_sections' => ['id', 'title', 'slug', 'type', 'view_more_url', 'sort_order', 'is_active', 'created_at', 'updated_at'],
        'menus' => ['id', 'name', 'location', 'is_active', 'created_at', 'updated_at'],
        'menu_items' => ['id', 'menu_id', 'parent_id', 'label', 'url', 'badge', 'badge_type', 'sort_order', 'is_active', 'created_at', 'updated_at'],
        'pages' => ['id', 'title', 'slug', 'meta_title', 'meta_description', 'content', 'is_published', 'created_at', 'updated_at'],
        'sliders' => ['id', 'title', 'subtitle', 'description', 'button_text', 'button_url', 'image', 'sort_order', 'is_active', 'created_at', 'updated_at'],
        'site_settings' => ['id', 'group', 'key', 'label', 'type', 'value', 'sort_order', 'created_at', 'updated_at'],
        'newsletter_subscribers' => ['id', 'email', 'is_active', 'created_at', 'updated_at'],
        'wishlist_items' => ['id', 'user_id', 'session_id', 'product_id', 'created_at', 'updated_at'],
        'shipping_methods' => ['id', 'name', 'code', 'base_cost', 'cost_per_kg', 'min_days', 'max_days', 'is_active', 'created_at', 'updated_at'],
        'return_requests' => ['id', 'order_id', 'order_item_id', 'reason', 'status', 'refund_amount', 'created_at', 'updated_at'],
        'inventory_adjustments' => ['id', 'product_id', 'product_option_id', 'change_qty', 'reason', 'admin_user_id', 'created_at', 'updated_at'],
        'audit_logs' => ['id', 'user_id', 'action', 'auditable_type', 'auditable_id', 'old_values', 'new_values', 'created_at', 'updated_at'],
        'imports' => ['id', 'completed_at', 'file_name', 'file_path', 'importer', 'processed_rows', 'total_rows', 'successful_rows', 'user_id', 'created_at', 'updated_at'],
        'exports' => ['id', 'completed_at', 'file_disk', 'file_name', 'exporter', 'processed_rows', 'total_rows', 'successful_rows', 'user_id', 'created_at', 'updated_at'],
        'failed_import_rows' => ['id', 'import_id', 'data', 'validation_error', 'created_at', 'updated_at'],
        'sessions' => ['id', 'user_id', 'ip_address', 'user_agent', 'payload', 'last_activity'],
        'cache' => ['key', 'value', 'expiration'],
        'migrations' => ['id', 'migration', 'batch'],
    ];
}

/**
 * @return list<string>
 */
function listTableColumns(PDO $pdo, string $table): array
{
    $stmt = $pdo->prepare(
        'SELECT COLUMN_NAME FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
         ORDER BY ORDINAL_POSITION'
    );
    $stmt->execute([$table]);

    return array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

/**
 * @return array{items: list<array{ok: bool, label: string, detail: string}>, missingTables: list<string>, missingColumns: array<string, list<string>>}
 */
function validateDatabaseSchema(PDO $pdo, string $logFile): array
{
    $schema = adminDiagExpectedSchema();
    $items = [];
    $missingTables = [];
    $missingColumns = [];

    $items[] = diagMark($logFile, true, 'Schema audit', count($schema) . ' tables defined in expected schema');

    foreach ($schema as $table => $expectedColumns) {
        if (! tableExists($pdo, $table)) {
            $missingTables[] = $table;
            $items[] = diagMark(
                $logFile,
                false,
                "Table: {$table}",
                "MISSING — run: php artisan migrate --force\nOr create this table in phpMyAdmin."
            );

            continue;
        }

        $actual = listTableColumns($pdo, $table);
        $missing = array_values(array_diff($expectedColumns, $actual));

        if ($missing !== []) {
            $missingColumns[$table] = $missing;
            $items[] = diagMark(
                $logFile,
                false,
                "Columns: {$table}",
                'MISSING (' . count($missing) . '): ' . implode(', ', $missing)
                    . "\n\nFix: php artisan migrate --force"
                    . "\nOr run ALTER TABLE in phpMyAdmin for each column."
            );
        } else {
            $items[] = diagMark(
                $logFile,
                true,
                "Columns: {$table}",
                count($expectedColumns) . '/' . count($expectedColumns) . ' expected columns present'
            );
        }
    }

    if ($missingTables === [] && $missingColumns === []) {
        $items[] = diagMark($logFile, true, 'Schema summary', 'All expected tables and columns are present');
    } else {
        $summary = [];
        if ($missingTables !== []) {
            $summary[] = 'Missing tables: ' . implode(', ', $missingTables);
        }
        if ($missingColumns !== []) {
            $parts = [];
            foreach ($missingColumns as $table => $cols) {
                $parts[] = "{$table}(" . implode(', ', $cols) . ')';
            }
            $summary[] = 'Missing columns: ' . implode('; ', $parts);
        }
        $items[] = diagMark($logFile, false, 'Schema summary', implode("\n", $summary));
    }

    return [
        'items' => $items,
        'missingTables' => $missingTables,
        'missingColumns' => $missingColumns,
    ];
}

/**
 * @return list<array{ok: bool, label: string, detail: string}>
 */
function checkPendingMigrations(Illuminate\Foundation\Application $app, string $logFile): array
{
    $items = [];

    try {
        $migrator = $app->make('migrator');
        $files = $migrator->getMigrationFiles($app->basePath('database/migrations'));
        $ran = $migrator->getRepository()->getRan();
        $pending = array_values(array_diff(array_keys($files), $ran));

        if ($pending === []) {
            $items[] = diagMark($logFile, true, 'Migrations', 'All ' . count($files) . ' migration files have been run');
        } else {
            $items[] = diagMark(
                $logFile,
                false,
                'Pending migrations (' . count($pending) . ')',
                "Not yet run:\n" . implode("\n", $pending)
                    . "\n\nFix: php artisan migrate --force"
            );
        }
    } catch (Throwable $e) {
        $items[] = diagMark($logFile, false, 'Migrations check', $e->getMessage());
    }

    return $items;
}

/**
 * Smoke-test Eloquent queries for every Filament admin model.
 *
 * @return list<array{ok: bool, label: string, detail: string}>
 */
function testFilamentModelQueries(string $logFile): array
{
    $models = [
        'Category' => App\Models\Category::class,
        'Product' => App\Models\Product::class,
        'ContactButton' => App\Models\ContactButton::class,
        'ContactMessage' => App\Models\ContactMessage::class,
        'Coupon' => App\Models\Coupon::class,
        'GiftCard' => App\Models\GiftCard::class,
        'HomeSection' => App\Models\HomeSection::class,
        'Menu' => App\Models\Menu::class,
        'MenuItem' => App\Models\MenuItem::class,
        'Page' => App\Models\Page::class,
        'Slider' => App\Models\Slider::class,
        'SiteSetting' => App\Models\SiteSetting::class,
        'NewsletterSubscriber' => App\Models\NewsletterSubscriber::class,
        'Order' => App\Models\Order::class,
        'OrderItem' => App\Models\OrderItem::class,
        'ProductImage' => App\Models\ProductImage::class,
        'ProductOption' => App\Models\ProductOption::class,
        'ProductReview' => App\Models\ProductReview::class,
        'WishlistItem' => App\Models\WishlistItem::class,
        'ShippingMethod' => App\Models\ShippingMethod::class,
        'ReturnRequest' => App\Models\ReturnRequest::class,
        'InventoryAdjustment' => App\Models\InventoryAdjustment::class,
        'AuditLog' => App\Models\AuditLog::class,
        'User' => App\Models\User::class,
    ];

    $items = [];
    $failures = [];

    foreach ($models as $label => $modelClass) {
        try {
            if (! class_exists($modelClass)) {
                $items[] = diagMark($logFile, false, "Query: {$label}", "Model class not found: {$modelClass}");
                $failures[] = $label;

                continue;
            }

            $count = $modelClass::query()->limit(1)->count();
            $items[] = diagMark($logFile, true, "Query: {$label}", "SELECT OK (sample count query returned {$count})");
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            $failures[] = $label;
            $items[] = diagMark(
                $logFile,
                false,
                "Query: {$label}",
                $msg . "\n\n>>> This SQL error often causes admin list page 500 for /admin/" . strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $label)) . 's'
            );
        }
    }

    $items[] = diagMark(
        $logFile,
        $failures === [],
        'Model query summary',
        $failures === []
            ? 'All ' . count($models) . ' Filament models can query the database'
            : 'Failed models: ' . implode(', ', $failures)
    );

    return $items;
}

/**
 * Run Filament resource queries (same DB path as /admin/* list pages).
 *
 * @return list<array{ok: bool, label: string, detail: string}>
 */
function testFilamentResourceTables(Illuminate\Foundation\Application $app, string $logFile): array
{
    $items = [];

    try {
        $panel = Filament\Facades\Filament::getPanel('admin');
        $failures = [];

        foreach ($panel->getResources() as $resourceClass) {
            $shortName = class_basename($resourceClass);

            try {
                $modelClass = $resourceClass::getModel();
                $count = $resourceClass::getEloquentQuery()->limit(1)->count();
                $items[] = diagMark(
                    $logFile,
                    true,
                    "Resource query: {$shortName}",
                    "getEloquentQuery() OK — {$modelClass} (count sample: {$count})"
                );
            } catch (Throwable $e) {
                $failures[] = $shortName;
                $items[] = diagMark(
                    $logFile,
                    false,
                    "Resource query: {$shortName}",
                    $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine()
                );
            }
        }

        $items[] = diagMark(
            $logFile,
            $failures === [],
            'Filament resource query summary',
            $failures === []
                ? 'All resource list queries run without SQL errors'
                : 'Failed: ' . implode(', ', $failures)
        );
    } catch (Throwable $e) {
        $items[] = diagMark($logFile, false, 'Filament resource query scan', $e->getMessage());
    }

    return $items;
}

function diagStyles(): string
{
    return <<<'CSS'
        body { font-family: "Segoe UI", system-ui, sans-serif; background: #111; color: #eee; margin: 0; padding: 24px; }
        .wrap { max-width: 1020px; margin: 0 auto; }
        h1 { margin-top: 0; font-weight: 600; }
        h2 { font-size: 16px; margin: 0 0 12px; text-transform: uppercase; letter-spacing: .5px; color: #aaa; }
        .summary { padding: 16px; border-radius: 8px; margin-bottom: 24px; }
        .summary.ok { background: #1b4332; border: 1px solid #40916c; }
        .summary.fail { background: #4a1515; border: 1px solid #c1121f; }
        .block { background: #1a1a1a; border: 1px solid #333; border-radius: 8px; padding: 16px; margin-bottom: 18px; }
        .row { display: flex; gap: 12px; padding: 10px 0; border-bottom: 1px solid #2a2a2a; }
        .row:last-child { border-bottom: 0; }
        .badge { min-width: 48px; text-align: center; font-weight: bold; border-radius: 4px; padding: 4px 8px; height: fit-content; font-size: 12px; }
        .ok .badge { background: #2d6a4f; color: #fff; }
        .fail .badge { background: #9b2226; color: #fff; }
        .detail { color: #bbb; margin-top: 6px; white-space: pre-wrap; word-break: break-word; font-size: 13px; font-family: Consolas, "Courier New", monospace; }
        .warn { background: #3d2c00; border: 1px solid #ffb703; padding: 12px; border-radius: 8px; margin-bottom: 18px; font-size: 14px; }
        code { background: #222; padding: 2px 6px; border-radius: 4px; }
        a { color: #83B735; }
        .toolbar { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; }
        .toolbar a { background: #222; padding: 8px 14px; border-radius: 6px; text-decoration: none; font-size: 14px; }
CSS;
}

/**
 * @return array{ok: bool, status: int, detail: string}
 */
function simulateHttpRequest(
    Illuminate\Foundation\Application $app,
    string $uri,
    string $method = 'GET',
    ?App\Models\User $user = null
): array {
    if (function_exists('simulateHttpRequestDetailed')) {
        $result = simulateHttpRequestDetailed($app, $uri, $method, $user, true);

        return [
            'ok' => $result['ok'],
            'status' => $result['status'],
            'detail' => $result['detail'],
        ];
    }

    try {
        $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
        $request = Illuminate\Http\Request::create($uri, $method);
        $request->headers->set('Accept', 'text/html');

        if ($user) {
            Illuminate\Support\Facades\Auth::login($user);
        }

        $response = $kernel->handle($request);
        $status = $response->getStatusCode();
        $detail = "HTTP {$status}";

        if ($status >= 400) {
            $body = (string) $response->getContent();
            if (preg_match('/<title>([^<]+)<\/title>/i', $body, $m)) {
                $detail .= ' — ' . trim($m[1]);
            }
            $detail .= "\n" . substr(strip_tags($body), 0, 600);
        }

        $kernel->terminate($request, $response);

        if ($user) {
            Illuminate\Support\Facades\Auth::logout();
        }

        return ['ok' => $status < 400, 'status' => $status, 'detail' => trim($detail)];
    } catch (Throwable $e) {
        if ($user) {
            Illuminate\Support\Facades\Auth::logout();
        }

        return [
            'ok' => false,
            'status' => 0,
            'detail' => $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine(),
        ];
    }
}
