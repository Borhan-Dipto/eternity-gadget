<?php

/**
 * Eternity Gadget — Server Diagnostic Tool
 *
 * 1. Upload this file to your Laravel "public" folder on the server.
 * 2. Visit: https://eternitygadget.shop/diag.php
 * 3. DELETE this file immediately after fixing issues.
 */

declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');

$basePath = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
$results = [];
$hasFailure = false;

function mark(bool $ok, string $label, string $detail = ''): array
{
    global $hasFailure;

    if (! $ok) {
        $hasFailure = true;
    }

    return [
        'ok' => $ok,
        'label' => $label,
        'detail' => $detail,
    ];
}

function section(string $title, array $items): void
{
    global $hasFailure;

    echo '<section class="block">';
    echo '<h2>' . htmlspecialchars($title) . '</h2>';

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

// -------------------------------------------------------------------------
// 1. Basic server info
// -------------------------------------------------------------------------
$phpOk = version_compare(PHP_VERSION, '8.2.0', '>=');
$results['server'][] = mark($phpOk, 'PHP version >= 8.2', 'Current: ' . PHP_VERSION);
$results['server'][] = mark(true, 'Server software', $_SERVER['SERVER_SOFTWARE'] ?? 'unknown');
$results['server'][] = mark(true, 'Document root', $_SERVER['DOCUMENT_ROOT'] ?? 'unknown');
$results['server'][] = mark(true, 'This script path', __FILE__);
$results['server'][] = mark(true, 'Laravel base path', $basePath);

$sapi = PHP_SAPI;
$results['server'][] = mark(true, 'PHP SAPI', $sapi);

// -------------------------------------------------------------------------
// 2. Required PHP extensions
// -------------------------------------------------------------------------
$requiredExtensions = [
    'bcmath', 'ctype', 'curl', 'dom', 'fileinfo', 'filter', 'hash',
    'mbstring', 'openssl', 'pcre', 'pdo', 'session', 'tokenizer', 'xml', 'intl',
];

foreach ($requiredExtensions as $ext) {
    $loaded = extension_loaded($ext);
    $results['extensions'][] = mark(
        $loaded,
        "Extension: {$ext}",
        $loaded ? 'Loaded' : 'Missing — ask host to enable it'
    );
}

$pdoDrivers = PDO::getAvailableDrivers();
$results['extensions'][] = mark(
    in_array('mysql', $pdoDrivers, true),
    'PDO MySQL driver',
    'Available drivers: ' . (empty($pdoDrivers) ? 'none' : implode(', ', $pdoDrivers))
);

// -------------------------------------------------------------------------
// 3. Important files and folders
// -------------------------------------------------------------------------
$paths = [
    'vendor/autoload.php' => $basePath . '/vendor/autoload.php',
    '.env file' => $basePath . '/.env',
    'artisan' => $basePath . '/artisan',
    'bootstrap/app.php' => $basePath . '/bootstrap/app.php',
    'public/index.php' => $basePath . '/public/index.php',
    'public/.htaccess' => $basePath . '/public/.htaccess',
    'storage/' => $basePath . '/storage',
    'storage/logs/' => $basePath . '/storage/logs',
    'storage/framework/' => $basePath . '/storage/framework',
    'storage/framework/cache/' => $basePath . '/storage/framework/cache',
    'storage/framework/sessions/' => $basePath . '/storage/framework/sessions',
    'storage/framework/views/' => $basePath . '/storage/framework/views',
    'bootstrap/cache/' => $basePath . '/bootstrap/cache',
    'storage/app/public/branding/eternity-logo.png' => $basePath . '/storage/app/public/branding/eternity-logo.png',
    'public/storage/branding/eternity-logo.png' => $basePath . '/public/storage/branding/eternity-logo.png',
];

foreach ($paths as $label => $path) {
    $exists = file_exists($path);
    $detail = $exists ? $path : 'Missing at: ' . $path;
    $results['files'][] = mark($exists, $label, $detail);
}

// Writable checks
$writablePaths = [
    'storage/' => $basePath . '/storage',
    'storage/logs/' => $basePath . '/storage/logs',
    'bootstrap/cache/' => $basePath . '/bootstrap/cache',
];

foreach ($writablePaths as $label => $path) {
    $writable = is_dir($path) && is_writable($path);
    $results['permissions'][] = mark(
        $writable,
        "{$label} writable",
        $writable ? 'Writable' : 'NOT writable — set permissions to 775 in File Manager'
    );
}

// -------------------------------------------------------------------------
// 4. Read .env safely (without exposing secrets)
// -------------------------------------------------------------------------
$envPath = $basePath . '/.env';
$envValues = [];

if (is_readable($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value, "\"'");
        $envValues[$key] = $value;
    }
}

