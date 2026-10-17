<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Small copies of product/category photos (WebP), so lists and cards don't download 1-3 MB originals.
 * Saved under storage/app/public/thumbs/<width>/... and served straight by the web server afterwards.
 */
class Thumbnail
{
    public const WIDTHS = [300, 600, 1000];

    public static function url(?string $path, int $width = 600): string
    {
        $path = MediaPath::normalize($path);
        if (! $path || Str::startsWith($path, ['http://', 'https://', '//', 'data:']) || ! in_array($width, self::WIDTHS, true)
            || ! extension_loaded('gd') || str_ends_with(strtolower($path), '.gif') || str_ends_with(strtolower($path), '.svg')) {
            return media_url($path);
        }

        $thumbPath = self::thumbPath($path, $width);
        if (Storage::disk('public')->exists($thumbPath)) {
            return MediaUploadLogger::storageUrl($thumbPath);
        }

        return route('media.thumb', ['width' => $width, 'path' => $path]);
    }

    public static function thumbPath(string $path, int $width): string
    {
        return 'thumbs/' . $width . '/' . preg_replace('/\.[a-z0-9]+$/i', '', $path) . '.' . (function_exists('imagewebp') ? 'webp' : 'jpg');
    }

    /** Create the small copy; returns its absolute file path, or null if it cannot be made. */
    public static function make(string $path, int $width): ?string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');
        if (! in_array($width, self::WIDTHS, true) || str_contains($path, '..') || ! preg_match('/\.(jpe?g|png|webp)$/i', $path)) {
            return null;
        }

        $disk = Storage::disk('public');
        $thumbPath = self::thumbPath($path, $width);
        if ($disk->exists($thumbPath)) {
            return $disk->path($thumbPath);
        }
        if (! $disk->exists($path) || $disk->size($path) === 0) {
            return null;
        }

        @ini_set('memory_limit', '512M');
        $source = $disk->path($path);
        $info = @getimagesize($source);
        if (! $info) {
            return null;
        }

        $image = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($source),
            IMAGETYPE_PNG => @imagecreatefrompng($source),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($source) : false,
            default => false,
        };
        if (! $image) {
            return null;
        }

        [$w, $h] = [$info[0], $info[1]];
        $newW = min($width, $w);
        $newH = (int) round($h * $newW / $w);
        $thumb = imagecreatetruecolor($newW, $newH);
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        imagefill($thumb, 0, 0, imagecolorallocatealpha($thumb, 255, 255, 255, 127));
        imagecopyresampled($thumb, $image, 0, 0, 0, 0, $newW, $newH, $w, $h);

        $target = $disk->path($thumbPath);
        @mkdir(dirname($target), 0755, true);
        $ok = function_exists('imagewebp') ? imagewebp($thumb, $target, 80) : imagejpeg($thumb, $target, 82);
        imagedestroy($image);
        imagedestroy($thumb);

        return $ok ? $target : null;
    }
}
