<?php

/**
 * Eternity Gadget — Filament Admin Panel Diagnostic (Quick)
 *
 * Upload to: public/admin-diag.php
 * Full check: https://yourdomain.com/admin-diag-deep.php?key=eternity-admin-2026
 *
 * Logs to: storage/logs/admin-diag.log
 * DELETE admin-diag*.php files after troubleshooting.
 */

declare(strict_types=1);

require __DIR__ . '/admin-diag-lib.php';

adminDiagAuth();
header('Content-Type: text/html; charset=utf-8');

$basePath = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
$logFile = adminDiagInit($basePath);
$results = [];
$hasFailure = false;

// -------------------------------------------------------------------------
// 1. Filament files & middleware
// -------------------------------------------------------------------------
$providerFile = $basePath . '/app/Providers/Filament/AdminPanelProvider.php';
$providerSource = is_readable($providerFile) ? (file_get_contents($providerFile) ?: '') : '';

$results['filament'][] = diagMark($logFile,
    is_readable($providerFile),
    'AdminPanelProvider.php exists',
    $providerFile
);

$results['filament'][] = diagMark($logFile,
    ! str_contains($providerSource, 'PreventRequestForgery'),
    'CSRF middleware (critical)',
    str_contains($providerSource, 'PreventRequestForgery')
        ? 'BROKEN: uses PreventRequestForgery — causes 500 on every /admin page after login. Replace with ValidateCsrfToken.'
        : 'OK: uses ValidateCsrfToken'
);

$results['filament'][] = diagMark($logFile,
    str_contains($providerSource, 'ValidateCsrfToken'),
    'ValidateCsrfToken registered',
    str_contains($providerSource, 'ValidateCsrfToken') ? 'Present in middleware stack' : 'Missing from AdminPanelProvider'
);

$filamentCss = $basePath . '/public/css/filament/filament/app.css';
$results['filament'][] = diagMark($logFile,
    is_readable($filamentCss),
    'Filament CSS assets published',
    is_readable($filamentCss) ? $filamentCss : 'Run: php artisan filament:assets'
);

$filamentComposer = $basePath . '/vendor/filament/filament/composer.json';
if (is_readable($filamentComposer)) {
    $meta = json_decode(file_get_contents($filamentComposer) ?: '{}', true);
    $results['filament'][] = diagMark($logFile,true, 'Filament package version', $meta['version'] ?? 'unknown');
} else {
    $results['filament'][] = diagMark($logFile,false, 'Filament package', 'vendor/filament/filament not found — run composer install');
}

// -------------------------------------------------------------------------
// 2. User model & panel access
// -------------------------------------------------------------------------
$userFile = $basePath . '/app/Models/User.php';
$userSource = is_readable($userFile) ? (file_get_contents($userFile) ?: '') : '';

$results['access'][] = diagMark($logFile,
    str_contains($userSource, 'FilamentUser'),
    'User implements FilamentUser',
    str_contains($userSource, 'FilamentUser') ? 'OK' : 'User must implement Filament\Models\Contracts\FilamentUser'
);

$results['access'][] = diagMark($logFile,
    str_contains($userSource, 'canAccessPanel'),
    'canAccessPanel() defined',
    'Admin access requires role=admin|staff OR is_admin=1'
);

$results['access'][] = diagMark($logFile,
    str_contains($userSource, 'protected $fillable'),
    'User $fillable defined',
    'Required for registration and profile updates on Laravel 12'
);

// -------------------------------------------------------------------------
// 3. Bootstrap Laravel + DB checks
// -------------------------------------------------------------------------
$autoload = $basePath . '/vendor/autoload.php';
$bootstrapApp = $basePath . '/bootstrap/app.php';
$pdo = null;