$appKey = $envValues['APP_KEY'] ?? '';
$results['env'][] = mark(
    is_readable($envPath),
    '.env readable',
    is_readable($envPath) ? $envPath : 'Cannot read .env'
);
$results['env'][] = mark(
    $appKey !== '' && $appKey !== 'base64:',
    'APP_KEY is set',
    $appKey !== '' ? 'Present' : 'Empty — run: php artisan key:generate --show'
);
$results['env'][] = mark(
    ($envValues['APP_URL'] ?? '') !== '',
    'APP_URL',
    $envValues['APP_URL'] ?? 'missing'
);
$results['env'][] = mark(
    ($envValues['DB_CONNECTION'] ?? '') === 'mysql',
    'DB_CONNECTION should be mysql on cPanel',
    'Current: ' . ($envValues['DB_CONNECTION'] ?? 'missing') . ' — do NOT use sqlite on shared hosting'
);
$results['env'][] = mark(
    ($envValues['SESSION_DRIVER'] ?? '') !== 'database' || ($envValues['DB_CONNECTION'] ?? '') === 'mysql',
    'SESSION_DRIVER',
    'Current: ' . ($envValues['SESSION_DRIVER'] ?? 'missing') . ' — use "file" until migrations are done'
);
$results['env'][] = mark(
    ($envValues['CACHE_STORE'] ?? '') !== 'database' || ($envValues['DB_CONNECTION'] ?? '') === 'mysql',
    'CACHE_STORE',
    'Current: ' . ($envValues['CACHE_STORE'] ?? 'missing') . ' — use "file" until migrations are done'
);
$results['env'][] = mark(
    ($envValues['FILESYSTEM_DISK'] ?? '') === 'public',
    'FILESYSTEM_DISK should be public',
    'Current: ' . ($envValues['FILESYSTEM_DISK'] ?? 'missing')
);

$dbName = $envValues['DB_DATABASE'] ?? '';
$dbUser = $envValues['DB_USERNAME'] ?? '';
$dbPass = $envValues['DB_PASSWORD'] ?? '';
$dbHost = $envValues['DB_HOST'] ?? 'localhost';

$results['env'][] = mark(
    $dbName !== '' && $dbUser !== '',
    'MySQL credentials present',
    'DB: ' . ($dbName ?: 'missing') . ' | User: ' . ($dbUser ?: 'missing')
);

