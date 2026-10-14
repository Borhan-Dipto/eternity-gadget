<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\WishlistItem;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    public function __construct(
        private CartService $cartService,
        private CheckoutService $checkoutService,
    ) {}

    public function page(string $slug): View
    {
        $page = Page::query()->where('slug', $slug)->where('is_published', true)->firstOrFail();

        return view('storefront.page', compact('page'));
    }

    public function setLocale(string $locale): RedirectResponse
    {
        if (in_array($locale, ['en', 'bn'], true)) {
            session(['locale' => $locale]);
        }

        return back();
    }

    public function cart(): View
    {
        $cartItems = $this->cartService->getItems();
        $savedItems = $this->cartService->getItems(includeSaved: true)->where('saved_for_later', true)->values();

        return view('storefront.cart', compact('cartItems', 'savedItems'));
    }

    public function addToCart(Product $product, Request $request): RedirectResponse
    {
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

        if ($result['redirect_checkout']) {
            return redirect()->route('checkout');
        }

        return back()->with('success', 'Product added to cart.');
    }

    public function updateCart(Request $request): RedirectResponse
    {
        $this->cartService->update($request->input('quantities', []));

        return back()->with('success', 'Cart updated.');
    }

    public function removeFromCart(Request $request): RedirectResponse
    {
        $this->cartService->remove($request->string('cart_key')->toString());

        return back()->with('success', 'Product removed.');
    }

    public function saveForLater(Request $request): RedirectResponse
    {
        $request->validate(['cart_key' => ['required', 'string']]);
        $this->cartService->saveForLater($request->string('cart_key')->toString());

        return back()->with('success', 'Item saved for later.');
    }

    public function moveToCart(Request $request): RedirectResponse
    {
        $request->validate(['cart_key' => ['required', 'string']]);
        $this->cartService->moveToCart($request->string('cart_key')->toString());

        return back()->with('success', 'Item moved to cart.');
    }

    public function checkout(): View|RedirectResponse
    {
        $cartItems = $this->cartService->getItems();
        if ($cartItems->isEmpty()) {
            return redirect()->route('cart');
        }

        $idempotencyKey = $this->checkoutService->prepareCheckout();
        $shippingMethods = ShippingMethod::query()->where('is_active', true)->orderBy('base_cost')->get();
        $addresses = collect();
        if (auth()->check()) {
            try {
                $addresses = auth()->user()->addresses()->orderByDesc('is_default')->get();
            } catch (\Throwable) {
                $addresses = collect();
            }
        }
        $subtotal = (float) $cartItems->sum(fn (array $item) => $item['line_total']);
        $defaultShipping = $shippingMethods->sortByDesc('base_cost')->first() ?? $shippingMethods->first();
        $shippingEstimate = $defaultShipping
            ? $defaultShipping->calculateCost($cartItems->sum(fn (array $item) => (float) ($item['product']->weight ?? 0) * $item['quantity']))
            : (float) setting('shipping_cost', 0);
        $taxEstimate = $this->checkoutService->calculateTax($subtotal);
        $deliveryEstimate = $this->checkoutService->estimateDelivery($defaultShipping?->code);

        return view('storefront.checkout', compact(
            'cartItems',
            'idempotencyKey',
            'shippingMethods',
            'addresses',
            'subtotal',
            'shippingEstimate',
            'taxEstimate',
            'deliveryEstimate',
        ));
    }

    public function placeOrder(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'idempotency_key' => ['required', 'uuid'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:50'],
            'shipping_address' => ['required', 'string', 'max:2000'],
            'shipping_method' => ['nullable', 'string', 'max:50'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'terms_agreed' => ['accepted'],
        ]);

        $idempotencyKey = $data['idempotency_key'];
        unset($data['idempotency_key']);

        $order = $this->checkoutService->placeOrder($data, $idempotencyKey);

        return redirect()->signedRoute('order.confirmed', ['order' => $order])
            ->with('success', 'Your order has been placed.');
    }

    public function orderConfirmed(Order $order): View
    {
        $order->confirmIfExpired()->load('items');

        return view('storefront.order-confirmed', compact('order'));
    }

    public function newsletter(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        NewsletterSubscriber::updateOrCreate(['email' => $data['email']], ['is_active' => true]);

        return back()->with('success', 'Thank you for subscribing.');
    }

    public function wishlist(): View
    {
        $products = $this->wishlistQuery()
            ->with(['product' => fn ($query) => $query->where('is_active', true)->with('images')])
            ->get()
            ->pluck('product')
            ->filter();

        return view('storefront.wishlist', compact('products'));
    }

    public function toggleWishlist(Product $product): RedirectResponse
    {
        abort_unless($product->is_active, 404);

        $existing = $this->wishlistQuery()->where('product_id', $product->id)->first();

        if ($existing) {
            $existing->delete();
        } else {
            WishlistItem::create([
                'user_id' => Auth::id(),
                'session_id' => Auth::check() ? null : session()->getId(),
                'product_id' => $product->id,
            ]);
        }

        return back()->with('success', 'Wishlist updated.');
    }

    private function wishlistQuery()
    {
        $query = WishlistItem::query();

        if (Auth::check()) {
            return $query->where('user_id', Auth::id());
        }

        return $query->where('session_id', session()->getId());
    }
}
