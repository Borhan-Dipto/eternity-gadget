<?php

/**
 * Eternity Gadget — Checkout Page Diagnostic (live server)
 *
 * Upload to: public/checkout-diag.php
 * Visit:    https://yourdomain.com/checkout-diag.php?key=eternity-admin-2026
 *
 * Logs to: storage/logs/admin-diag.log
 * DELETE after fixing checkout.
 */

declare(strict_types=1);

require __DIR__ . '/admin-diag-lib.php';

adminDiagAuth();
header('Content-Type: text/html; charset=utf-8');

$basePath = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
$logFile = adminDiagInit($basePath);
$results = [];
$hasFailure = false;

$autoload = $basePath . '/vendor/autoload.php';
$bootstrapApp = $basePath . '/bootstrap/app.php';

echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>Checkout Diagnostic</title>';
echo '<style>body{font-family:system-ui,sans-serif;max-width:960px;margin:24px auto;padding:0 16px;background:#f8f8f8;color:#111}';
echo 'h1{font-size:22px}.block{background:#fff;border:1px solid #ddd;padding:16px;margin:16px 0}';
echo '.row{display:flex;gap:12px;padding:10px 0;border-bottom:1px solid #eee}.row:last-child{border:0}';
echo '.badge{flex:0 0 48px;text-align:center;font-weight:700;font-size:12px;padding:4px 0;border-radius:4px}';
echo '.ok .badge{background:#d4edda;color:#155724}.fail .badge{background:#f8d7da;color:#721c24}';
echo '.detail{font-size:13px;color:#555;margin-top:4px;white-space:pre-wrap;word-break:break-word}';
echo 'pre{background:#1e1e1e;color:#d4d4d4;padding:12px;overflow:auto;font-size:12px;border-radius:4px}</style></head><body>';
echo '<h1>Checkout Diagnostic</h1>';
echo '<p>Server time: ' . date('Y-m-d H:i:s T') . ' &mdash; delete <code>checkout-diag.php</code> after fixing.</p>';

// -------------------------------------------------------------------------
// 1. Files on disk
// -------------------------------------------------------------------------
$checkoutBlade = $basePath . '/resources/views/storefront/checkout.blade.php';
$langEn = $basePath . '/lang/en/messages.php';
$langBn = $basePath . '/lang/bn/messages.php';

$results['files'][] = diagMark($logFile, is_readable($checkoutBlade), 'checkout.blade.php', $checkoutBlade);
$results['files'][] = diagMark($logFile, is_readable($langEn), 'lang/en/messages.php', $langEn);
$results['files'][] = diagMark($logFile, is_readable($langBn), 'lang/bn/messages.php', $langBn);

$requiredKeys = [
    'shopping_cart', 'checkout_form_title', 'checkout_name_placeholder',
    'checkout_phone_placeholder', 'checkout_address_placeholder', 'checkout_notes_placeholder',
    'checkout_select_area', 'checkout_select_payment', 'checkout_terms',
    'checkout_confirm_order', 'checkout_your_order', 'sub_total',
];

if (is_readable($langEn)) {
    $en = include $langEn;
    $missing = array_values(array_filter($requiredKeys, fn ($k) => ! array_key_exists($k, $en)));
    $results['files'][] = diagMark(
        $logFile,
        $missing === [],
        'EN translation keys for checkout',
        $missing === [] ? 'All ' . count($requiredKeys) . ' keys present' : 'MISSING: ' . implode(', ', $missing)
    );
}

