<?php

/**
 * Eternity Gadget — Site crash diagnostic (homepage 500)
 *
 * Upload to: public/site-crash-diag.php
 * Visit:     https://eternitygadget.shop/site-crash-diag.php?key=eternity-admin-2026
 *
 * DELETE this file after fixing the site.
 */

declare(strict_types=1);

require __DIR__ . '/admin-diag-lib.php';

adminDiagAuth();

header('Content-Type: text/html; charset=utf-8');

$basePath = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
$logFile = adminDiagInit($basePath);
$results = [];
$hasFailure = false;

function viewDotToPath(string $basePath, string $dotName): string
{
    $relative = str_replace('.', DIRECTORY_SEPARATOR, $dotName) . '.blade.php';

    return $basePath . '/resources/views/' . $relative;
}

function scanBladeIncludes(string $basePath): array
{
    $viewsRoot = $basePath . '/resources/views';
    $missing = [];
    $found = [];

    if (! is_dir($viewsRoot)) {
        return ['missing' => [['view' => '(views folder)', 'referenced_in' => 'N/A', 'path' => $viewsRoot]], 'found' => []];
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($viewsRoot, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
            continue;
        }

        $content = file_get_contents($file->getPathname()) ?: '';
        $relativeSource = str_replace($viewsRoot . DIRECTORY_SEPARATOR, '', $file->getPathname());
        $relativeSource = str_replace('\\', '/', $relativeSource);

        if (preg_match_all("/@include\s*\(\s*['\"]([^'\"]+)['\"]/", $content, $matches)) {
            foreach ($matches[1] as $viewName) {
                $path = viewDotToPath($basePath, $viewName);
                $entry = ['view' => $viewName, 'referenced_in' => $relativeSource, 'path' => $path];

                if (is_readable($path)) {
                    $found[$viewName] = $entry;
                } else {
                    $missing[$viewName . '@' . $relativeSource] = $entry;
                }
            }
        }

        if (preg_match_all("/@extends\s*\(\s*['\"]([^'\"]+)['\"]/", $content, $extendsMatches)) {
            foreach ($extendsMatches[1] as $viewName) {
                $path = viewDotToPath($basePath, $viewName);
                $entry = ['view' => $viewName, 'referenced_in' => $relativeSource, 'path' => $path];

                if (is_readable($path)) {
                    $found[$viewName] = $entry;
                } else {
                    $missing['extends:' . $viewName . '@' . $relativeSource] = $entry;
                }
            }
        }
    }

    return ['missing' => array_values($missing), 'found' => array_values($found)];
}

function extractRecentLogErrors(string $logPath, int $maxEntries = 8): array
{
    if (! is_readable($logPath)) {
        return [];
    }

    $content = file_get_contents($logPath) ?: '';
    if ($content === '') {
        return [];
    }

    preg_match_all('/^\[[^\]]+\] production\.ERROR: (.+?)(?=^\[|\z)/ms', $content, $matches);

    $entries = $matches[0] ?? [];

    return array_slice(array_reverse($entries), 0, $maxEntries);
}

function extractRouteNamesFromBlade(string $basePath): array
{
    $routes = [];
    $viewsRoot = $basePath . '/resources/views';

    if (! is_dir($viewsRoot)) {
        return $routes;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($viewsRoot, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
            continue;
        }

        $content = file_get_contents($file->getPathname()) ?: '';
        $relativeSource = str_replace($viewsRoot . DIRECTORY_SEPARATOR, '', $file->getPathname());
        $relativeSource = str_replace('\\', '/', $relativeSource);

        if (preg_match_all("/route\s*\(\s*['\"]([^'\"]+)['\"]/", $content, $matches)) {
            foreach ($matches[1] as $routeName) {
                $routes[$routeName][] = $relativeSource;
            }
        }
    }

    return $routes;
}

// -------------------------------------------------------------------------
// 1. Critical storefront view files (known crash causes)
// -------------------------------------------------------------------------
$criticalViews = [
    'layouts/storefront.blade.php',
    'storefront/home.blade.php',
    'storefront/partials/cart-toast.blade.php',
    'storefront/partials/cart-sidebar.blade.php',
    'storefront/partials/product-card.blade.php',
    'storefront/partials/order-actions.blade.php',
    'storefront/checkout.blade.php',
    'storefront/order-confirmed.blade.php',
    'storefront/account/dashboard.blade.php',
    'storefront/account/layout.blade.php',
    'storefront/account/sidebar.blade.php',
];

foreach ($criticalViews as $relative) {
    $path = $basePath . '/resources/views/' . $relative;
    $exists = is_readable($path);
    $size = $exists ? (string) filesize($path) . ' bytes' : 'UPLOAD THIS FILE';
    $results['critical_views'][] = diagMark($logFile, $exists, $relative, $exists ? $path . ' (' . $size . ')' : 'Missing at: ' . $path);
}