if (is_readable($autoload) && is_readable($bootstrapApp)) {
    try {
        require $autoload;
        $app = require $bootstrapApp;
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        $results['laravel'][] = diagMark($logFile,true, 'Laravel boot', 'Version ' . $app->version());

        try {
            $pdo = Illuminate\Support\Facades\DB::connection()->getPdo();
            $results['laravel'][] = diagMark($logFile,true, 'Laravel DB connection', 'OK');
        } catch (Throwable $e) {
            $results['laravel'][] = diagMark($logFile,false, 'Laravel DB connection', $e->getMessage());
        }

        try {
            Illuminate\Support\Facades\Route::getRoutes()->match(
                Illuminate\Http\Request::create('/admin/login', 'GET')
            );
            $results['laravel'][] = diagMark($logFile,true, '/admin/login route', 'Registered');
        } catch (Throwable $e) {
            $results['laravel'][] = diagMark($logFile,false, '/admin/login route', $e->getMessage());
        }

        try {
            Illuminate\Support\Facades\Route::getRoutes()->match(
                Illuminate\Http\Request::create('/admin', 'GET')
            );
            $results['laravel'][] = diagMark($logFile,true, '/admin dashboard route', 'Registered');
        } catch (Throwable $e) {
            $results['laravel'][] = diagMark($logFile,false, '/admin dashboard route', $e->getMessage());
        }

        $sessionDriver = (string) config('session.driver');
        $sessionOk = true;
        $sessionDetail = "driver={$sessionDriver}";
        if ($pdo instanceof PDO && $sessionDriver === 'database') {
            $sessionOk = tableExists($pdo, 'sessions');
            if (! $sessionOk) {
                $sessionDetail .= ' — sessions table missing!';
            }
        }
        $results['laravel'][] = diagMark($logFile,$sessionOk, 'Session driver', $sessionDetail);

        $cacheStore = (string) config('cache.default');
        $cacheOk = true;
        $cacheDetail = "store={$cacheStore}";
        if ($pdo instanceof PDO && $cacheStore === 'database') {
            $cacheOk = tableExists($pdo, 'cache');
            if (! $cacheOk) {
                $cacheDetail .= ' — cache table missing!';
            }
        }
        $results['laravel'][] = diagMark($logFile,$cacheOk, 'Cache store', $cacheDetail);
    } catch (Throwable $e) {
        $results['laravel'][] = diagMark($logFile,false, 'Laravel boot', $e->getMessage());
    }
} else {
    $results['laravel'][] = diagMark($logFile,false, 'Laravel boot', 'Missing vendor/ or bootstrap/app.php');
}

// -------------------------------------------------------------------------
// 4. Required DB tables & columns for admin
// -------------------------------------------------------------------------
$requiredTables = [
    'users', 'orders', 'products', 'categories', 'site_settings',
    'migrations', 'sessions', 'cache', 'jobs',
];

if ($pdo instanceof PDO) {
    foreach ($requiredTables as $table) {
        $exists = tableExists($pdo, $table);
        $results['database'][] = diagMark($logFile,$exists, "Table: {$table}", $exists ? 'Exists' : 'MISSING — run php artisan migrate --force');
    }

    $userColumns = ['id', 'name', 'email', 'password', 'is_admin', 'role', 'email_verified_at'];
    foreach ($userColumns as $col) {
        $exists = tableHasColumn($pdo, 'users', $col);
        $results['database'][] = diagMark($logFile,
            $exists,
            "users.{$col}",
            $exists ? 'OK' : 'MISSING — migration 2026_06_19 or 2026_06_20 not applied'
        );
    }

    $orderColumns = ['id', 'order_number', 'status', 'total', 'payment_status'];
    foreach ($orderColumns as $col) {
        $exists = tableHasColumn($pdo, 'orders', $col);
        $results['database'][] = diagMark($logFile,
            $exists,
            "orders.{$col}",
            $exists ? 'OK' : 'MISSING — SalesOverviewWidget will crash dashboard'
        );
    }

    try {
        $stmt = $pdo->query(
            "SELECT id, email, role, is_admin FROM users
             WHERE is_admin = 1 OR role IN ('admin', 'staff')
             LIMIT 5"
        );
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $results['database'][] = diagMark($logFile,
            count($admins) > 0,
            'Admin users in database',
            count($admins) > 0
                ? implode("\n", array_map(fn ($u) => "id={$u['id']} email={$u['email']} role={$u['role']} is_admin={$u['is_admin']}", $admins))
                : 'No admin user! Seed with: php artisan db:seed --class=ImagineGadgetSeeder'
        );
    } catch (Throwable $e) {
        $results['database'][] = diagMark($logFile,false, 'Admin users query', $e->getMessage());
    }

    try {
        $pending = $pdo->query("SELECT migration FROM migrations ORDER BY id DESC LIMIT 10")->fetchAll(PDO::FETCH_COLUMN);
        $results['database'][] = diagMark($logFile,true, 'Latest migrations applied', implode("\n", $pending));
    } catch (Throwable $e) {
        $results['database'][] = diagMark($logFile,false, 'Migrations table', $e->getMessage());
    }
}

