<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MediaUploadLogger
{
    /** @var array<string, true> */
    private static array $loggedMissingPaths = [];

    public static function info(string $step, array $context = []): void
    {
        Log::channel('media')->info($step, $context);
    }

    public static function warning(string $step, array $context = []): void
    {
        Log::channel('media')->warning($step, $context);
    }

    public static function error(string $step, array $context = []): void
    {
        Log::channel('media')->error($step, $context);
    }

    /**
     * Pull the first usable relative path from Filament / Livewire upload state.
     */
    public static function extractPath(mixed $value, string $source = 'unknown'): ?string
    {
        self::info('extract.start', [
            'source' => $source,
            'raw_type' => get_debug_type($value),
            'raw_value' => is_scalar($value) ? $value : json_encode($value),
        ]);

        if ($value === null || $value === '' || $value === []) {
            self::warning('extract.empty', [
                'source' => $source,
                'message' => 'Form submitted with no image value',
            ]);

            return null;
        }

        $raw = self::firstStringPath($value);
        $normalized = MediaPath::normalize($raw);

        if (! $normalized) {
            self::error('extract.failed', [
                'source' => $source,
                'raw_extracted' => $raw,
                'message' => 'Could not normalize image path from upload field',
            ]);

            return null;
        }

        self::info('extract.success', [
            'source' => $source,
            'raw_extracted' => $raw,
            'normalized' => $normalized,
        ]);

        return $normalized;
    }

    /**
     * Pull all usable relative paths from a Filament multi-upload field.
     *
     * @return list<string>
     */
    public static function extractPaths(mixed $value, string $source = 'unknown'): array
    {
        self::info('extract_paths.start', [
            'source' => $source,
            'raw_type' => get_debug_type($value),
        ]);

        if ($value === null || $value === '' || $value === []) {
            return [];
        }

        $paths = [];

        if (is_string($value)) {
            $normalized = MediaPath::normalize(trim($value));

            return $normalized ? [$normalized] : [];
        }

        if (! is_array($value)) {
            return [];
        }

        foreach ($value as $item) {
            foreach (self::extractPaths($item, $source) as $path) {
                if (! in_array($path, $paths, true)) {
                    $paths[] = $path;
                }
            }
        }

        self::info('extract_paths.success', [
            'source' => $source,
            'count' => count($paths),
            'paths' => $paths,
        ]);

        return $paths;
    }

    /**
     * @return array<string, mixed>
     */
    public static function auditFile(?string $path, string $context): array
    {
        $normalized = MediaPath::normalize($path);

        $audit = [
            'context' => $context,
            'input_path' => $path,
            'normalized_path' => $normalized,
            'storage_disk' => config('filesystems.default'),
            'public_disk_root' => storage_path('app/public'),
            'public_storage_link' => public_path('storage'),
            'symlink_exists' => file_exists(public_path('storage')),
            'symlink_is_link' => is_link(public_path('storage')),
            'storage_exists' => false,
            'absolute_exists' => false,
            'public_file_exists' => false,
            'public_url' => null,
            'status' => 'missing',
        ];

        if (! $normalized) {
            self::error('audit.no_path', array_merge($audit, [
                'message' => 'Nothing to audit — path is empty or invalid',
            ]));

            return $audit;
        }

        if (str_starts_with($normalized, 'http://') || str_starts_with($normalized, 'https://')) {
            $audit['status'] = 'external_url';
            $audit['public_url'] = $normalized;

            self::info('audit.external', array_merge($audit, [
                'message' => 'External URL — no local file check',
            ]));

            return $audit;
        }

        $audit['storage_exists'] = Storage::disk('public')->exists($normalized);
        $audit['absolute_exists'] = file_exists(storage_path('app/public/' . $normalized));
        $audit['public_file_exists'] = file_exists(public_path('storage/' . $normalized));
        $audit['public_url'] = self::storageUrl($normalized);
        $audit['status'] = $audit['storage_exists'] ? 'ok' : 'missing';

        $level = $audit['storage_exists'] ? 'info' : 'error';
        $message = $audit['storage_exists']
            ? 'File exists on public disk'
            : 'FILE NOT FOUND — image will 404 on website and admin preview';

        self::{$level}('audit.file', array_merge($audit, ['message' => $message]));

        if ($audit['storage_exists'] && ! $audit['public_file_exists']) {
            self::warning('audit.symlink', array_merge($audit, [
                'message' => 'File on disk but NOT reachable via public/storage — run php artisan storage:link',
            ]));
        }

        return $audit;
    }

    /**
     * @return array<string, mixed>
     */
    public static function auditStorageSetup(): array
    {
        $report = [
            'public_disk_root' => storage_path('app/public'),
            'public_storage_path' => public_path('storage'),
            'symlink_exists' => file_exists(public_path('storage')),
            'symlink_is_link' => is_link(public_path('storage')),
            'products_dir_exists' => is_dir(storage_path('app/public/products')),
            'products_dir_writable' => is_writable(storage_path('app/public/products')),
        ];

        self::info('storage.setup', array_merge($report, [
            'message' => 'Media storage environment check',
        ]));

        return $report;
    }

    public static function storageUrl(string $path): string
    {
        return asset('storage/' . ltrim($path, '/'));
    }

    public static function logMissingStorefrontFile(string $path, array $context = []): void
    {
        if (isset(self::$loggedMissingPaths[$path])) {
            return;
        }

        self::$loggedMissingPaths[$path] = true;

        self::warning('storefront.media_url.missing_file', array_merge([
            'path' => $path,
            'url' => self::storageUrl($path),
            'storage_exists' => Storage::disk('public')->exists($path),
            'absolute_path' => storage_path('app/public/' . $path),
            'public_storage_exists' => file_exists(public_path('storage/' . $path)),
            'symlink_is_link' => is_link(public_path('storage')),
            'message' => 'Storefront requested image URL but file is missing on public disk',
        ], $context));
    }

    /**
     * @return array<string, mixed>
     */
    public static function auditStorefrontProduct(\App\Models\Product $product, string $context): array
    {
        $primaryPath = $product->primaryImage();
        $imageRows = $product->relationLoaded('images')
            ? $product->images
            : $product->images()->get(['id', 'image', 'is_primary', 'sort_order']);

        $audit = [
            'context' => $context,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'is_active' => $product->is_active,
            'is_new' => $product->is_new,
            'is_featured' => $product->is_featured,
            'image_row_count' => $imageRows->count(),
            'image_rows' => $imageRows->map(fn ($row) => [
                'id' => $row->id,
                'image' => $row->image,
                'is_primary' => (bool) $row->is_primary,
                'sort_order' => $row->sort_order,
            ])->all(),
            'primary_path' => $primaryPath,
            'display_url' => $primaryPath ? self::storageUrl($primaryPath) : asset('images/placeholder.svg'),
            'using_placeholder' => $primaryPath === null,
            'storage_exists' => false,
            'public_file_exists' => false,
            'status' => 'no_image',
        ];

        if ($primaryPath && ! str_starts_with($primaryPath, 'http')) {
            $audit['storage_exists'] = Storage::disk('public')->exists($primaryPath);
            $audit['public_file_exists'] = file_exists(public_path('storage/' . $primaryPath));
            $audit['status'] = $audit['storage_exists']
                ? ($audit['public_file_exists'] ? 'ok' : 'symlink')
                : 'missing_file';
        } elseif ($primaryPath) {
            $audit['status'] = 'external_url';
            $audit['storage_exists'] = true;
            $audit['public_file_exists'] = true;
        }

        $message = match ($audit['status']) {
            'no_image' => 'NO product_images row — homepage will show placeholder.svg',
            'missing_file' => 'DB has path but FILE NOT ON DISK — image will 404',
            'symlink' => 'File on disk but public/storage broken — run fix-storage-link.php',
            'ok' => 'Image should display correctly',
            default => 'External image URL',
        };

        $level = in_array($audit['status'], ['ok', 'external_url'], true) ? 'info' : 'warning';

        self::{$level}('storefront.product', array_merge($audit, ['message' => $message]));

        return $audit;
    }

    /**
     * @param  iterable<\App\Models\Product>  $products
     */
    public static function auditStorefrontHomepage(iterable $sections): void
    {
        self::info('storefront.homepage.start', [
            'message' => 'Homepage loaded — auditing product images shown on main website',
            'section_count' => is_countable($sections) ? count($sections) : null,
        ]);

        foreach ($sections as $section) {
            foreach ($section->products as $product) {
                self::auditStorefrontProduct($product, 'homepage.' . $section->slug);
            }
        }

        self::info('storefront.homepage.done', [
            'message' => 'Homepage image audit complete — view at /storefront-images-diag.php?key=eternity-admin-2026',
        ]);
    }

    private static function firstStringPath(mixed $value): ?string
    {
        if (is_string($value)) {
            $value = trim($value);

            return $value !== '' ? $value : null;
        }

        if (! is_array($value)) {
            return null;
        }

        foreach ($value as $item) {
            $found = self::firstStringPath($item);

            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }
}
