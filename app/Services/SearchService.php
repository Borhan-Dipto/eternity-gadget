<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SearchService
{
    /**
     * @return array{products: LengthAwarePaginator, term: string, filters: array<string, mixed>}
     */
    public function search(Request $request, int $perPage = 24): array
    {
        $term = trim($request->string('q')->toString());
        $categorySlug = $request->string('category')->toString();
        $minPrice = $request->filled('min_price') ? (float) $request->input('min_price') : null;
        $maxPrice = $request->filled('max_price') ? (float) $request->input('max_price') : null;
        $sort = $request->string('sort')->toString();
        $featuredOnly = $request->boolean('featured');
        $inStockOnly = $request->boolean('in_stock');

        $query = Product::query()
            ->where('is_active', true)
            ->with(['images', 'category']);

        if ($term !== '') {
            $query->where(function ($builder) use ($term) {
                $builder->where('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%");
            });
        }

        if ($categorySlug !== '') {
            $category = Category::query()->where('slug', $categorySlug)->where('is_active', true)->first();
            if ($category) {
                $categoryIds = collect([$category->id])->merge(
                    $category->children()->where('is_active', true)->pluck('id')
                );
                $query->whereIn('category_id', $categoryIds);
            }
        }

        if ($minPrice !== null) {
            $query->whereRaw('COALESCE(sale_price, price) >= ?', [$minPrice]);
        }

        if ($maxPrice !== null) {
            $query->whereRaw('COALESCE(sale_price, price) <= ?', [$maxPrice]);
        }

        if ($featuredOnly) {
            $query->where('is_featured', true);
        }

        if ($inStockOnly) {
            $query->where('stock', '>', 0);
        }

        match ($sort) {
            'price_asc' => $query->orderByRaw('COALESCE(sale_price, price) asc'),
            'price_desc' => $query->orderByRaw('COALESCE(sale_price, price) desc'),
            'name_asc' => $query->orderBy('name'),
            'name_desc' => $query->orderByDesc('name'),
            default => $query->storefrontOrder(),
        };

        return [
            'products' => $query->paginate($perPage)->withQueryString(),
            'term' => $term,
            'filters' => [
                'category' => $categorySlug,
                'min_price' => $minPrice,
                'max_price' => $maxPrice,
                'sort' => $sort,
                'featured' => $featuredOnly,
                'in_stock' => $inStockOnly,
            ],
        ];
    }

    public function autocomplete(string $term, int $limit = 8): Collection
    {
        $term = trim($term);

        if ($term === '') {
            return collect();
        }

        return Product::query()
            ->where('is_active', true)
            ->where(function ($query) use ($term) {
                $query->where('name', 'like', "{$term}%")
                    ->orWhere('name', 'like', "% {$term}%")
                    ->orWhere('sku', 'like', "{$term}%");
            })
            ->with('images')
            ->orderByRaw('CASE WHEN name LIKE ? THEN 0 ELSE 1 END', ["{$term}%"])
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'price' => $product->displayPrice(),
                'image' => media_thumb($product->primaryImage(), 300),
                'url' => route('product.show', $product->slug),
            ]);
    }
}
