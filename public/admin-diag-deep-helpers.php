<?php

declare(strict_types=1);

/**
 * Helpers used only by admin-diag-deep.php — upload this file alongside deep + lib.
 */

if (! function_exists('extractExceptionFromHtml')) {
    function extractExceptionFromHtml(string $body): string
    {
        $parts = [];

        if (preg_match('/<div[^>]*class="[^"]*exception-message[^"]*"[^>]*>(.*?)<\/div>/is', $body, $m)) {
            $parts[] = 'Message: ' . trim(html_entity_decode(strip_tags($m[1])));
        }

        if (preg_match('/<span[^>]*class="[^"]*text-gray-900[^"]*"[^>]*>(.*?)<\/span>/is', $body, $m)) {
            $text = trim(html_entity_decode(strip_tags($m[1])));
            if ($text !== '' && ! str_contains($text, 'normalize.css')) {
                $parts[] = 'Exception: ' . $text;
            }
        }

        if (preg_match('/<div[^>]*class="[^"]*lg:text-lg[^"]*"[^>]*>(.*?)<\/div>/is', $body, $m)) {
            $parts[] = trim(html_entity_decode(strip_tags($m[1])));
        }

        if (preg_match('/SQLSTATE\[[^\]]+\][^<\n]+/i', $body, $m)) {
            $parts[] = 'SQL: ' . trim(html_entity_decode($m[0]));
        }

        if (preg_match('/in file\s+(.+?)\s+on line\s+(\d+)/i', $body, $m)) {
            $parts[] = "File: {$m[1]}:{$m[2]}";
        } elseif (preg_match('/<span class="font-mono[^"]*">([^<]+)<\/span>\s*:\s*<span class="font-mono[^"]*">(\d+)<\/span>/', $body, $m)) {
            $parts[] = "File: {$m[1]}:{$m[2]}";
        }

        if (preg_match('/<!--\s*([\s\S]*?Exception[\s\S]*?)\s*-->/', $body, $m)) {
            $parts[] = trim($m[1]);
        }

        if ($parts === [] && preg_match('/<title>([^<]+)<\/title>/i', $body, $m)) {
            $title = trim($m[1]);
            if (! str_contains(strtolower($title), 'server error') || strlen($body) < 5000) {
                $parts[] = 'Title: ' . $title;
            }
        }

        return implode("\n", array_unique(array_filter($parts)));
    }
}

if (! function_exists('simulateHttpRequestDetailed')) {
    /**
     * @return array{ok: bool, status: int, detail: string, exception: ?string}
     */
    function simulateHttpRequestDetailed(
        Illuminate\Foundation\Application $app,
        string $uri,
        string $method = 'GET',
        ?App\Models\User $user = null,
        bool $forceDebug = true
    ): array {
        $previousDebug = (bool) config('app.debug');

        if ($forceDebug) {
            Illuminate\Support\Facades\Config::set('app.debug', true);
        }

        try {
            $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
            $request = Illuminate\Http\Request::create($uri, $method);
            $request->headers->set('Accept', 'text/html');

            try {
                $request->setLaravelSession($app->make('session.store'));
            } catch (Throwable) {
            }

            if ($user) {
                Illuminate\Support\Facades\Auth::login($user);
            }

            Filament\Facades\Filament::setCurrentPanel(
                Filament\Facades\Filament::getPanel('admin')
            );

            $response = $kernel->handle($request);
            $status = $response->getStatusCode();
            $body = (string) $response->getContent();
            $detail = "HTTP {$status}";
            $exception = null;

            if ($status >= 400) {
                $exception = extractExceptionFromHtml($body);

                if ($exception !== '') {
                    $detail .= "\n\n" . $exception;
                } else {
                    if (preg_match('/<title>([^<]+)<\/title>/i', $body, $m)) {
                        $detail .= ' — ' . trim($m[1]);
                    }
                    $detail .= "\n" . substr(strip_tags($body), 0, 800);
                }
            }

            $kernel->terminate($request, $response);

            if ($user) {
                Illuminate\Support\Facades\Auth::logout();
            }

            return [
                'ok' => $status < 400,
                'status' => $status,
                'detail' => trim($detail),
                'exception' => $exception !== '' ? $exception : null,
            ];
        } catch (Throwable $e) {
            if ($user) {
                Illuminate\Support\Facades\Auth::logout();
            }

            return [
                'ok' => false,
                'status' => 0,
                'detail' => $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine() . "\n\n" . $e->getTraceAsString(),
                'exception' => $e->getMessage(),
            ];
        } finally {
            if ($forceDebug) {
                Illuminate\Support\Facades\Config::set('app.debug', $previousDebug);
            }
        }
    }
}