$bootstrapAppPath = $basePath . '/bootstrap/app.php';
$bootstrapContent = is_readable($bootstrapAppPath) ? (file_get_contents($bootstrapAppPath) ?: '') : '';
$hasApplicationImport = str_contains($bootstrapContent, 'use Illuminate\Foundation\Application;');
$usesApplicationConfigure = str_contains($bootstrapContent, 'Application::configure');

$results['bootstrap'][] = diagMark(
    $logFile,
    is_readable($bootstrapAppPath),
    'bootstrap/app.php exists',
    is_readable($bootstrapAppPath) ? $bootstrapAppPath : 'Missing'
);

$results['bootstrap'][] = diagMark(
    $logFile,
    ! $usesApplicationConfigure || $hasApplicationImport,
    'bootstrap/app.php Application import',
    $usesApplicationConfigure && ! $hasApplicationImport
        ? "MISSING: add this line near the top of bootstrap/app.php:\nuse Illuminate\\Foundation\\Application;\n\nWithout it every page returns 500 (Class \"Application\" not found)."
        : 'use Illuminate\\Foundation\\Application; is present'
);

// -------------------------------------------------------------------------
// 2. Scan all @include / @extends references
// -------------------------------------------------------------------------
$scan = scanBladeIncludes($basePath);

if ($scan['missing'] === []) {
    $results['view_scan'][] = diagMark($logFile, true, 'All @include / @extends targets', count($scan['found']) . ' view references resolved on disk');
} else {
    $results['view_scan'][] = diagMark($logFile, false, 'Missing view files (' . count($scan['missing']) . ')', 'These @include/@extends references point to files that are NOT on the server.');

    foreach ($scan['missing'] as $item) {
        $results['view_scan'][] = diagMark(
            $logFile,
            false,
            'Missing: ' . $item['view'],
            "Referenced in: resources/views/{$item['referenced_in']}\nExpected: {$item['path']}"
        );
    }
}

// -------------------------------------------------------------------------
// 3. Laravel bootstrap + homepage simulation
// -------------------------------------------------------------------------
$autoload = $basePath . '/vendor/autoload.php';
$bootstrapApp = $basePath . '/bootstrap/app.php';
$app = null;

if (is_readable($autoload) && is_readable($bootstrapApp)) {
    try {
        require $autoload;
        $app = require $bootstrapApp;
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        $results['laravel'][] = diagMark($logFile, true, 'Laravel bootstrap', 'OK — ' . $app->version());

        // Route checks used by storefront layout
        $layoutRoutes = [
            'home', 'search', 'wishlist', 'cart', 'checkout', 'account', 'account.login',
            'account.orders', 'account.contact', 'account.guest-tracking', 'page.show',
            'category.show', 'sitemap', 'orders.cancel',
        ];

        $missingRoutes = [];
        foreach ($layoutRoutes as $routeName) {
            if (! Illuminate\Support\Facades\Route::has($routeName)) {
                $missingRoutes[] = $routeName;
            }
        }

        $results['laravel'][] = diagMark(
            $logFile,
            $missingRoutes === [],
            'Storefront routes registered',
            $missingRoutes === []
                ? 'All ' . count($layoutRoutes) . ' checked routes exist'
                : "Missing routes: " . implode(', ', $missingRoutes)
        );

        // Removed routes that should NOT exist (return removal)
        $removedRoutes = ['account.returns', 'orders.return'];
        $stillPresent = array_filter($removedRoutes, fn ($r) => Illuminate\Support\Facades\Route::has($r));

        $results['laravel'][] = diagMark(
            $logFile,
            $stillPresent === [],
            'Return routes removed',
            $stillPresent === []
                ? 'account.returns and orders.return are not registered (correct)'
                : 'Still registered (remove from routes/web.php): ' . implode(', ', $stillPresent)
        );

        // Try resolving key views through Laravel's view finder
        $viewsToResolve = [
            'layouts.storefront',
            'storefront.home',
            'storefront.partials.cart-toast',
            'storefront.partials.cart-sidebar',
            'storefront.partials.product-card',
        ];

        foreach ($viewsToResolve as $viewName) {
            try {
                $resolved = Illuminate\Support\Facades\View::getFinder()->find($viewName);
                $results['laravel'][] = diagMark($logFile, true, "View::find({$viewName})", $resolved);
            } catch (Throwable $e) {
                $results['laravel'][] = diagMark($logFile, false, "View::find({$viewName})", $e->getMessage());
            }
        }

        // Simulate actual HTTP request to homepage (same as browser)
        $sim = simulateHttpRequest($app, '/', 'GET');
        $results['laravel'][] = diagMark(
            $logFile,
            $sim['ok'],
            'HTTP GET / (homepage simulation)',
            $sim['detail']
        );

        // Also test cart and search
        foreach (['/search?q=test', '/cart'] as $testUri) {
            $sim = simulateHttpRequest($app, $testUri, 'GET');
            $results['laravel'][] = diagMark(
                $logFile,
                $sim['ok'],
                'HTTP GET ' . $testUri,
                $sim['detail']
            );
        }

        // Scan blade files for route() names and verify
        $bladeRoutes = extractRouteNamesFromBlade($basePath);
        $undefinedRoutes = [];

        foreach (array_keys($bladeRoutes) as $routeName) {
            if (! Illuminate\Support\Facades\Route::has($routeName)) {
                $undefinedRoutes[$routeName] = array_slice($bladeRoutes[$routeName], 0, 3);
            }
        }

        if ($undefinedRoutes === []) {
            $results['laravel'][] = diagMark($logFile, true, 'route() names in Blade templates', 'All ' . count($bladeRoutes) . ' unique route() calls resolve');
        } else {
            $lines = [];
            foreach ($undefinedRoutes as $name => $files) {
                $lines[] = "{$name} — used in: " . implode(', ', $files);
            }
            $results['laravel'][] = diagMark(
                $logFile,
                false,
                'Undefined route() names in Blade (' . count($undefinedRoutes) . ')',
                implode("\n", $lines)
            );
        }
    } catch (Throwable $e) {
        $results['laravel'][] = diagMark(
            $logFile,
            false,
            'Laravel bootstrap / simulation',
            $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine() . "\n\n" . $e->getTraceAsString()
        );
    }
} else {
    $results['laravel'][] = diagMark($logFile, false, 'Laravel bootstrap', 'Missing vendor/autoload.php or bootstrap/app.php');
}

