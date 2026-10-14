<?php

/**
 * Eternity Gadget — Admin product page diagnostic (View / Edit 500)
 *
 * Upload to: public/products-diag.php
 * Visit:     https://eternitygadget.com/products-diag.php?key=eg-products-diag-7f3k9
 *            &id=78              (optional) product ID to test, defaults to the latest product
 *            &action=clear-cache (optional) runs optimize:clear + filament:clear-cached-components
 *
 * DELETE this file after the product pages are fixed.
 */

declare(strict_types=1);

const PRODUCTS_DIAG_KEY = 'eg-products-diag-7f3k9';

if (($_GET['key'] ?? '') !== PRODUCTS_DIAG_KEY) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Forbidden.');
}

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
@ini_set('display_errors', '1');
@ini_set('memaory_limit', '512M');
@set_time_limit(120);
error_reporting(E_ALL);

$basePath = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
$failures = [];

register_shutdown_function(static function (): void {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        echo "\n\n!!!!! PHP FATAL ERROR (script stopped) !!!!!\n";
        echo $error['message'] . "\n" . $error['file'] . ':' . $error['line'] . "\n";
    }
});

function section(string $title): void
{
    echo "\n" . str_repeat('=', 90) . "\n## {$title}\n" . str_repeat('=', 90) . "\n";
}

function line(bool $ok, string $label, string $detail = ''): void
{
    global $failures;

    if (! $ok) {
        $failures[] = $label;
    }

    echo '[' . ($ok ? ' OK ' : 'FAIL') . "] {$label}\n";
    if ($detail !== '') {
        echo '       ' . str_replace("\n", "\n       ", rtrim($detail)) . "\n";
    }
}

function info(string $label, string $detail = ''): void
{
    echo "[info] {$label}" . ($detail !== '' ? ': ' . $detail : '') . "\n";
}

function describeException(Throwable $e, int $traceLines = 25): string
{
    $out = [];
    $depth = 0;

    while ($e && $depth < 4) {
        $out[] = ($depth === 0 ? '' : "--- previous ---\n") . get_class($e) . ': ' . $e->getMessage();
        $out[] = 'at ' . $e->getFile() . ':' . $e->getLine();
        $trace = explode("\n", $e->getTraceAsString());
        $out[] = implode("\n", array_slice($trace, 0, $traceLines));
        $e = $e->getPrevious();
        $depth++;
    }

    return implode("\n", $out);
}

function relativePath(string $basePath, string $path): string
{
    return ltrim(str_replace([$basePath, '\\'], ['', '/'], $path), '/');
}

// -------------------------------------------------------------------------
section('1. Environment');
// -------------------------------------------------------------------------
info('Server time', date('Y-m-d H:i:s T'));
info('Base path', $basePath);
info('PHP version', PHP_VERSION . ' (' . PHP_SAPI . ')');
line(version_compare(PHP_VERSION, '8.3.0', '>='), 'PHP >= 8.3 (required by composer.lock)', PHP_VERSION);

foreach (['mbstring', 'intl', 'gd', 'fileinfo', 'pdo_mysql', 'xml', 'zip', 'bcmath', 'tokenizer', 'ctype', 'openssl', 'curl', 'iconv'] as $ext) {
    info("ext-{$ext}", extension_loaded($ext) ? 'loaded' : 'MISSING');
}

line(
    extension_loaded('dom') && class_exists('DOMDocument') && class_exists('Dom\HTMLDocument'),
    'ext-dom (DOMDocument + Dom\HTMLDocument)',
    extension_loaded('dom')
        ? 'loaded'
        : "MISSING — product View page (description HTML) and Edit page (rich editor) need it.\nEnable it in cPanel > Select PHP Version > Extensions > tick \"dom\"."
);

foreach (['mb_split', 'mb_strimwidth', 'mb_str_pad', 'mb_ucfirst'] as $fn) {
    info("function {$fn}()", function_exists($fn) ? 'available' : 'MISSING (before Laravel boot)');
}

info('opcache.enable', var_export(ini_get('opcache.enable'), true));
info('opcache.validate_timestamps', var_export(ini_get('opcache.validate_timestamps'), true));

// -------------------------------------------------------------------------
section('2. Product resource files on the server');
// -------------------------------------------------------------------------
$productsDir = $basePath . '/app/Filament/Resources/Products';

