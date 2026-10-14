<?php

/**
 * One-time setup — run migrations/seeds from browser (no SSH).
 *
 * 1. Add this line to .env:  SETUP_TOKEN=change-me-to-any-long-random-string
 * 2. Visit: https://eternitygadget.shop/setup-once.php?token=change-me-to-any-long-random-string
 * 3. DELETE this file immediately after success.
 */

declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');

$basePath = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
$token = $_GET['token'] ?? '';

function readEnvValue(string $basePath, string $key): ?string
{
    $envPath = $basePath . '/.env';

    if (! is_readable($envPath)) {
        return null;
    }

    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
            continue;
        }

        [$name, $value] = array_map('trim', explode('=', $line, 2));

        if ($name === $key) {
            return trim($value, "\"'");
        }
    }

    return null;
}

$expectedToken = readEnvValue($basePath, 'SETUP_TOKEN');

if ($expectedToken === null || $expectedToken === '') {
    exit('<h1>Setup</h1><p>Add <code>SETUP_TOKEN=your-secret-here</code> to your <code>.env</code> file first, then reload this page with <code>?token=your-secret-here</code></p>');
}

if (! hash_equals($expectedToken, $token)) {
    http_response_code(403);
    exit('<h1>Forbidden</h1><p>Invalid or missing token.</p>');
}

require $basePath . '/vendor/autoload.php';

$app = require $basePath . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    Illuminate\Support\Facades\DB::connection()->getPdo();
} catch (Throwable $e) {
    http_response_code(500);
    exit('<h1>Database failed</h1><p>Fix MySQL credentials in .env first.</p><pre>' . htmlspecialchars($e->getMessage()) . '</pre>');
}

$commands = [
    'migrate --force' => 'Creating database tables',
    'db:seed --force' => 'Loading products and Eternity Gadget branding',
    'storage:link' => 'Linking public/storage (may be skipped if already exists)',
];

echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Setup</title>';
echo '<style>body{font-family:Arial;background:#111;color:#eee;padding:24px} .ok{color:#52b788} .fail{color:#ff6b6b} code{background:#222;padding:2px 6px}</style></head><body>';
echo '<h1>Eternity Gadget — One-time setup</h1>';

foreach ($commands as $command => $label) {
    echo '<h2>' . htmlspecialchars($label) . '</h2>';

    try {
        $exitCode = Illuminate\Support\Facades\Artisan::call($command);
        $output = trim(Illuminate\Support\Facades\Artisan::output());
        $class = $exitCode === 0 ? 'ok' : 'fail';

        echo '<p class="' . $class . '">Exit code: ' . $exitCode . '</p>';

        if ($output !== '') {
            echo '<pre>' . htmlspecialchars($output) . '</pre>';
        }
    } catch (Throwable $e) {
        echo '<p class="fail">' . htmlspecialchars($e->getMessage()) . '</p>';
    }
}

echo '<hr><p><strong>Done.</strong> Visit <a href="/" style="color:#83B735">homepage</a> and <a href="/admin" style="color:#83B735">/admin</a>.</p>';
echo '<p style="color:#ffb703"><strong>DELETE</strong> <code>public/setup-once.php</code> and remove <code>SETUP_TOKEN</code> from .env now.</p>';
echo '</body></html>';