// -------------------------------------------------------------------------
// 4. Recent production errors from laravel.log
// -------------------------------------------------------------------------
$recentErrors = extractRecentLogErrors($basePath . '/storage/logs/laravel.log');

if ($recentErrors === []) {
    $results['logs'][] = diagMark($logFile, true, 'Recent laravel.log errors', 'No production.ERROR entries found (or log empty)');
} else {
    $results['logs'][] = diagMark($logFile, false, 'Recent laravel.log errors (' . count($recentErrors) . ')', implode("\n---\n", $recentErrors));
}

// -------------------------------------------------------------------------
// 5. Quick fix hints
// -------------------------------------------------------------------------
$fixHints = [];

foreach ($scan['missing'] as $item) {
    $fixHints[] = 'Upload: resources/views/' . str_replace('.', '/', $item['view']) . '.blade.php';
}

if ($scan['missing'] !== []) {
    $fixHints[] = 'Then run: php artisan view:clear && php artisan cache:clear';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Site Crash Diagnostic — Eternity Gadget</title>
    <style><?= diagStyles() ?>
        .fix-box { background: #1a2e1a; border: 1px solid #40916c; padding: 14px; border-radius: 8px; margin-bottom: 18px; }
        .fix-box h3 { margin: 0 0 8px; color: #95d5b2; }
        .fix-box li { margin: 4px 0; font-family: Consolas, monospace; font-size: 13px; }
        .meta { color: #888; font-size: 14px; margin-bottom: 20px; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>Site Crash Diagnostic</h1>
    <p class="meta">
        Server time: <?= htmlspecialchars(date('Y-m-d H:i:s T')) ?> &mdash;
        Base path: <code><?= htmlspecialchars($basePath) ?></code><br>
        Also logged to: <code>storage/logs/admin-diag.log</code>
    </p>

    <div class="warn">
        <strong>Security:</strong> Delete <code>public/site-crash-diag.php</code> after fixing.
        Key: <code>?key=eternity-admin-2026</code>
    </div>

    <div class="summary <?= $hasFailure ? 'fail' : 'ok' ?>">
        <?php if ($hasFailure): ?>
            <strong>Failures found.</strong> Fix every item marked <strong>FAIL</strong> below — the homepage 500 is almost certainly one of these.
        <?php else: ?>
            <strong>All checks passed.</strong> If the site still 500s, check your hosting error log or temporarily set <code>APP_DEBUG=true</code> in .env.
        <?php endif; ?>
    </div>

    <?php if ($fixHints !== []): ?>
    <div class="fix-box">
        <h3>Quick fix (most likely)</h3>
        <ul>
            <?php foreach (array_unique($fixHints) as $hint): ?>
                <li><?= htmlspecialchars($hint) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="toolbar">
        <a href="/">Try homepage</a>
        <a href="/diag.php">General diag</a>
        <a href="/storefront-images-diag.php?key=eternity-admin-2026">Image diag</a>
        <a href="/fix-storage-link.php?key=eternity-admin-2026">Fix storage link</a>
    </div>

    <?php foreach ($results as $title => $items) {
        diagSection($title, $items);
    } ?>

    <section class="block">
        <h2>After uploading missing files</h2>
        <div class="detail">Run in cPanel Terminal or SSH:

php artisan view:clear
php artisan cache:clear
php artisan route:clear

Then reload https://eternitygadget.shop/</div>
    </section>
</div>
</body>
</html>
