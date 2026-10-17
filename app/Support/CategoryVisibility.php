<?php

namespace App\Support;

use App\Http\Controllers\CatalogController;
use App\Models\Category;
use App\Models\MenuItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Keeps menus clean: a category with no products to show (and menu links that lead nowhere)
 * are hidden automatically, and come back by themselves as soon as products are added.
 */
class CategoryVisibility
{
    private const CACHE_KEY = 'category_visibility_v1';

    /** @return array<string, int> slug => number of products its page shows */
    public static function counts(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(30), function () {
            $controller = app(CatalogController::class);

            return Category::query()->where('is_active', true)->get()
                ->mapWithKeys(fn (Category $c) => [$c->slug => $controller->liveProductCount($c)])
                ->all();
        });
    }

    public static function hasProducts(string $slug): bool
    {
        return (self::counts()[$slug] ?? 0) > 0;
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** Remove menu items that point to empty / missing categories, or to nothing at all. */
    public static function pruneMenuItems(Collection $items): Collection
    {
        return $items->map(function (MenuItem $item) {
            if ($item->relationLoaded('children')) {
                $item->setRelation('children', self::pruneMenuItems($item->children));
            }

            return $item;
        })->filter(function (MenuItem $item) {
            $url = trim((string) $item->url);
            $hasChildren = $item->relationLoaded('children') && $item->children->isNotEmpty();

            if ($url === '' || $url === '#') {
                return $hasChildren; // a heading with no link is only useful if it opens a sub-menu
            }

            if (preg_match('#^/?category/([^/?\#]+)#', parse_url($url, PHP_URL_PATH) ?? '', $m)) {
                return self::hasProducts($m[1]) || $hasChildren;
            }

            return true; // pages, external links etc.
        })->values();
    }
}