// -------------------------------------------------------------------------
// 5. Dashboard widget simulation (post-login crash test)
// -------------------------------------------------------------------------
if ($pdo instanceof PDO && tableExists($pdo, 'orders')) {
    try {
        $count = (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
        $revenue = (float) $pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status != 'cancelled'")->fetchColumn();
        $pending = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
        $results['widgets'][] = diagMark($logFile,true, 'SalesOverviewWidget queries', "orders={$count}, revenue={$revenue}, pending={$pending}");
    } catch (Throwable $e) {
        $results['widgets'][] = diagMark($logFile,false, 'SalesOverviewWidget queries', $e->getMessage() . ' — THIS IS LIKELY WHY DASHBOARD CRASHES AFTER LOGIN');
    }
} else {
    $results['widgets'][] = diagMark($logFile,false, 'SalesOverviewWidget queries', 'orders table missing');
}

// -------------------------------------------------------------------------
// 6. Recent admin-related log errors
// -------------------------------------------------------------------------
$laravelLogFile = $basePath . '/storage/logs/laravel.log';
if (is_readable($laravelLogFile)) {
    $lines = file($laravelLogFile) ?: [];
    $relevant = [];
    foreach (array_reverse($lines) as $line) {
        if (preg_match('/(filament|admin|PreventRequestForgery|SalesOverview|canAccessPanel|SQLSTATE)/i', $line)) {
            $relevant[] = rtrim($line);
            if (count($relevant) >= 25) {
                break;
            }
        }
    }
    $results['logs'][] = diagMark($logFile,
        count($relevant) > 0,
        'Recent admin-related log lines',
        count($relevant) > 0 ? implode("\n", array_reverse($relevant)) : 'No recent Filament/admin errors in log (good)'
    );
} else {
    $results['logs'][] = diagMark($logFile,true, 'laravel.log', 'No log file yet');
}

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
    <h1>Filament Admin Panel — Quick Diagnostic</h1>

    <div class="warn">
        <strong>Security:</strong> Delete all <code>admin-diag*.php</code> files after use.<br>
        <strong>Full 500 finder:</strong> <a href="admin-diag-deep.php?key=<?= htmlspecialchars(ADMIN_DIAG_SECRET) ?>">Run admin-diag-deep.php</a> — simulates every admin page and logs errors.<br>
        Log file: <code>storage/logs/admin-diag.log</code>
        <?php if (is_readable($logFile)): ?> | <a href="admin-diag-log.php?key=<?= htmlspecialchars(ADMIN_DIAG_SECRET) ?>">Download log</a><?php endif; ?>
    </div>

    <div class="summary <?= $hasFailure ? 'fail' : 'ok' ?>">
        <?php if ($hasFailure): ?>
            <strong>Issues found.</strong> Fix every FAIL item, then reload. Most common post-login crash: missing <code>orders</code> table or invalid <code>PreventRequestForgery</code> middleware.
        <?php else: ?>
            <strong>All quick checks passed.</strong> If admin still fails after login, run the deep diagnostic.
        <?php endif; ?>
    </div>

    <?php foreach ($results as $title => $items) { diagSection($title, $items); } ?>

    <section class="block">
        <h2>Quick fixes (cPanel / phpMyAdmin)</h2>
        <div class="detail">1. Ensure admin user has is_admin=1 OR role='admin' in users table
2. Run all migrations (Terminal): php artisan migrate --force
3. Seed admin: php artisan db:seed --class=ImagineGadgetSeeder
4. Set .env: SESSION_DRIVER=file, CACHE_STORE=file, QUEUE_CONNECTION=sync
5. Clear cache: php artisan config:clear && php artisan view:clear && php artisan route:clear
6. Default login: admin@gmail.com / admin000</div>
    </section>
</div>
</body>
</html>
