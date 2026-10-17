<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $urls = collect([
            ['loc' => route('home'), 'priority' => '1.0', 'changefreq' => 'daily'],
        ]);

        Category::query()->where('is_active', true)->orderBy('sort_order')->each(function (Category $category) use ($urls) {
            $urls->push([
                'loc' => route('category.show', $category->slug),
                'priority' => '0.8',
                'changefreq' => 'weekly',
                'lastmod' => $category->updated_at?->toAtomString(),
            ]);
        });

        Product::query()->where('is_active', true)->orderByDesc('updated_at')->each(function (Product $product) use ($urls) {
            $urls->push([
                'loc' => route('product.show', $product->slug),
                'priority' => '0.9',
                'changefreq' => 'weekly',
                'lastmod' => $product->updated_at?->toAtomString(),
            ]);
        });

        Page::query()->where('is_published', true)->each(function (Page $page) use ($urls) {
            $urls->push([
                'loc' => route('page.show', $page->slug),
                'priority' => '0.6',
                'changefreq' => 'monthly',
                'lastmod' => $page->updated_at?->toAtomString(),
            ]);
        });

        $xml = view('sitemap', ['urls' => $urls])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