if (! is_dir($productsDir)) {
    line(false, 'app/Filament/Resources/Products exists', 'Directory is missing');
} else {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($productsDir, FilesystemIterator::SKIP_DOTS));
    $files = [];
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $files[] = $file->getPathname();
        }
    }
    sort($files);

    $declaredClasses = [];
    foreach ($files as $path) {
        $content = (string) file_get_contents($path);
        $namespace = preg_match('/^namespace\s+([^;]+);/m', $content, $m) ? trim($m[1]) : '';
        $class = preg_match('/^(?:final\s+|abstract\s+)?(?:class|trait)\s+(\w+)/m', $content, $m) ? $m[1] : '(none)';
        $fqcn = $namespace . '\\' . $class;
        $declaredClasses[$fqcn][] = relativePath($basePath, $path);

        $expected = pathinfo($path, PATHINFO_FILENAME);
        $matches = $class === $expected;

        line(
            $matches,
            relativePath($basePath, $path),
            sprintf('%d bytes, modified %s, md5 %s, declares %s', filesize($path), date('Y-m-d H:i:s', (int) filemtime($path)), md5($content), $class)
                . ($matches ? '' : "\n>>> File name does not match class name. Composer/PSR-4 cannot load it correctly — delete or rename it.")
        );
    }

    foreach ($declaredClasses as $fqcn => $paths) {
        if (count($paths) > 1) {
            line(false, "Duplicate class {$fqcn}", "Declared in:\n" . implode("\n", $paths) . "\n>>> Delete the file whose name does not match the class.");
        }
    }

    $relationManager = $productsDir . '/RelationManagers/ImagesRelationManager.php';
    line(is_file($relationManager), 'RelationManagers/ImagesRelationManager.php present', is_file($relationManager) ? '' : 'MISSING — ProductResource::getRelations() references this class. Upload it.');

    $resourceFile = $productsDir . '/ProductResource.php';
    if (is_file($resourceFile)) {
        preg_match('/function getRelations\(\): array\s*\{(.*?)\}/s', (string) file_get_contents($resourceFile), $m);
        info('ProductResource::getRelations() source', trim(preg_replace('/\s+/', ' ', $m[1] ?? '(not found)')));
    }
}

// -------------------------------------------------------------------------
section('3. Caches (bootstrap/cache)');
// -------------------------------------------------------------------------
$cacheDir = $basePath . '/bootstrap/cache';
if (is_dir($cacheDir)) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($cacheDir, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            info(relativePath($basePath, $file->getPathname()), filesize($file->getPathname()) . ' bytes, modified ' . date('Y-m-d H:i:s', (int) $file->getMTime()));
        }
    }

    foreach (['config.php', 'routes-v7.php', 'events.php'] as $cached) {
        if (is_file($cacheDir . '/' . $cached)) {
            info("Cached {$cached} present", 'if code or .env changed since it was built, run ?action=clear-cache');
        }
    }

    $filamentCache = $cacheDir . '/filament';
    if (is_dir($filamentCache)) {
        info('Filament component cache present', 'Filament will NOT pick up added/removed resource files until it is cleared');
    }
} else {
    line(false, 'bootstrap/cache exists');
}

$classmap = $basePath . '/vendor/composer/autoload_classmap.php';
if (is_file($classmap)) {
    $map = (string) file_get_contents($classmap);
    preg_match_all("/'App\\\\\\\\Filament\\\\\\\\Resources\\\\\\\\Products\\\\\\\\[^']+' => [^\n]+/", $map, $m);
    info('Composer classmap entries for Products resource', $m[0] === [] ? '(none — PSR-4 fallback)' : "\n       " . implode("\n       ", $m[0]));
}

// -------------------------------------------------------------------------
section('4. Laravel boot');
// -------------------------------------------------------------------------
$app = null;
$kernel = null;

