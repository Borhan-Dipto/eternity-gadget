<?php

/**
 * Eternity Gadget — Admin Panel Full Diagnostic
 *
 * Visit: https://yourdomain.com/admin-diag-deep.php?key=eternity-admin-2026
 *
 * Logs to: storage/logs/admin-diag.log
 * DELETE this file + admin-diag.php + admin-diag-lib.php after fixing.
 */

declare(strict_types=1);

require __DIR__ . '/admin-diag-lib.php';
require_once __DIR__ . '/admin-diag-deep-helpers.php';

adminDiagAuth();
header('Content-Type: text/html; charset=utf-8');

$basePath = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
$logFile = adminDiagInit($basePath);
$results = [];
$hasFailure = false;

$autoload = $basePath . '/vendor/autoload.php';
$bootstrapApp = $basePath . '/bootstrap/app.php';

if (! is_readable($autoload) || ! is_readable($bootstrapApp)) {
    $results['boot'][] = diagMark($logFile, false, 'Laravel files', 'Missing vendor/autoload.php or bootstrap/app.php');
} else {
    try {
        require $autoload;
        $app = require $bootstrapApp;
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        $results['boot'][] = diagMark($logFile, true, 'Laravel boot', 'Version ' . $app->version());
        $results['boot'][] = diagMark(
            $logFile,
            (bool) config('app.key'),
            'APP_KEY configured',
            config('app.key') ? 'Set' : 'MISSING — run php artisan key:generate'
        );

        // -----------------------------------------------------------------
        // Environment & permissions
        // -----------------------------------------------------------------
        $writablePaths = [
            'storage/logs' => $basePath . '/storage/logs',
            'storage/framework' => $basePath . '/storage/framework',
            'storage/app' => $basePath . '/storage/app',
            'bootstrap/cache' => $basePath . '/bootstrap/cache',
        ];

        foreach ($writablePaths as $label => $path) {
            $writable = is_dir($path) && is_writable($path);
            $results['environment'][] = diagMark(
                $logFile,
                $writable,
                "Writable: {$label}",
                $writable ? $path : "NOT WRITABLE — {$path}"
            );
        }

        $results['environment'][] = diagMark(
            $logFile,
            is_readable($basePath . '/.env'),
            '.env file',
            is_readable($basePath . '/.env') ? 'Readable' : 'Missing or not readable'
        );

        $results['environment'][] = diagMark(
            $logFile,
            true,
            'PHP version',
            PHP_VERSION . ' (' . PHP_SAPI . ')'
        );

        $results['environment'][] = diagMark(
            $logFile,
            true,
            'Memory limit',
            (string) ini_get('memory_limit')
        );

        foreach (['intl', 'mbstring', 'openssl', 'pdo_mysql', 'fileinfo', 'gd'] as $ext) {
            $loaded = extension_loaded($ext);
            $critical = in_array($ext, ['intl', 'fileinfo'], true);
            $results['environment'][] = diagMark(
                $logFile,
                $loaded,
                ($critical ? 'CRITICAL ext: ' : 'PHP ext: ') . $ext,
                $loaded ? 'Loaded' : 'MISSING' . ($critical ? ' — likely causes admin list page 500' : ' — may cause issues')
            );
        }

        foreach (testCriticalPhpExtensions($logFile) as $item) {
            $results['extensions'][] = $item;
        }

        $results['environment'][] = diagMark(
            $logFile,
            (bool) config('app.debug'),
            'APP_DEBUG',
            config('app.debug')
                ? 'true — errors visible in browser'
                : 'false — generic “Server Error” page hides details (diag forces debug for HTTP tests)'
        );

        // -----------------------------------------------------------------
        // bootstrap/app.php — guest redirect route
        // -----------------------------------------------------------------
        $bootstrapSource = file_get_contents($bootstrapApp) ?: '';
        $results['bootstrap'][] = diagMark(
            $logFile,
            str_contains($bootstrapSource, "route('account.login')"),
            'Guest redirect → account.login',
            str_contains($bootstrapSource, "route('account.login')")
                ? 'OK (storefront auth)'
                : 'Check bootstrap/app.php redirectGuestsTo'
        );

        try {
            $loginRoute = route('account.login');
            $results['bootstrap'][] = diagMark($logFile, true, 'route(account.login)', $loginRoute);
        } catch (Throwable $e) {
            $results['bootstrap'][] = diagMark($logFile, false, 'route(account.login)', $e->getMessage());
        }

        // -----------------------------------------------------------------
        // Filament panel provider
        // -----------------------------------------------------------------
        $providerFile = $basePath . '/app/Providers/Filament/AdminPanelProvider.php';
        $providerSource = is_readable($providerFile) ? (file_get_contents($providerFile) ?: '') : '';

        $results['filament'][] = diagMark($logFile, is_readable($providerFile), 'AdminPanelProvider.php', $providerFile);

        $results['filament'][] = diagMark(
            $logFile,
            ! str_contains($providerSource, 'PreventRequestForgery'),
            'CSRF middleware',
            str_contains($providerSource, 'PreventRequestForgery')
                ? 'BROKEN: PreventRequestForgery causes 500. Use ValidateCsrfToken.'
                : 'OK: ValidateCsrfToken'
        );

        $filamentCss = $basePath . '/public/css/filament/filament/app.css';
        $results['filament'][] = diagMark(
            $logFile,
            is_readable($filamentCss),
            'Filament published assets',
            is_readable($filamentCss) ? $filamentCss : 'Run: php artisan filament:assets'
        );

        try {
            $provider = new App\Providers\Filament\AdminPanelProvider($app);
            $panel = $provider->panel(Filament\Panel::make());
            $results['filament'][] = diagMark(
                $logFile,
                true,
                'Panel registration',
                'id=' . $panel->getId() . ', path=/' . $panel->getPath()
            );
        } catch (Throwable $e) {
            $results['filament'][] = diagMark($logFile, false, 'Panel registration', $e->getMessage() . "\n" . $e->getTraceAsString());
        }

        // -----------------------------------------------------------------
        // Admin user access
        // -----------------------------------------------------------------
        try {
            $admins = App\Models\User::query()
                ->where(fn ($q) => $q->where('is_admin', true)->orWhereIn('role', ['admin', 'staff']))
                ->limit(5)
                ->get();

            if ($admins->isEmpty()) {
                $results['access'][] = diagMark($logFile, false, 'Admin users', 'No user with is_admin=1 or role admin/staff');
            } else {
                foreach ($admins as $admin) {
                    $can = $admin->canAccessPanel(Filament\Facades\Filament::getPanel('admin'));
                    $results['access'][] = diagMark(
                        $logFile,
                        $can,
                        "canAccessPanel: {$admin->email}",
                        "id={$admin->id}, role={$admin->role}, is_admin=" . ($admin->is_admin ? '1' : '0')
                    );
                }
            }
        } catch (Throwable $e) {
            $results['access'][] = diagMark($logFile, false, 'Admin access check', $e->getMessage());
        }

        // -----------------------------------------------------------------
        // Widgets (dashboard crash)
        // -----------------------------------------------------------------
        foreach ([
            Filament\Widgets\AccountWidget::class,
            App\Filament\Widgets\SalesOverviewWidget::class,
        ] as $widgetClass) {
            try {
                if (! class_exists($widgetClass)) {
                    $results['widgets'][] = diagMark($logFile, false, $widgetClass, 'Class not found');
                    continue;
                }

                $widget = app($widgetClass);

                if ($widgetClass === App\Filament\Widgets\SalesOverviewWidget::class) {
                    $ref = new ReflectionClass($widget);
                    $method = $ref->getMethod('getStats');
                    $method->setAccessible(true);
                    $stats = $method->invoke($widget);
                    $results['widgets'][] = diagMark($logFile, true, 'SalesOverviewWidget::getStats()', count($stats) . ' stats returned');
                } else {
                    $results['widgets'][] = diagMark($logFile, true, "Widget: {$widgetClass}", 'Instantiated OK');
                }
            } catch (Throwable $e) {
                $results['widgets'][] = diagMark(
                    $logFile,
                    false,
                    $widgetClass,
                    $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine()
                );
            }
        }

        // -----------------------------------------------------------------
        // Database — connection, schema, migrations, live queries
        // -----------------------------------------------------------------
        $pdo = null;
        try {
            $pdo = Illuminate\Support\Facades\DB::connection()->getPdo();
            $dbName = (string) Illuminate\Support\Facades\DB::connection()->getDatabaseName();
            $results['database'][] = diagMark($logFile, true, 'Database connection', 'OK — database: ' . $dbName);
        } catch (Throwable $e) {
            $results['database'][] = diagMark($logFile, false, 'Database connection', $e->getMessage());
        }

        if ($pdo instanceof PDO) {
            // Quick sanity tables (legacy check)
            foreach (['users', 'orders', 'products', 'categories', 'sessions', 'cache', 'migrations'] as $table) {
                $exists = tableExists($pdo, $table);
                $results['database'][] = diagMark(
                    $logFile,
                    $exists,
                    "Core table: {$table}",
                    $exists ? 'Exists' : 'MISSING — run php artisan migrate --force'
                );
            }

            // Full schema audit — every table + column the app expects
            $schemaResult = validateDatabaseSchema($pdo, $logFile);
            foreach ($schemaResult['items'] as $item) {
                $results['schema'][] = $item;
            }

            // Pending migrations
            foreach (checkPendingMigrations($app, $logFile) as $item) {
                $results['schema'][] = $item;
            }

            // Live Eloquent queries (catches deleted_at, bad casts, etc.)
            foreach (testFilamentModelQueries($logFile) as $item) {
                $results['schema_queries'][] = $item;
            }

            // Filament resource table config (same path as /admin/* list pages)
            foreach (testFilamentResourceTables($app, $logFile) as $item) {
                $results['schema_queries'][] = $item;
            }
        }

        // -----------------------------------------------------------------
        // HTTP simulation — with debug + session to capture real exceptions
        // -----------------------------------------------------------------
        $urlsToTest = [
            'GET /admin/login' => ['/admin/login', 'GET', null],
            'GET /admin (guest)' => ['/admin', 'GET', null],
        ];

        $admin = App\Models\User::query()
            ->where(fn ($q) => $q->where('is_admin', true)->orWhereIn('role', ['admin', 'staff']))
            ->first();

        if ($admin) {
            $urlsToTest['GET /admin (logged in)'] = ['/admin', 'GET', $admin];
            $urlsToTest['GET /admin/products'] = ['/admin/products', 'GET', $admin];
            $urlsToTest['GET /admin/orders'] = ['/admin/orders', 'GET', $admin];
            $urlsToTest['GET /admin/categories'] = ['/admin/categories', 'GET', $admin];
        }

        $firstListException = null;

        foreach ($urlsToTest as $label => [$uri, $method, $user]) {
            $sim = simulateHttpRequestDetailed($app, $uri, $method, $user, true);
            $detail = $sim['detail'];

            if (! $sim['ok'] && $sim['exception'] && $firstListException === null && str_contains($uri, '/admin/') && $uri !== '/admin') {
                $firstListException = ['label' => $label, 'uri' => $uri, 'exception' => $sim['exception']];
            }

            $results['http'][] = diagMark(
                $logFile,
                $sim['ok'],
                $label,
                $detail . ($sim['ok'] ? '' : "\n\n>>> FIX THIS — exception captured above.")
            );
        }

        // Livewire direct render (bypasses generic 500 page)
        if ($admin) {
            foreach (testLivewireListPages($app, $admin, $logFile) as $item) {
                $results['livewire'][] = $item;
            }
        }

        // Pinpoint: first list-page exception in its own row
        if ($firstListException !== null) {
            $results['exceptions'][] = diagMark(
                $logFile,
                false,
                'Root exception: ' . $firstListException['label'],
                $firstListException['exception']
                    . "\n\nURL: " . $firstListException['uri']
                    . "\n\nThis is the actual PHP/Laravel error behind the generic 500 page."
            );
        } elseif ($admin) {
            $results['exceptions'][] = diagMark(
                $logFile,
                true,
                'Root exception capture',
                'No list-page exception captured — check Livewire section below'
            );
        }

        // Filament import/export tables (products list uses ImportAction)
        if ($pdo instanceof PDO) {
            $missingFilamentTables = array_filter(
                ['imports', 'exports', 'failed_import_rows'],
                fn (string $t) => ! tableExists($pdo, $t)
            );

            if ($missingFilamentTables !== []) {
                $results['exceptions'][] = diagMark(
                    $logFile,
                    false,
                    'Filament import/export tables',
                    'Missing: ' . implode(', ', $missingFilamentTables)
                        . "\n\nOption A (SSH/terminal):\n"
                        . "php artisan vendor:publish --tag=filament-actions-migrations --force\n"
                        . "php artisan migrate --force\n\n"
                        . "Option B (phpMyAdmin) — run this SQL:\n"
                        . filamentImportExportSql()
                );
            }
        }

        // Filament resource index pages
        if ($admin) {
            try {
                $panel = Filament\Facades\Filament::getPanel('admin');
                $resourceFails = [];

                foreach ($panel->getResources() as $resourceClass) {
                    try {
                        $url = $resourceClass::getUrl('index');
                        $path = parse_url($url, PHP_URL_PATH) ?: $url;
                        $sim = simulateHttpRequestDetailed($app, $path, 'GET', $admin, true);

                        if (! $sim['ok']) {
                            $resourceFails[] = "{$resourceClass}\n  {$path}\n  {$sim['detail']}";
                        }
                    } catch (Throwable $e) {
                        $resourceFails[] = "{$resourceClass}: " . $e->getMessage();
                    }
                }

                $results['http'][] = diagMark(
                    $logFile,
                    count($resourceFails) === 0,
                    'All Filament resource index pages (' . count($panel->getResources()) . ')',
                    count($resourceFails) === 0
                        ? 'Every resource list page returned HTTP < 400'
                        : implode("\n\n---\n\n", array_slice($resourceFails, 0, 8))
                            . (count($resourceFails) > 8 ? "\n\n... and " . (count($resourceFails) - 8) . ' more' : '')
                );
            } catch (Throwable $e) {
                $results['http'][] = diagMark($logFile, false, 'Resource index scan', $e->getMessage());
            }
        }

        // -----------------------------------------------------------------
        // Latest laravel.log error
        // -----------------------------------------------------------------
        $laravelLog = $basePath . '/storage/logs/laravel.log';
        if (is_readable($laravelLog)) {
            $content = file_get_contents($laravelLog) ?: '';
            $patterns = [
                '/\[\d{4}-\d{2}-\d{2}[^\]]*\].*?\.ERROR:.*?(?=\[\d{4}-\d{2}-\d{2}|\z)/s',
                '/\[\d{4}-\d{2}-\d{2}[^\]]*\].*?production\.ERROR:.*?(?=\[\d{4}-\d{2}-\d{2}|\z)/s',
            ];
            $found = false;

            foreach ($patterns as $pattern) {
                if (preg_match_all($pattern, $content, $matches) && $matches[0] !== []) {
                    $last = end($matches[0]);
                    $results['logs'][] = diagMark($logFile, false, 'Latest laravel.log ERROR', trim(substr($last, 0, 4000)));
                    $found = true;

                    break;
                }
            }

            if (! $found) {
                $results['logs'][] = diagMark(
                    $logFile,
                    true,
                    'laravel.log',
                    'No ERROR entries found'
                        . (config('app.debug') ? '' : ' (APP_DEBUG=false — errors may not be logged; use Exceptions + Livewire sections above)')
                );
            }
        }

        diagLog($logFile, 'info', '=== Diagnostic run finished ===', ['failures' => $hasFailure]);

    } catch (Throwable $e) {
        $results['boot'][] = diagMark($logFile, false, 'Fatal error', $e->getMessage() . "\n" . $e->getTraceAsString());
    }
}