// -------------------------------------------------------------------------
// 2. Boot Laravel
// -------------------------------------------------------------------------
if (! is_readable($autoload) || ! is_readable($bootstrapApp)) {
    $results['boot'][] = diagMark($logFile, false, 'Laravel bootstrap', 'Missing vendor/autoload.php or bootstrap/app.php');
} else {
    try {
        require $autoload;
        $app = require $bootstrapApp;
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        $results['boot'][] = diagMark($logFile, true, 'Laravel boot', 'Version ' . $app->version());

        $pdo = Illuminate\Support\Facades\DB::connection()->getPdo();

        // -----------------------------------------------------------------
        // 3. Database tables checkout depends on
        // -----------------------------------------------------------------
        foreach (['shipping_methods', 'addresses', 'users', 'pages', 'site_settings'] as $table) {
            $exists = tableExists($pdo, $table);
            $results['schema'][] = diagMark(
                $logFile,
                $exists,
                "Table: {$table}",
                $exists ? 'OK' : 'MISSING — run php artisan migrate --force'
            );
        }

        if (tableExists($pdo, 'users')) {
            $results['schema'][] = diagMark(
                $logFile,
                tableHasColumn($pdo, 'users', 'phone'),
                'users.phone column',
                tableHasColumn($pdo, 'users', 'phone') ? 'OK' : 'MISSING — checkout pre-fills phone for logged-in users'
            );
        }

        if (tableExists($pdo, 'shipping_methods')) {
            $methods = Illuminate\Support\Facades\DB::table('shipping_methods')
                ->where('is_active', true)
                ->orderBy('base_cost')
                ->get(['name', 'code', 'base_cost']);
            $results['schema'][] = diagMark(
                $logFile,
                $methods->isNotEmpty(),
                'Active shipping methods',
                $methods->isEmpty()
                    ? 'NONE — checkout area selection will be empty'
                    : $methods->map(fn ($m) => "{$m->name} ({$m->code}) = {$m->base_cost}")->implode("\n")
            );
        }

        if (tableExists($pdo, 'pages')) {
            $terms = Illuminate\Support\Facades\DB::table('pages')->where('slug', 'terms-conditions')->exists();
            $results['schema'][] = diagMark(
                $logFile,
                $terms,
                'Terms page (terms-conditions)',
                $terms ? 'OK' : 'Missing — terms link still works but page 404s'
            );
        }

        // -----------------------------------------------------------------
        // 4. Render checkout view (empty cart)
        // -----------------------------------------------------------------
        try {
            $html = view('storefront.checkout', [
                'cartItems' => collect(),
                'idempotencyKey' => '00000000-0000-4000-8000-000000000001',
                'shippingMethods' => collect(),
                'addresses' => collect(),
                'subtotal' => 0,
                'shippingEstimate' => 0,
                'taxEstimate' => 0,
                'deliveryEstimate' => ['label' => ''],
            ])->render();
            $results['render'][] = diagMark($logFile, true, 'Render checkout (empty cart)', 'OK — ' . strlen($html) . ' bytes');
        } catch (Throwable $e) {
            $results['render'][] = diagMark($logFile, false, 'Render checkout (empty cart)', checkoutDiagException($e));
        }

        // -----------------------------------------------------------------
        // 5. Render with real shipping methods + sample cart item
        // -----------------------------------------------------------------
        try {
            $shippingMethods = App\Models\ShippingMethod::query()->where('is_active', true)->orderBy('base_cost')->get();
            $product = App\Models\Product::query()->where('is_active', true)->with('images', 'options')->first();

            $cartItems = collect();
            if ($product) {
                $cartService = $app->make(App\Services\CartService::class);
                $optionIds = $product->options->groupBy('name')->map->first()->pluck('id')->values()->all();
                try {
                    $cartService->add($product, 1, $optionIds);
                    $cartItems = $cartService->getItems();
                } catch (Throwable $e) {
                    $cartItems = collect([[
                        'cart_key' => 'diag-' . $product->id,
                        'product' => $product,
                        'option' => $product->options->first(),
                        'option_summary' => 'Model: Test, Color: Black',
                        'quantity' => 1,
                        'unit_price' => (float) $product->displayPrice(),
                        'line_total' => (float) $product->displayPrice(),
                        'saved_for_later' => false,
                    ]]);
                }
            }

            $subtotal = (float) $cartItems->sum(fn (array $i) => $i['line_total']);
            $defaultShipping = $shippingMethods->sortByDesc('base_cost')->first() ?? $shippingMethods->first();
            $shippingEstimate = $defaultShipping
                ? (float) $defaultShipping->base_cost
                : 0.0;
            $taxEstimate = $app->make(App\Services\CheckoutService::class)->calculateTax($subtotal);

            $html = view('storefront.checkout', [
                'cartItems' => $cartItems,
                'idempotencyKey' => '00000000-0000-4000-8000-000000000002',
                'shippingMethods' => $shippingMethods,
                'addresses' => collect(),
                'subtotal' => $subtotal,
                'shippingEstimate' => $shippingEstimate,
                'taxEstimate' => $taxEstimate,
                'deliveryEstimate' => ['label' => 'test'],
            ])->render();

            $results['render'][] = diagMark(
                $logFile,
                true,
                'Render checkout (with cart item)',
                'OK — ' . strlen($html) . ' bytes, ' . $cartItems->count() . ' item(s), product: ' . ($product?->name ?? 'none')
            );
        } catch (Throwable $e) {
            $results['render'][] = diagMark($logFile, false, 'Render checkout (with cart item)', checkoutDiagException($e));
        }

        // -----------------------------------------------------------------
        // 6. Simulate logged-in user with addresses (common 500 cause)
        // -----------------------------------------------------------------
        try {
            $user = App\Models\User::query()->first();
            if ($user && tableExists($pdo, 'addresses')) {
                Illuminate\Support\Facades\Auth::login($user);
                $addresses = $user->addresses()->orderByDesc('is_default')->get();
                $results['render'][] = diagMark(
                    $logFile,
                    true,
                    'Logged-in user addresses query',
                    'User #' . $user->id . ', addresses: ' . $addresses->count()
                );
                Illuminate\Support\Facades\Auth::logout();
            } elseif ($user && ! tableExists($pdo, 'addresses')) {
                $results['render'][] = diagMark(
                    $logFile,
                    false,
                    'Logged-in user addresses query',
                    'User exists but addresses TABLE MISSING — checkout 500s for logged-in users. Run migrations.'
                );
            } else {
                $results['render'][] = diagMark($logFile, true, 'Logged-in user addresses query', 'Skipped — no users in DB');
            }
        } catch (Throwable $e) {
            $results['render'][] = diagMark($logFile, false, 'Logged-in user addresses query', checkoutDiagException($e));
        }

        // -----------------------------------------------------------------
        // 7. Hit /checkout route via kernel (same as browser)
        // -----------------------------------------------------------------
        try {
            $request = Illuminate\Http\Request::create('/checkout', 'GET');
            $response = $app->handle($request);
            $status = $response->getStatusCode();
            $body = $response->getContent();
            $ok = $status < 400;
            $detail = "HTTP {$status}, " . strlen($body) . " bytes";
            if (! $ok) {
                $detail .= "\n\n" . substr(strip_tags($body), 0, 1500);
            } elseif ($status === 302) {
                $detail .= ' (redirect — cart empty on this session; add a product to cart first, then re-run)';
            }
            $results['route'][] = diagMark($logFile, $ok, 'GET /checkout route', $detail);
        } catch (Throwable $e) {
            $results['route'][] = diagMark($logFile, false, 'GET /checkout route', checkoutDiagException($e));
        }

        // -----------------------------------------------------------------
        // 8. Clear stale compiled views hint
        // -----------------------------------------------------------------
        $compiled = glob($basePath . '/storage/framework/views/*.php') ?: [];
        $results['env'][] = diagMark(
            $logFile,
            true,
            'Compiled Blade views',
            count($compiled) . ' cached files. If checkout was recently updated, run: php artisan view:clear'
        );

    } catch (Throwable $e) {
        $results['boot'][] = diagMark($logFile, false, 'Laravel boot failed', checkoutDiagException($e));
    }
}

