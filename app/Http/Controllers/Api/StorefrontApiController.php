<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use App\Models\WishlistItem;
use App\Services\CartService;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class StorefrontApiController extends Controller
{
    public function __construct(
        private CartService $cartService,
        private SearchService $searchService,
    ) {}

    public function headerCounts(): JsonResponse
    {
        $items = $this->cartService->getItems();
        $subtotal = $items->sum(fn (array $item) => $item['line_total']);

        return response()->json([
            'cart_count' => $items->sum('quantity'),
            'cart_subtotal' => money_bd($subtotal),
            'cart_subtotal_raw' => $subtotal,
            'wishlist_count' => $this->wishlistCount(),
        ]);
    }

    private function wishlistCount(): int
    {
        $query = WishlistItem::query();

        if (Auth::check()) {
            return $query->where('user_id', Auth::id())->count();
        }

        return $query->where('session_id', session()->getId())->count();
    }
    public function searchAutocomplete(Request $request): JsonResponse
    {
        $term = trim($request->string('q')->toString());

        if ($term === '') {
            return response()->json(['results' => []]);
        }

        return response()->json([
            'results' => $this->searchService->autocomplete($term)->map(fn (array $item) => [
                'id' => $item['id'],
                'name' => $item['name'],
                'slug' => $item['slug'],
                'price' => money_bd($item['price']),
                'url' => $item['url'],
                'image' => $item['image'],
            ])->values(),
        ]);
    }

    public function checkPhone(Request $request): JsonResponse
    {
        $phone = User::normalizePhone($request->string('phone')->toString());

        if (! $phone) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid phone number.',
            ], 422);
        }

        $registered = User::query()->where('phone', $phone)->exists();
        $previousOrders = Order::query()
            ->where('customer_phone', 'like', '%' . substr($phone, -11) . '%')
            ->count();

        return response()->json([
            'valid' => true,
            'phone' => $phone,
            'available' => ! $registered,
            'registered' => $registered,
            'previous_orders' => $previousOrders,
            'sharing_warning' => ! $registered && $previousOrders > 0,
        ]);
    }

    public function addToCart(Product $product, Request $request): JsonResponse
    {
        abort_unless($product->is_active, 404);

        try {
            $optionIds = collect($request->input('option_ids', []))
                ->filter(fn ($id) => $id !== null && $id !== '')
                ->values()
                ->all();

            if ($optionIds === [] && $request->filled('option_id')) {
                $optionIds = [$request->input('option_id')];
            }

            $result = $this->cartService->add(
                $product,
                max(1, (int) $request->input('quantity', 1)),
                $optionIds,
                $request->boolean('checkout')
            );

            $cart = $this->miniCartPayload();
            $added = collect($cart['items'])->firstWhere('cart_key', $result['cart_key']);

            return response()->json([
                'success' => true,
                'redirect_checkout' => $result['redirect_checkout'],
                'product' => [
                    'name' => $product->name,
                    'image' => media_url($product->primaryImage()),
                    'price' => $added['unit_price'] ?? money_bd($product->displayPrice()),
                    'option_summary' => $added['option_summary'] ?? null,
                    'url' => route('product.show', $product->slug),
                ],
                'cart' => $cart,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?: 'Validation failed.',
            ], 422);
        }
    }

    public function getMiniCart(Request $request): JsonResponse
    {
        return response()->json($this->miniCartPayload());
    }

    public function updateCart(Request $request): JsonResponse
    {
        $request->validate(['quantities' => ['required', 'array']]);
        $this->cartService->update($request->input('quantities', []));

        return response()->json($this->miniCartPayload());
    }

    public function removeFromCart(Request $request): JsonResponse
    {
        $request->validate(['cart_key' => ['required', 'string']]);
        $this->cartService->remove($request->string('cart_key')->toString());

        return response()->json($this->miniCartPayload());
    }

    /**
     * @return array<string, mixed>
     */
    private function miniCartPayload(): array
    {
        $items = $this->cartService->getItems();
        $subtotal = $items->sum(fn (array $item) => $item['line_total']);

        return [
            'count' => $items->sum('quantity'),
            'subtotal' => money_bd($subtotal),
            'subtotal_raw' => $subtotal,
            'wishlist_count' => $this->wishlistCount(),
            'items' => $items->map(fn (array $item) => [
                'cart_key' => $item['cart_key'],
                'product_id' => $item['product']->id,
                'name' => $item['product']->name,
                'slug' => $item['product']->slug,
                'option_summary' => $item['option_summary'],
                'quantity' => $item['quantity'],
                'unit_price' => money_bd($item['unit_price']),
                'line_total' => money_bd($item['line_total']),
                'image' => media_url($item['product']->primaryImage()),
                'url' => route('product.show', $item['product']->slug),
            ])->values(),
        ];
    }

    public function productReviews(Product $product): JsonResponse
    {
        abort_unless($product->is_active, 404);

        $reviews = ProductReview::query()
            ->where('product_id', $product->id)
            ->where('is_approved', true)
            ->latest()
            ->limit(50)
            ->get();

        $average = $reviews->avg('rating');

        return response()->json([
            'product_id' => $product->id,
            'average_rating' => $average ? round((float) $average, 1) : null,
            'review_count' => $reviews->count(),
            'reviews' => $reviews->map(fn (ProductReview $review) => [
                'id' => $review->id,
                'name' => $review->name,
                'rating' => $review->rating,
                'title' => $review->title,
                'body' => $review->body,
                'created_at' => $review->created_at?->toIso8601String(),
            ])->values(),
        ]);
    }

}
