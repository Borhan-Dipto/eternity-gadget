<?php

namespace App\Support;

use Illuminate\Support\Str;

class MediaPath
{
    /**
     * Normalize a stored media path from Filament / legacy uploads.
     */
    public static function normalize(mixed $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (is_array($path)) {
            $path = array_values(array_filter($path, fn ($v) => $v !== null && $v !== ''))[0] ?? null;
        }

        if (! is_string($path)) {
            return null;
        }

        $path = trim($path);

        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '//', 'data:'])) {
            return $path;
        }

        if (str_starts_with($path, '[')) {
            $decoded = json_decode($path, true);
            if (is_array($decoded)) {
                return self::normalize($decoded[0] ?? null);
            }
        }

        $path = ltrim($path, '/');
        $path = preg_replace('#^storage/#', '', $path) ?? $path;

        return $path !== '' ? $path : null;
    }
}