if (! function_exists('testLivewireListPages')) {
    /**
     * @return list<array{ok: bool, label: string, detail: string}>
     */
    function testLivewireListPages(
        Illuminate\Foundation\Application $app,
        App\Models\User $admin,
        string $logFile
    ): array {
        $pages = [
            'ListCategories' => App\Filament\Resources\Categories\Pages\ListCategories::class,
            'ListProducts' => App\Filament\Resources\Products\Pages\ListProducts::class,
            'ListContactButtons' => App\Filament\Resources\ContactButtons\Pages\ListContactButtons::class,
            'ListOrders' => App\Filament\Resources\Orders\Pages\ListOrders::class,
        ];

        $items = [];
        $failures = [];

        Filament\Facades\Filament::setCurrentPanel(Filament\Facades\Filament::getPanel('admin'));
        Illuminate\Support\Facades\Auth::login($admin);

        foreach ($pages as $label => $pageClass) {
            try {
                if (! class_exists($pageClass)) {
                    $items[] = diagMark($logFile, false, "Livewire: {$label}", "Class not found: {$pageClass}");
                    $failures[] = $label;

                    continue;
                }

                $previousDebug = (bool) config('app.debug');
                Illuminate\Support\Facades\Config::set('app.debug', true);

                try {
                    $component = Livewire\Livewire::test($pageClass);
                    $html = $component->html();
                    $hasError = str_contains(strtolower($html), 'server error')
                        || str_contains(strtolower($html), 'whoops')
                        || str_contains(strtolower($html), 'exception-message');

                    if ($hasError) {
                        $exception = extractExceptionFromHtml($html);
                        $failures[] = $label;
                        $items[] = diagMark(
                            $logFile,
                            false,
                            "Livewire: {$label}",
                            ($exception !== '' ? $exception : 'Rendered HTML contains an error page')
                                . "\n\n>>> Livewire render failed — this is the real list-page crash."
                        );
                    } else {
                        $items[] = diagMark(
                            $logFile,
                            true,
                            "Livewire: {$label}",
                            'Component rendered OK (' . strlen($html) . ' bytes HTML)'
                        );
                    }
                } finally {
                    Illuminate\Support\Facades\Config::set('app.debug', $previousDebug);
                }
            } catch (Throwable $e) {
                $failures[] = $label;
                $items[] = diagMark(
                    $logFile,
                    false,
                    "Livewire: {$label}",
                    $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine()
                        . "\n\n" . implode("\n", array_slice(explode("\n", $e->getTraceAsString()), 0, 8))
                        . "\n\n>>> Livewire exception — root cause of admin list 500."
                );
            }
        }

        Illuminate\Support\Facades\Auth::logout();

        $items[] = diagMark(
            $logFile,
            $failures === [],
            'Livewire list page summary',
            $failures === []
                ? 'Sample list pages render without exception'
                : 'Failed: ' . implode(', ', $failures)
        );

        return $items;
    }
}

if (! function_exists('filamentImportExportSql')) {
    function filamentImportExportSql(): string
    {
        return <<<'SQL'
-- Filament import/export tables (run in phpMyAdmin if artisan migrate cannot add them)

CREATE TABLE IF NOT EXISTS `imports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `completed_at` timestamp NULL DEFAULT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `importer` varchar(255) NOT NULL,
  `processed_rows` int unsigned NOT NULL DEFAULT 0,
  `total_rows` int unsigned NOT NULL,
  `successful_rows` int unsigned NOT NULL DEFAULT 0,
  `user_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `imports_user_id_foreign` (`user_id`),
  CONSTRAINT `imports_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `exports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `completed_at` timestamp NULL DEFAULT NULL,
  `file_disk` varchar(255) NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `exporter` varchar(255) NOT NULL,
  `processed_rows` int unsigned NOT NULL DEFAULT 0,
  `total_rows` int unsigned NOT NULL,
  `successful_rows` int unsigned NOT NULL DEFAULT 0,
  `user_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `exports_user_id_foreign` (`user_id`),
  CONSTRAINT `exports_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `failed_import_rows` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `data` json NOT NULL,
  `import_id` bigint unsigned NOT NULL,
  `validation_error` text,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `failed_import_rows_import_id_foreign` (`import_id`),
  CONSTRAINT `failed_import_rows_import_id_foreign` FOREIGN KEY (`import_id`) REFERENCES `imports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
    }
}

if (! function_exists('testCriticalPhpExtensions')) {
    /**
     * @return list<array{ok: bool, label: string, detail: string}>
     */
    function testCriticalPhpExtensions(string $logFile): array
    {
        $items = [];

        if (! extension_loaded('intl')) {
            $items[] = diagMark(
                $logFile,
                false,
                'CRITICAL: PHP intl extension',
                "MISSING — Filament admin list pages require intl for dates, numbers, and money columns.\n"
                . "This alone can cause HTTP 500 on every /admin/* list page.\n\n"
                . "Fix in cPanel:\n"
                . "1. MultiPHP INI Editor → select PHP 8.2\n"
                . "2. Enable extension: intl\n"
                . "3. Or PHP Extensions / Select PHP Version → check intl\n"
                . "4. Save and wait 1–2 minutes, then re-run this diagnostic."
            );
        } else {
            try {
                $fmt = new NumberFormatter('en_US', NumberFormatter::DECIMAL);
                $items[] = diagMark($logFile, true, 'PHP intl extension', 'Loaded — NumberFormatter OK');
            } catch (Throwable $e) {
                $items[] = diagMark($logFile, false, 'PHP intl extension', 'Loaded but broken: ' . $e->getMessage());
            }
        }

        if (! extension_loaded('fileinfo')) {
            $items[] = diagMark(
                $logFile,
                false,
                'CRITICAL: PHP fileinfo extension',
                "MISSING — Laravel file uploads and Filament ImageColumn need fileinfo.\n"
                . "Enable in cPanel → PHP Extensions → fileinfo (same steps as intl)."
            );
        } else {
            $items[] = diagMark($logFile, true, 'PHP fileinfo extension', 'Loaded');
        }

        return $items;
    }
}
