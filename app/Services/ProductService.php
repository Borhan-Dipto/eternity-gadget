<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

class ProductService
{
    private const SESSION_RECENT = 'recently_viewed';

    private const MAX_RECENT = 12;

    public function recordView(Product $product): void
    {
        $ids = collect(session(self::SESSION_RECENT, []))
            ->prepend($product->id)
            ->unique()
            ->take(self::MAX_RECENT)
            ->values()
            ->all();

        session([self::SESSION_RECENT => $ids]);
    }

    public function getRecentlyViewed(?int $excludeProductId = null, int $limit = 8): Collection
    {
        $ids = collect(session(self::SESSION_RECENT, []))
            ->when($excludeProductId, fn (Collection $collection) => $collection->reject(fn ($id) => (int) $id === $excludeProductId))
            ->take($limit)
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        $products = Product::query()
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->with('images')
            ->get()
            ->keyBy('id');

        return $ids
            ->map(fn ($id) => $products->get((int) $id))
            ->filter()
            ->values();
    }

    public function getRelated(Product $product, int $limit = 10): Collection
    {
        return Product::query()
            ->where('is_active', true)
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->with('images')
            ->limit($limit)
            ->get();
    }

    /**
     * @param  array<int|string>  $productIds
     */
    public function getComparisonProducts(array $productIds, int $max = 4): Collection
    {
        $ids = collect($productIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->take($max)
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return Product::query()
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->with(['images', 'options', 'category'])
            ->get()
            ->sortBy(fn (Product $product) => $ids->search($product->id))
            ->values();
    }
}
