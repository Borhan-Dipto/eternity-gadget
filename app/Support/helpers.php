<?php

use App\Models\SiteSetting;
use App\Support\MediaPath;
use App\Support\MediaUploadLogger;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

if (! function_exists('setting')) {
    function setting(string $key, ?string $default = null): ?string
    {
        return SiteSetting::value($key, $default);
    }
}

if (! function_exists('media_url')) {
    function media_url(?string $path, ?string $fallback = null): string
    {
        $path = MediaPath::normalize($path ?: $fallback);

        if (! $path) {
            return asset('images/placeholder.svg');
        }

        if (Str::startsWith($path, ['http://', 'https://', '//', 'data:'])) {
            return $path;
        }

        if (! Storage::disk('public')->exists($path)) {
            MediaUploadLogger::logMissingStorefrontFile($path);
        }

        return MediaUploadLogger::storageUrl($path);
    }
}

if (! function_exists('money_bd')) {
    function money_bd(float|int|string|null $amount): string
    {
        return '৳' . number_format((float) $amount, 0);
    }
}

// symfony/polyfill-mbstring does not provide mb_split, which Str::studly() needs.
if (! function_exists('mb_split')) {
    function mb_split(string $pattern, string $string, int $limit = -1): array|false
    {
        return preg_split('/' . str_replace('/', '\/', $pattern) . '/u', $string, $limit);
    }
}