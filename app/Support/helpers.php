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

if (! function_exists('whatsapp_link')) {
    /** wa.me link for the shop's WhatsApp number (Admin → Shop Settings), with an optional pre-filled message. */
    function whatsapp_link(?string $message = null): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) (setting('whatsapp_number') ?: setting('phone', '')));
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            $digits = '88' . $digits;
        }

        return 'https://wa.me/' . $digits . ($message ? '?text=' . rawurlencode($message) : '');
    }
}

if (! function_exists('media_thumb')) {
    /** Small WebP copy of an uploaded photo for cards/lists (falls back to the original). */
    function media_thumb(?string $path, int $width = 600): string
    {
        return \App\Support\Thumbnail::url($path, $width);
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