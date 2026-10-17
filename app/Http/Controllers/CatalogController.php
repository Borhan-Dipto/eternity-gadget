<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\HomeSection;
use App\Models\Product;
use App\Models\ProductQuestion;
use App\Models\ProductReview;
use App\Services\SearchService;
use App\Support\MediaUploadLogger;
use App\Models\Slider;
use App\Models\StockAlert;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CatalogController extends Controller
{
    private const COMPARE_LIMIT = 4;

    private const RECENTLY_VIEWED_LIMIT = 12;

    public function __construct(private SearchService $searchService) {}

    public function home(): View
    {
        $sections = HomeSection::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->each(fn (HomeSection $section) => $section->setRelation(
                'products',
                $section->resolvedProducts()
            ));

        MediaUploadLogger::auditStorefrontHomepage($sections);

        return view('storefront.home', [
            'sliders' => Slider::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'featuredCategories' => Category::query()->where('is_active', true)->where('is_featured', true)->withCount('products')->orderBy('sort_order')->get()
                ->filter(fn (Category $c) => \App\Support\CategoryVisibility::hasProducts($c->slug))->values(),
            'sections' => $sections,
        ]);
    }

    /** How many products a customer would see on this category's page (same rules as the page itself). */
    public function liveProductCount(Category $category): int
    {
        $categoryScopeIds = $this->categoryScopeIds($category);
        $modelTokens = $category->parent_id ? $this->modelMatchTokens($category->name) : [];

        return Product::query()
            ->where('is_active', true)
            ->where(function ($query) use ($category, $categoryScopeIds, $modelTokens) {
                $this->applyCategoryProductScope($query, $category, $categoryScopeIds, $modelTokens);
            })
            ->count();
    }

    public function category(string $slug, Request $request): View
    {
        $category = Category::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->with(['children' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order'), 'parent'])
            ->firstOrFail();

        $rootCategory = $category->parent_id
            ? Category::query()->whereKey($category->parent_id)->with(['children' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')])->first()
            : $category;

        $subcategories = $rootCategory?->children ?? collect();
        $categoryScopeIds = $this->categoryScopeIds($category);
        $modelTokens = $category->parent_id ? $this->modelMatchTokens($category->name) : [];

        $baseProductQuery = Product::query()
            ->where('is_active', true)
            ->where(function ($query) use ($category, $categoryScopeIds, $modelTokens) {
                $this->applyCategoryProductScope($query, $category, $categoryScopeIds, $modelTokens);
            });

        $priceBounds = (clone $baseProductQuery)
            ->selectRaw('MIN(COALESCE(sale_price, price)) as min_price, MAX(COALESCE(sale_price, price)) as max_price')
            ->first();

        $boundMin = (int) floor((float) ($priceBounds->min_price ?? 0));
        $boundMax = (int) ceil((float) ($priceBounds->max_price ?? 0));
        if ($boundMax <= $boundMin) {
            $boundMax = $boundMin + 1;
        }

        $filterMin = max($boundMin, $request->integer('min_price', $boundMin));
        $filterMax = min($boundMax, $request->integer('max_price', $boundMax));
        if ($filterMin > $filterMax) {
            $filterMin = $boundMin;
            $filterMax = $boundMax;
        }

        $perPage = in_array($request->integer('per_page'), [24, 50, 100], true)
            ? $request->integer('per_page')
            : 50;

        $products = (clone $baseProductQuery)
            ->with('images')
            ->when($request->filled('q'), fn ($query) => $query->where('name', 'like', '%' . $request->string('q') . '%'))
            ->when($request->boolean('price_filter'), function ($query) use ($filterMin, $filterMax) {
                $query->whereRaw('COALESCE(sale_price, price) BETWEEN ? AND ?', [$filterMin, $filterMax]);
            })
            ->when($request->string('sort')->toString() === 'price_asc', fn ($query) => $query->orderByRaw('COALESCE(sale_price, price) asc'))
            ->when($request->string('sort')->toString() === 'price_desc', fn ($query) => $query->orderByRaw('COALESCE(sale_price, price) desc'))
            ->when($request->string('sort')->toString() === 'name_asc', fn ($query) => $query->orderBy('name'))
            ->when(! in_array($request->string('sort')->toString(), ['price_asc', 'price_desc', 'name_asc'], true), fn ($query) => $query->storefrontOrder())
            ->paginate($perPage)
            ->withQueryString();

        $rootCategories = Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->with(['children' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        $categoryCounts = $this->buildCategoryCounts($rootCategories);

        return view('storefront.category', [
            'category' => $category,
            'rootCategory' => $rootCategory,
            // empty categories are hidden from the filters (the page you are on always stays)
            'subcategories' => $subcategories->filter(fn (Category $c) => $c->id === $category->id || \App\Support\CategoryVisibility::hasProducts($c->slug))->values(),
            'rootCategories' => $rootCategories->filter(fn (Category $c) => $c->id === $rootCategory?->id || \App\Support\CategoryVisibility::hasProducts($c->slug))
                ->each(fn (Category $c) => $c->setRelation('children', $c->children->filter(fn (Category $ch) => $ch->id === $category->id || \App\Support\CategoryVisibility::hasProducts($ch->slug))->values()))
                ->values(),
            'categoryCounts' => $categoryCounts,
            'products' => $products,
            'boundMin' => $boundMin,
            'boundMax' => $boundMax,
            'filterMin' => $filterMin,
            'filterMax' => $filterMax,
            'perPage' => $perPage,
        ]);
    }

    /** @return array<int, int> */
    private function buildCategoryCounts($rootCategories): array
    {
        $counts = [];

        foreach ($rootCategories as $root) {
            $counts[$root->id] = Product::query()
                ->where('is_active', true)
                ->whereIn('category_id', $this->categoryTreeIds($root))
                ->count();

            foreach ($root->children as $child) {
                $childTokens = $this->modelMatchTokens($child->name);
                $counts[$child->id] = Product::query()
                    ->where('is_active', true)
                    ->where(function ($query) use ($child, $childTokens, $root) {
                        $this->applyCategoryProductScope(
                            $query,
                            $child,
                            [$child->id],
                            $childTokens,
                            $this->categoryTreeIds($root),
                        );
                    })
                    ->count();
            }
        }

        return $counts;
    }

    /** @return array<int, int> */
    private function categoryTreeIds(Category $category): array
    {
        return Category::query()
            ->where('is_active', true)
            ->where(function ($query) use ($category) {
                $query->where('id', $category->id)->orWhere('parent_id', $category->id);
            })
            ->pluck('id')
            ->all();
    }

    /** @return array<int, int> */
    private function categoryScopeIds(Category $category): array
    {
        if ($category->parent_id) {
            return [$category->id];
        }

        return $this->categoryTreeIds($category);
    }

    /**
     * Child model categories (e.g. iPhone 17 Pro Max) should also include
     * parent-tree products that match the model name / device options.
     *
     * @param  array<int, int>  $categoryScopeIds
     * @param  array<int, string>  $modelTokens
     * @param  array<int, int>|null  $parentTreeIds
     */
    private function applyCategoryProductScope($query, Category $category, array $categoryScopeIds, array $modelTokens = [], ?array $parentTreeIds = null): void
    {
        if (! $category->parent_id || $modelTokens === []) {
            $query->whereIn('category_id', $categoryScopeIds);

            return;
        }

        $treeIds = $parentTreeIds ?? $this->categoryTreeIds(
            Category::query()->find($category->parent_id) ?? $category
        );

        $query->where(function ($scoped) use ($category, $treeIds, $modelTokens) {
            $scoped->where('category_id', $category->id)
                ->orWhere(function ($matched) use ($treeIds, $modelTokens) {
                    $matched->whereIn('category_id', $treeIds)
                        ->where(function ($tokenQuery) use ($modelTokens) {
                            foreach ($modelTokens as $token) {
                                $tokenQuery->orWhere('name', 'like', '%'.$token.'%')
                                    ->orWhereHas('options', function ($optionQuery) use ($token) {
                                        $optionQuery->where('value', 'like', '%'.$token.'%')
                                            ->orWhereHas('deviceModel', function ($deviceQuery) use ($token) {
                                                $deviceQuery->where('name', 'like', '%'.$token.'%')
                                                    ->orWhere('slug', 'like', '%'.$token.'%');
                                            });
                                    });
                            }
                        });
                });
        });
    }

    /** @return array<int, string> */
    private function modelMatchTokens(string $name): array
    {
        $normalized = \Illuminate\Support\Str::of($name)
            ->lower()
            ->replaceMatches('/\b(iphone|galaxy|samsung|apple|google|pixel|series|case|cases|for|the|and)\b/u', ' ')
            ->replaceMatches('/[^a-z0-9]+/u', ' ')
            ->trim()
            ->toString();

        if ($normalized === '') {
            $normalized = \Illuminate\Support\Str::lower(trim($name));
        }

        $compact = str_replace(' ', '', $normalized);

        return array_values(array_unique(array_filter([
            $normalized,
            $compact,
            str_replace(' ', '-', $normalized),
            \Illuminate\Support\Str::lower(trim($name)),
        ])));
    }

    public function product(string $slug): View
    {
        $product = Product::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->with(['category', 'images', 'options'])
            ->firstOrFail();

        $this->trackRecentlyViewed($product->id);

        $related = Product::query()
            ->where('is_active', true)
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->with('images')
            ->storefrontOrder()
            ->limit(10)
            ->get();

        $reviews = ProductReview::query()
            ->where('product_id', $product->id)
            ->where('is_approved', true)
            ->latest()
            ->limit(20)
            ->get();

        $questions = ProductQuestion::query()
            ->where('product_id', $product->id)
            ->where('is_published', true)
            ->latest()
            ->limit(20)
            ->get();

        $averageRating = $reviews->avg('rating');
        $recentlyViewed = $this->recentlyViewedProducts($product->id);
        $inCompare = in_array($product->id, session('compare', []), true);

        return view('storefront.product', compact(
            'product',
            'related',
            'reviews',
            'questions',
            'averageRating',
            'recentlyViewed',
            'inCompare'
        ));
    }

    public function search(Request $request): View
    {
        $result = $this->searchService->search($request);

        return view('storefront.search', [
            'products' => $result['products'],
            'term' => $result['term'],
        ]);
    }

    public function submitReview(Product $product, Request $request): RedirectResponse
    {
        abort_unless($product->is_active, 404);

        $expectedCaptcha = strtolower((string) session('review_captcha', ''));

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'captcha' => ['required', 'string', 'max:10'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($expectedCaptcha === '' || strtolower($data['captcha']) !== $expectedCaptcha) {
            throw ValidationException::withMessages([
                'captcha' => 'ক্যাপচা কোডটি ভুল হয়েছে, আবার চেষ্টা করুন।',
            ]);
        }

        session()->forget('review_captcha');

        $imagePath = null;
        $hasImageColumn = Schema::hasColumn('product_reviews', 'image');

        if ($hasImageColumn && $request->hasFile('image')) {
            Storage::disk('public')->makeDirectory('review-images');
            $imagePath = $request->file('image')->store('review-images', 'public');
        }

        $reviewData = [
            'product_id' => $product->id,
            'user_id' => auth()->id(),
            'name' => $data['name'],
            'email' => auth()->user()?->email,
            'rating' => $data['rating'],
            'title' => null,
            'body' => strip_tags($data['body']),
            'is_approved' => false,
        ];

        if ($hasImageColumn) {
            $reviewData['image'] = $imagePath;
        }

        ProductReview::create($reviewData);

        return back()
            ->with('success', 'ধন্যবাদ! যাচাইয়ের পর আপনার রিভিউটি দেখা যাবে।')
            ->withFragment('reviews');
    }

    public function submitQuestion(Product $product, Request $request): RedirectResponse
    {
        abort_unless($product->is_active, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'question' => ['required', 'string', 'max:2000'],
        ]);

        ProductQuestion::create([
            'product_id' => $product->id,
            'user_id' => auth()->id(),
            'name' => $data['name'],
            'question' => $data['question'],
            'is_published' => false,
        ]);

        return back()->with('success', 'আপনার প্রশ্নটি পাঠানো হয়েছে।');
    }

    public function stockAlert(Product $product, Request $request): RedirectResponse
    {
        abort_unless($product->is_active, 404);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        StockAlert::updateOrCreate(
            ['product_id' => $product->id, 'email' => $data['email']],
            ['notified_at' => null]
        );

        return back()->with('success', 'প্রোডাক্টটি স্টকে এলে আপনাকে জানানো হবে।');
    }

    public function toggleComparison(Product $product): RedirectResponse
    {
        abort_unless($product->is_active, 404);

        $compare = session('compare', []);

        if (in_array($product->id, $compare, true)) {
            $compare = array_values(array_filter($compare, fn ($id) => $id !== $product->id));
            session(['compare' => $compare]);

            return back()->with('success', 'তুলনা থেকে সরানো হয়েছে।');
        }

        if (count($compare) >= self::COMPARE_LIMIT) {
            return back()->withErrors(['compare' => 'সর্বোচ্চ ' . self::COMPARE_LIMIT . 'টি প্রোডাক্ট তুলনা করা যায়।']);
        }

        $compare[] = $product->id;
        session(['compare' => $compare]);

        return back()->with('success', 'তুলনায় যোগ হয়েছে।');
    }

    private function trackRecentlyViewed(int $productId): void
    {
        $recent = session('recently_viewed', []);
        $recent = array_values(array_filter($recent, fn ($id) => $id !== $productId));
        array_unshift($recent, $productId);
        session(['recently_viewed' => array_slice($recent, 0, self::RECENTLY_VIEWED_LIMIT)]);
    }

    private function recentlyViewedProducts(int $excludeId)
    {
        $ids = array_values(array_filter(session('recently_viewed', []), fn ($id) => $id !== $excludeId));

        if ($ids === []) {
            return collect();
        }

        return Product::query()
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->with('images')
            ->get()
            ->sortBy(fn (Product $p) => array_search($p->id, $ids, true))
            ->values();
    }
}