try {
    require $basePath . '/vendor/autoload.php';
    $app = require $basePath . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $kernel->bootstrap();
    $app->instance('request', Illuminate\Http\Request::create('/admin'));
    line(true, 'Laravel bootstrap', 'Laravel ' . $app->version() . ', env=' . $app->environment() . ', debug=' . var_export(config('app.debug'), true));

    foreach (['filament/filament', 'livewire/livewire', 'laravel/framework'] as $package) {
        try {
            info($package, (string) Composer\InstalledVersions::getPrettyVersion($package));
        } catch (Throwable) {
            info($package, 'unknown');
        }
    }

    foreach (['mb_split', 'mb_strimwidth'] as $fn) {
        info("function {$fn}() after boot", function_exists($fn) ? 'available' : 'MISSING');
    }
} catch (Throwable $e) {
    line(false, 'Laravel bootstrap', describeException($e));
}

// -------------------------------------------------------------------------
section('5. Optional action');
// -------------------------------------------------------------------------
if ($app && ($_GET['action'] ?? '') === 'clear-cache') {
    foreach (['optimize:clear', 'filament:clear-cached-components'] as $command) {
        try {
            Illuminate\Support\Facades\Artisan::call($command);
            line(true, "php artisan {$command}", trim(Illuminate\Support\Facades\Artisan::output()));
        } catch (Throwable $e) {
            line(false, "php artisan {$command}", $e->getMessage());
        }
    }

    if (function_exists('opcache_reset')) {
        info('opcache_reset()', var_export(@opcache_reset(), true));
    }

    echo "\nCaches cleared. Reload this page WITHOUT &action=clear-cache to re-run the checks.\n";
    exit;
}
info('No action requested', 'add &action=clear-cache to clear Laravel + Filament caches');

if (! $app) {
    echo "\nLaravel did not boot — stopping here.\n";
    exit;
}

// -------------------------------------------------------------------------
section('6. Database schema for products');
// -------------------------------------------------------------------------
$schema = Illuminate\Support\Facades\Schema::class;
$expected = [
    'products' => ['id', 'category_id', 'name', 'slug', 'sku', 'price', 'sale_price', 'stock', 'allow_backorder', 'is_preorder', 'is_active', 'is_featured', 'is_new', 'is_clearance', 'meta_title', 'meta_description', 'deleted_at'],
    'product_images' => ['id', 'product_id', 'image', 'alt_text', 'sort_order', 'is_primary'],
    'product_options' => ['id', 'product_id', 'device_model_id', 'name', 'value', 'color_hex', 'price_adjustment', 'stock', 'sort_order'],
    'device_models' => ['id', 'brand', 'name', 'slug', 'sort_order', 'is_active'],
    'categories' => ['id', 'name'],
];

try {
    foreach ($expected as $table => $columns) {
        if (! $schema::hasTable($table)) {
            line(false, "Table {$table}", 'MISSING — run php artisan migrate --force');
            continue;
        }
        $missing = array_values(array_filter($columns, fn ($c) => ! $schema::hasColumn($table, $c)));
        line($missing === [], "Table {$table}", $missing === [] ? 'all expected columns present' : 'MISSING columns: ' . implode(', ', $missing));
    }

    $migrator = $app->make('migrator');
    $migrationFiles = $migrator->getMigrationFiles($app->databasePath('migrations'));
    $ran = $migrator->getRepository()->getRan();
    $pending = array_values(array_diff(array_keys($migrationFiles), $ran));
    line($pending === [], 'Pending migrations', $pending === [] ? 'none' : implode("\n", $pending));
} catch (Throwable $e) {
    line(false, 'Schema check', describeException($e, 8));
}

// -------------------------------------------------------------------------
section('7. Product record + relations');
// -------------------------------------------------------------------------
$product = null;
$productId = isset($_GET['id']) ? (int) $_GET['id'] : null;