// -------------------------------------------------------------------------
// 9. Latest laravel.log errors mentioning checkout
// -------------------------------------------------------------------------
$laravelLog = $basePath . '/storage/logs/laravel.log';
$checkoutErrors = '';
if (is_readable($laravelLog)) {
    $lines = file($laravelLog, FILE_IGNORE_NEW_LINES) ?: [];
    $chunks = [];
    $buf = [];
    foreach ($lines as $line) {
        if (preg_match('/^\[\d{4}-\d{2}-\d{2}/', $line)) {
            if ($buf !== []) {
                $chunks[] = implode("\n", $buf);
            }
            $buf = [$line];
        } else {
            $buf[] = $line;
        }
    }
    if ($buf !== []) {
        $chunks[] = implode("\n", $buf);
    }
    $relevant = array_values(array_filter(
        $chunks,
        fn ($c) => stripos($c, 'checkout') !== false || stripos($c, 'ERROR') !== false
    ));
    $checkoutErrors = implode("\n\n---\n\n", array_slice($relevant, -5));
}

$results['log'][] = diagMark(
    $logFile,
    true,
    'Recent laravel.log (checkout / errors)',
    $checkoutErrors !== '' ? $checkoutErrors : 'No checkout-related errors found in log (or log empty).'
);

foreach ($results as $section => $items) {
    diagSection(ucfirst($section), $items);
}

echo '<section class="block"><h2>Next steps</h2><ul>';
echo '<li>Fix any <strong>FAIL</strong> rows above on the live server.</li>';
echo '<li>Upload the latest <code>checkout.blade.php</code> and <code>lang/*/messages.php</code> if missing keys.</li>';
echo '<li>Run <code>php artisan migrate --force</code> if tables/columns are missing.</li>';
echo '<li>Run <code>php artisan view:clear</code> after uploading the new checkout view.</li>';
echo '<li>Delete <code>checkout-diag.php</code> when done.</li>';
echo '</ul></section>';

echo '<p style="color:#888;font-size:13px">Log: storage/logs/admin-diag.log</p>';
echo '</body></html>';

diagLog($logFile, $hasFailure ? 'error' : 'info', '=== Checkout diagnostic finished ===', ['failures' => $hasFailure]);

function checkoutDiagException(Throwable $e): string
{
    return $e->getMessage() . "\n\n" . $e->getFile() . ':' . $e->getLine() . "\n\n" . $e->getTraceAsString();
}