// -------------------------------------------------------------------------
// 5. Raw MySQL connection test
// -------------------------------------------------------------------------
if (extension_loaded('pdo_mysql') && $dbName !== '' && $dbUser !== '') {
    try {
        $pdo = new PDO(
            "mysql:host={$dbHost};port=" . ($envValues['DB_PORT'] ?? '3306') . ";dbname={$dbName}",
            $dbUser,
            $dbPass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $version = $pdo->query('SELECT VERSION()')->fetchColumn();
        $results['database'][] = mark(true, 'MySQL connection', 'Connected successfully. Server: ' . $version);

        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        $results['database'][] = mark(
            count($tables) > 0,
            'Database has tables',
            count($tables) > 0
                ? 'Found ' . count($tables) . ' table(s): ' . implode(', ', array_slice($tables, 0, 10))
                : 'No tables yet — you must run: php artisan migrate --force && php artisan db:seed --force'
        );
    } catch (Throwable $e) {
        $results['database'][] = mark(false, 'MySQL connection', $e->getMessage());
    }
} else {
    $results['database'][] = mark(false, 'MySQL connection', 'PDO MySQL not available or DB credentials missing in .env');
}

// -------------------------------------------------------------------------
// 6. Composer platform check
// -------------------------------------------------------------------------
$platformFile = $basePath . '/vendor/composer/platform_check.php';
if (is_readable($platformFile)) {
    try {
        require $platformFile;
        $results['composer'][] = mark(true, 'Composer platform check', 'PHP version satisfies dependencies');
    } catch (Throwable $e) {
        $results['composer'][] = mark(false, 'Composer platform check', $e->getMessage());
    }
} else {
    $results['composer'][] = mark(false, 'Composer platform check', 'vendor/composer/platform_check.php missing');
}

// -------------------------------------------------------------------------
// 7. Laravel bootstrap test
// -------------------------------------------------------------------------
$autoload = $basePath . '/vendor/autoload.php';
$bootstrapApp = $basePath . '/bootstrap/app.php';

if (is_readable($autoload) && is_readable($bootstrapApp)) {
    try {
        require $autoload;
        $app = require $bootstrapApp;
        $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();

        $results['laravel'][] = mark(true, 'Laravel bootstrap', 'Application booted successfully');
        $results['laravel'][] = mark(true, 'Laravel version', $app->version());
        $results['laravel'][] = mark(true, 'Configured APP_ENV', (string) config('app.env'));
        $results['laravel'][] = mark(true, 'Configured APP_DEBUG', config('app.debug') ? 'true' : 'false');
        $results['laravel'][] = mark(true, 'Configured DB driver', (string) config('database.default'));

        try {
            Illuminate\Support\Facades\DB::connection()->getPdo();
            $results['laravel'][] = mark(true, 'Laravel DB connection', 'OK via Laravel config');
        } catch (Throwable $e) {
            $results['laravel'][] = mark(false, 'Laravel DB connection', $e->getMessage());
        }

        try {
            Illuminate\Support\Facades\Cache::store(config('cache.default'))->put('diag_test', 'ok', 60);
            $results['laravel'][] = mark(true, 'Cache write test', 'OK using ' . config('cache.default'));
        } catch (Throwable $e) {
            $results['laravel'][] = mark(false, 'Cache write test', $e->getMessage());
        }

        try {
            Illuminate\Support\Facades\Route::getRoutes();
            $results['laravel'][] = mark(true, 'Routes loaded', 'Route list available');
        } catch (Throwable $e) {
            $results['laravel'][] = mark(false, 'Routes loaded', $e->getMessage());
        }

        $verificationRoute = Illuminate\Support\Facades\Route::has('verification.verify');
        $results['laravel'][] = mark(
            $verificationRoute,
            'Email verification route',
            $verificationRoute ? 'verification.verify is registered' : 'Missing verification.verify — registration will 500'
        );

        try {
            $panelMiddleware = (new ReflectionClass(App\Providers\Filament\AdminPanelProvider::class))->newInstanceWithoutConstructor();
            $results['laravel'][] = mark(true, 'Filament provider class', 'Loadable');
        } catch (Throwable $e) {
            $results['laravel'][] = mark(false, 'Filament provider class', $e->getMessage());
        }

        $adminProviderFile = file_get_contents($basePath . '/app/Providers/Filament/AdminPanelProvider.php') ?: '';
        $results['laravel'][] = mark(
            ! str_contains($adminProviderFile, 'PreventRequestForgery'),
            'Admin CSRF middleware',
            str_contains($adminProviderFile, 'PreventRequestForgery')
                ? 'Still uses invalid PreventRequestForgery class — admin pages will 500'
                : 'Uses ValidateCsrfToken'
        );

        $userModelFile = file_get_contents($basePath . '/app/Models/User.php') ?: '';
        $results['laravel'][] = mark(
            str_contains($userModelFile, 'protected $fillable'),
            'User model fillable',
            str_contains($userModelFile, 'protected $fillable')
                ? 'Mass assignment configured for registration'
                : 'Missing $fillable — registration will 500 on Laravel 12'
        );

        try {
            $response = Illuminate\Support\Facades\Route::getRoutes()->match(
                Illuminate\Http\Request::create('/admin/login', 'GET')
            );
            $results['laravel'][] = mark(true, 'Admin login route', 'Matched: ' . $response->getName());
        } catch (Throwable $e) {
            $results['laravel'][] = mark(false, 'Admin login route', $e->getMessage());
        }
    } catch (Throwable $e) {
        $results['laravel'][] = mark(false, 'Laravel bootstrap', $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine());
    }
} else {
    $results['laravel'][] = mark(false, 'Laravel bootstrap', 'Missing vendor/autoload.php or bootstrap/app.php');
}

// -------------------------------------------------------------------------
// 8. Latest Laravel log lines
// -------------------------------------------------------------------------
$logFile = $basePath . '/storage/logs/laravel.log';
if (is_readable($logFile)) {
    $lines = file($logFile) ?: [];
    $tail = implode('', array_slice($lines, -40));
    $results['logs'][] = mark(true, 'Latest laravel.log entries', $tail !== '' ? $tail : 'Log file is empty');
} else {
    $results['logs'][] = mark(false, 'Latest laravel.log entries', 'No log file yet at storage/logs/laravel.log');
}

// -------------------------------------------------------------------------
// Output
// -------------------------------------------------------------------------
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Eternity Gadget Server Diagnostic</title>
    <style>
        body { font-family: Arial, sans-serif; background: #111; color: #eee; margin: 0; padding: 24px; }
        .wrap { max-width: 980px; margin: 0 auto; }
        h1 { margin-top: 0; }
        .summary { padding: 16px; border-radius: 8px; margin-bottom: 24px; }
        .summary.ok { background: #1b4332; border: 1px solid #40916c; }
        .summary.fail { background: #4a1515; border: 1px solid #c1121f; }
        .block { background: #1a1a1a; border: 1px solid #333; border-radius: 8px; padding: 16px; margin-bottom: 18px; }
        .row { display: flex; gap: 12px; padding: 10px 0; border-bottom: 1px solid #2a2a2a; }
        .row:last-child { border-bottom: 0; }
        .badge { min-width: 48px; text-align: center; font-weight: bold; border-radius: 4px; padding: 4px 8px; height: fit-content; }
        .ok .badge { background: #2d6a4f; color: #fff; }
        .fail .badge { background: #9b2226; color: #fff; }
        .detail { color: #bbb; margin-top: 6px; white-space: pre-wrap; word-break: break-word; font-size: 14px; }
        .warn { background: #3d2c00; border: 1px solid #ffb703; padding: 12px; border-radius: 8px; margin-bottom: 18px; }
        code { background: #222; padding: 2px 6px; border-radius: 4px; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>Eternity Gadget — Server Diagnostic</h1>

    <div class="warn">
        <strong>Security:</strong> Delete <code>public/diag.php</code> immediately after you finish troubleshooting.
    </div>

    <div class="summary <?= $hasFailure ? 'fail' : 'ok' ?>">
        <?php if ($hasFailure): ?>
            <strong>Issues found.</strong> Fix every item marked <strong>FAIL</strong> below, then reload this page.
        <?php else: ?>
            <strong>All checks passed.</strong> If the homepage still shows 500, check your hosting error log or temporarily set <code>APP_DEBUG=true</code>.
        <?php endif; ?>
    </div>

    <?php
    foreach ($results as $title => $items) {
        section($title, $items);
    }
    ?>

    <section class="block">
        <h2>Recommended .env for cPanel</h2>
        <div class="detail"><code>DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=babuntoo_ternitygadget
DB_USERNAME=babuntoo_ternitygadget
DB_PASSWORD=your_password
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=public
APP_DEBUG=false</code></div>
    </section>

    <section class="block">
        <h2>After fixes</h2>
        <div class="detail">Run once on server (Terminal or setup script):
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link</div>
    </section>
</div>
</body>
</html>
