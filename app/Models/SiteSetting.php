<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = ['group', 'key', 'label', 'type', 'value', 'sort_order'];

    protected static function booted(): void
    {
        static::saved(fn (self $setting) => static::clearCacheFor($setting->key));
        static::deleted(fn (self $setting) => static::clearCacheFor($setting->key));
    }

    public static function value(string $key, ?string $default = null): ?string
    {
        return cache()->remember("site_setting_{$key}", 60, fn () => static::where('key', $key)->value('value') ?? $default);
    }

    public static function clearCacheFor(string $key): void
    {
        cache()->forget("site_setting_{$key}");
    }
}