try {
    $productId ??= (int) App\Models\Product::query()->latest('id')->value('id');
    info('Testing product ID', (string) $productId);

    $resource = App\Filament\Resources\Products\ProductResource::class;
    $product = $resource::getEloquentQuery()->whereKey($productId)->first();
    line($product !== null, 'ProductResource::getEloquentQuery() finds the product', $product ? $product->name : 'not found');

    if ($product) {
        info('images', (string) $product->images->count());
        info('options', (string) $product->options->count());
        info('category', (string) ($product->category?->name ?? 'none'));

        foreach ($product->options as $option) {
            info("option #{$option->id}", json_encode($option->only(['name', 'value', 'device_model_id', 'color_hex', 'price_adjustment', 'stock']), JSON_UNESCAPED_UNICODE));
        }
        foreach ($product->images as $image) {
            info("image #{$image->id}", json_encode($image->only(['image', 'is_primary', 'sort_order']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        info('description length', (string) strlen((string) $product->description));
        line(mb_check_encoding((string) $product->description, 'UTF-8'), 'description is valid UTF-8');
        line(mb_check_encoding((string) $product->short_description, 'UTF-8'), 'short_description is valid UTF-8');
    }

    foreach ($resource::getRelations() as $relation) {
        $relationClass = is_string($relation) ? $relation : get_class($relation);
        $exists = class_exists($relationClass);
        line(
            $exists,
            "Relation manager {$relationClass}",
            $exists ? 'loaded from ' . relativePath($basePath, (string) (new ReflectionClass($relationClass))->getFileName()) : 'CLASS NOT FOUND'
        );
    }
} catch (Throwable $e) {
    line(false, 'Product record checks', describeException($e));
}

// -------------------------------------------------------------------------
section('8. Simulated admin requests (real exception, even with APP_DEBUG=false)');
// -------------------------------------------------------------------------
$admin = null;

try {
    $admin = App\Models\User::query()
        ->where(fn ($q) => $q->where('is_admin', true)->orWhereIn('role', ['admin', 'staff']))
        ->orderBy('id')
        ->first();
    line($admin !== null, 'Admin user for simulation', $admin ? "#{$admin->id} {$admin->email}" : 'no admin user found');
} catch (Throwable $e) {
    line(false, 'Admin user lookup', $e->getMessage());
}

$captured = [];

try {
    $handler = $app->make(Illuminate\Contracts\Debug\ExceptionHandler::class);
    if (method_exists($handler, 'reportable')) {
        $handler->reportable(function (Throwable $e) use (&$captured) {
            $captured[] = $e;
        });
    }
} catch (Throwable) {
}

$simulate = function (string $uri) use ($app, $kernel, $admin, &$captured): void {
    $captured = [];

    try {
        if ($admin) {
            Illuminate\Support\Facades\Auth::guard('web')->setUser($admin);
        }

        $request = Illuminate\Http\Request::create($uri, 'GET');
        $request->headers->set('Accept', 'text/html');
        $app->instance('request', $request);

        $response = $kernel->handle($request);
        $status = $response->getStatusCode();
        $exception = property_exists($response, 'exception') ? $response->exception : null;
        $exception ??= $captured[0] ?? null;

        $detail = "HTTP {$status}";
        if ($status >= 300 && $status < 400) {
            $detail .= ' -> ' . $response->headers->get('Location');
        }
        if ($exception) {
            $detail .= "\n\n" . describeException($exception);
        } elseif ($status >= 400) {
            $detail .= "\n" . substr(trim(preg_replace('/\s+/', ' ', strip_tags((string) $response->getContent()))), 0, 500);
        } else {
            $detail .= ', ' . strlen((string) $response->getContent()) . ' bytes';
        }

        line($status < 400, "GET {$uri}", $detail);
    } catch (Throwable $e) {
        line(false, "GET {$uri}", describeException($e));
    }
};

if ($product && $admin) {
    $simulate('/admin/products');
    $simulate("/admin/products/{$product->id}");
    $simulate("/admin/products/{$product->id}/edit");
} else {
    line(false, 'Simulated requests skipped', 'Need both a product and an admin user');
}

// -------------------------------------------------------------------------
section('9. Render the Product Images relation manager directly (lazy-loaded in the browser)');
// -------------------------------------------------------------------------
if ($product && $admin) {
    try {
        Illuminate\Support\Facades\Auth::guard('web')->setUser($admin);
        Filament\Facades\Filament::setCurrentPanel(Filament\Facades\Filament::getPanel('admin'));

        foreach (App\Filament\Resources\Products\ProductResource::getRelations() as $relation) {
            $relationClass = is_string($relation) ? $relation : get_class($relation);
            foreach ([App\Filament\Resources\Products\Pages\ViewProduct::class, App\Filament\Resources\Products\Pages\EditProduct::class] as $pageClass) {
                try {
                    $html = Livewire\Livewire::mount($relationClass, [
                        'ownerRecord' => $product,
                        'pageClass' => $pageClass,
                        'lazy' => false,
                    ]);
                    line(true, class_basename($relationClass) . ' on ' . class_basename($pageClass), strlen((string) $html) . ' bytes rendered');
                } catch (Throwable $e) {
                    line(false, class_basename($relationClass) . ' on ' . class_basename($pageClass), describeException($e));
                }
            }
        }
    } catch (Throwable $e) {
        line(false, 'Relation manager render', describeException($e));
    }
}

// -------------------------------------------------------------------------
section('10. Latest errors in storage/logs');
// -------------------------------------------------------------------------
$logFiles = glob($basePath . '/storage/logs/laravel*.log') ?: [];
usort($logFiles, fn ($a, $b) => filemtime($b) <=> filemtime($a));

foreach (array_slice($logFiles, 0, 2) as $logPath) {
    $size = filesize($logPath);
    info(relativePath($basePath, $logPath), $size . ' bytes, modified ' . date('Y-m-d H:i:s', (int) filemtime($logPath)));

    $handle = fopen($logPath, 'rb');
    if (! $handle) {
        continue;
    }
    fseek($handle, max(0, $size - 400_000));
    $tail = (string) stream_get_contents($handle);
    fclose($handle);

    preg_match_all('/^\[\d{4}-\d{2}-\d{2}[^\]]*\] \w+\.(?:ERROR|CRITICAL|ALERT|EMERGENCY): .*?(?=^\[\d{4}-\d{2}-\d{2}|\z)/ms', $tail, $m);
    $entries = array_slice(array_reverse($m[0]), 0, 5);

    if ($entries === []) {
        info('  no ERROR entries in the last 400 KB');
    }

    foreach ($entries as $entry) {
        $lines = explode("\n", trim($entry));
        echo "\n  " . implode("\n  ", array_slice($lines, 0, 18)) . "\n";
    }
}

// -------------------------------------------------------------------------
section('11. Product image storage (/storage/products/... 404)');
// -------------------------------------------------------------------------
try {
    $publicDisk = Illuminate\Support\Facades\Storage::disk('public');
    $diskRoot = (string) config('filesystems.disks.public.root');
    $diskRootReal = realpath($diskRoot) ?: '(does not exist)';

    info('APP_URL', (string) config('app.url'));
    info('public disk root (config)', $diskRoot);
    info('public disk root (realpath)', $diskRootReal);
    info('public disk url', (string) config('filesystems.disks.public.url'));
    info('default filesystem disk', (string) config('filesystems.default'));
    info('livewire temporary_file_upload', json_encode(config('livewire.temporary_file_upload'), JSON_UNESCAPED_SLASHES));
    line(is_dir($diskRoot) && is_writable($diskRoot), 'public disk root exists + writable', $diskRoot);

    $productsPath = $diskRoot . '/products';
    $productFiles = is_dir($productsPath) ? (glob($productsPath . '/*') ?: []) : [];
    usort($productFiles, fn ($a, $b) => filemtime($b) <=> filemtime($a));
    line(is_dir($productsPath) && is_writable($productsPath), 'storage/app/public/products exists + writable', count($productFiles) . ' files');
    foreach (array_slice($productFiles, 0, 5) as $file) {
        info('  newest file', basename($file) . ' — ' . filesize($file) . ' bytes, ' . date('Y-m-d H:i:s', (int) filemtime($file)));
    }

    $link = public_path('storage');
    $linkInfo = match (true) {
        is_link($link) => 'SYMLINK -> ' . readlink($link) . ' (resolves to ' . (realpath($link) ?: 'NOTHING — broken link') . ')',
        is_dir($link) => 'REAL DIRECTORY (not a symlink) — web server serves files from here first',
        default => 'missing',
    };
    info('public/storage', $linkInfo);
    $linkOk = ! file_exists($link) || (is_link($link) && realpath($link) === realpath($diskRoot));
    line($linkOk, 'public/storage points at the public disk root', $linkOk ? '' : 'public/storage does not resolve to ' . $diskRootReal);

    if (is_dir($link) && ! is_link($link)) {
        $realDirFiles = glob($link . '/products/*') ?: [];
        info('  files in public/storage/products (real dir)', (string) count($realDirFiles));
    }

    $searchRoots = array_unique(array_filter([
        $diskRoot,
        storage_path('app'),
        storage_path('app/private'),
        public_path('storage'),
        public_path(),
        '/home/babuntoo/eternitygadget.shop/storage/app/public',
        '/home/babuntoo/eternitygadget.shop/public/storage',
        '/home/babuntoo/public_html/storage',
    ], 'is_dir'));

    $findFile = function (string $basename) use ($searchRoots): array {
        $found = [];
        foreach ($searchRoots as $root) {
            foreach (['', '/products', '/livewire-tmp', '/storage/products'] as $sub) {
                $candidate = $root . $sub . '/' . $basename;
                if (is_file($candidate)) {
                    $found[] = (realpath($candidate) ?: $candidate) . ' (' . filesize($candidate) . ' bytes)';
                }
            }
        }

        return array_values(array_unique($found));
    };

    $recentImages = App\Models\ProductImage::query()->latest('id')->limit(12)->get(['id', 'product_id', 'image', 'created_at']);
    $missingCount = 0;
    foreach ($recentImages as $row) {
        $path = App\Support\MediaPath::normalize($row->image) ?? '';
        $exists = $path !== '' && $publicDisk->exists($path);
        $missingCount += $exists ? 0 : 1;
        $elsewhere = $exists ? [] : $findFile(basename($path));
        line(
            $exists,
            "product_images #{$row->id} (product {$row->product_id}, {$row->created_at})",
            "db path: {$row->image}"
                . ($exists ? '' : "\nNOT on public disk at {$diskRoot}/{$path}")
                . ($elsewhere !== [] ? "\nFOUND ELSEWHERE:\n  " . implode("\n  ", $elsewhere) : ($exists ? '' : "\nnot found in: " . implode(', ', $searchRoots)))
        );
    }
    info('Recent image rows missing on disk', "{$missingCount} / {$recentImages->count()}");

    if (! empty($_GET['file'])) {
        $basename = basename((string) $_GET['file']);
        $hits = $findFile($basename);
        line($hits !== [], "Search for {$basename}", $hits === [] ? 'not found in: ' . implode(', ', $searchRoots) : implode("\n", $hits));
    }

    $probe = 'products/.diag-write-test-' . bin2hex(random_bytes(4)) . '.txt';
    $written = $publicDisk->put($probe, 'ok');
    $absolute = $diskRoot . '/' . $probe;
    line(
        $written && is_file($absolute),
        'Write test on public disk',
        ($written ? 'put() returned true' : 'put() returned FALSE') . '; file on disk: ' . (is_file($absolute) ? 'yes' : 'NO') . " ({$absolute})"
    );
    $publicDisk->delete($probe);

    $tmpDirs = [storage_path('app/private/livewire-tmp'), storage_path('app/livewire-tmp'), $diskRoot . '/livewire-tmp'];
    foreach ($tmpDirs as $tmp) {
        if (is_dir($tmp)) {
            $tmpFiles = glob($tmp . '/*') ?: [];
            info(relativePath($basePath, $tmp), count($tmpFiles) . ' temp upload files, writable=' . var_export(is_writable($tmp), true));
        }
    }
} catch (Throwable $e) {
    line(false, 'Image storage checks', describeException($e, 10));
}

$mediaLogs = glob($basePath . '/storage/logs/media-upload*.log') ?: [];
usort($mediaLogs, fn ($a, $b) => filemtime($b) <=> filemtime($a));
if ($mediaLogs !== []) {
    $latest = $mediaLogs[0];
    info('Latest media log', relativePath($basePath, $latest) . ', modified ' . date('Y-m-d H:i:s', (int) filemtime($latest)));
    $size = filesize($latest);
    $handle = fopen($latest, 'rb');
    fseek($handle, max(0, $size - 300_000));
    $tail = (string) stream_get_contents($handle);
    fclose($handle);
    $interesting = array_filter(explode("\n", $tail), fn ($l) => preg_match('/(ERROR|WARNING|product\.sync|product\.after|storage\.|extract_paths\.success)/', $l));
    foreach (array_slice($interesting, -25) as $logLine) {
        echo '  ' . substr($logLine, 0, 600) . "\n";
    }
} else {
    info('Media logs', 'none found in storage/logs');
}

// -------------------------------------------------------------------------
section('Summary');
// -------------------------------------------------------------------------
echo $failures === [] ? "No failures detected.\n" : count($failures) . " failure(s):\n - " . implode("\n - ", array_unique($failures)) . "\n";