$logReadable = is_readable($logFile);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Panel Diagnostic</title>
    <style><?= diagStyles() ?></style>
</head>
<body>
<div class="wrap">
    <h1>Admin Panel — Full Diagnostic</h1>

    <div class="warn">
        <strong>Delete after use:</strong> <code>admin-diag.php</code>, <code>admin-diag-deep.php</code>, <code>admin-diag-deep-helpers.php</code>, <code>admin-diag-lib.php</code><br>
        Secret key: <code><?= htmlspecialchars(ADMIN_DIAG_SECRET) ?></code>
    </div>

    <div class="toolbar">
        <a href="admin-diag.php?key=<?= htmlspecialchars(ADMIN_DIAG_SECRET) ?>">Quick check</a>
        <a href="admin-diag-deep.php?key=<?= htmlspecialchars(ADMIN_DIAG_SECRET) ?>">Re-run full check</a>
        <?php if ($logReadable): ?>
            <a href="admin-diag-log.php?key=<?= htmlspecialchars(ADMIN_DIAG_SECRET) ?>">Download log file</a>
        <?php endif; ?>
    </div>

    <div class="summary <?= $hasFailure ? 'fail' : 'ok' ?>">
        <?php if ($hasFailure): ?>
            <strong>Failures found.</strong> Check <em>Exceptions</em> and <em>Livewire render</em> first — those show the real PHP error. Then <em>Schema</em> and <em>HTTP</em>.
        <?php else: ?>
            <strong>All checks passed.</strong> If admin still fails, check <code>storage/logs/laravel.log</code> while reproducing the error.
        <?php endif; ?>
        <?php if ($logReadable): ?>
            <br><br>Log written to: <code>storage/logs/admin-diag.log</code>
        <?php endif; ?>
    </div>

    <?php
    $sectionTitles = [
        'boot' => 'Boot',
        'environment' => 'Environment',
        'bootstrap' => 'Bootstrap',
        'filament' => 'Filament',
        'access' => 'Access',
        'widgets' => 'Widgets',
        'database' => 'Database',
        'schema' => 'Schema (tables & columns)',
        'schema_queries' => 'Schema queries (live SQL)',
        'extensions' => 'PHP extensions (critical)',
        'exceptions' => 'Exceptions (root cause)',
        'livewire' => 'Livewire render',
        'http' => 'Http',
        'logs' => 'Logs',
    ];
    foreach ($results as $key => $items) {
        $title = $sectionTitles[$key] ?? ucfirst((string) $key);
        diagSection($title, $items);
    }
    ?>

    <section class="block">
        <h2>After fixing</h2>
        <div class="detail">php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
php artisan filament:assets</div>
    </section>
</div>
</body>
</html>
