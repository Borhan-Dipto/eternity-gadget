<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Some product/category photos still point to the old store's CDN (another company's server).
 * This downloads them into this site's own storage so they keep working if that CDN disappears.
 */
class ImageLocalizer
{
    /** @return array{external:int, copied:int, failed:list<string>} */
    public static function status(): array
    {
        return [
            'external' => DB::table('product_images')->where('image', 'like', 'http%')->count()
                + DB::table('categories')->where('image', 'like', 'http%')->count(),
            'copied' => 0,
            'failed' => [],
        ];
    }

    /** @return array{external:int, copied:int, failed:list<string>} */
    public static function run(int $limit = 200): array
    {
        @set_time_limit(600);
        $copied = 0;
        $failed = [];

        $targets = [
            ['product_images', 'products', fn ($row) => DB::table('products')->where('id', $row->product_id)->value('name')],
            ['categories', 'categories', fn ($row) => 'Category: ' . $row->name],
        ];

        foreach ($targets as [$table, $folder, $label]) {
            $rows = DB::table($table)->where('image', 'like', 'http%')->limit($limit)->get();
            foreach ($rows as $row) {
                $path = self::download($row->image, $folder);
                if ($path) {
                    DB::table($table)->where('id', $row->id)->update(['image' => $path, 'updated_at' => now()]);
                    $copied++;
                } else {
                    $failed[] = trim((string) $label($row)) ?: $row->image;
                }
            }
        }

        return ['external' => self::status()['external'], 'copied' => $copied, 'failed' => array_values(array_unique($failed))];
    }

    private static function download(string $url, string $folder): ?string
    {
        try {
            $response = Http::timeout(25)->withHeaders(['User-Agent' => 'Mozilla/5.0 EternityGadget-ImageCopy'])->get($url);
        } catch (\Throwable) {
            return null;
        }

        $type = strtolower((string) $response->header('Content-Type'));
        if (! $response->successful() || ! str_starts_with($type, 'image/') || strlen($response->body()) < 200) {
            return null;
        }

        $ext = match (true) {
            str_contains($type, 'png') => 'png',
            str_contains($type, 'webp') => 'webp',
            str_contains($type, 'gif') => 'gif',
            default => 'jpg',
        };
        $path = $folder . '/copied-' . Str::lower(Str::ulid()) . '.' . $ext;
        Storage::disk('public')->put($path, $response->body());

        return $path;
    }
}
