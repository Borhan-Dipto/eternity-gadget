<?php

namespace App\Http\Controllers;

use App\Support\MetaPixel;
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
use Illuminate\Validation\Rule;
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

        $pixelEventId = MetaPixel::newEventId('add_to_cart');
        $pixelParams = MetaPixel::productParams($product, max(1, (int) $request->input('quantity', 1)));
        MetaPixel::queue('AddToCart', $pixelParams, $pixelEventId);
        \App\Support\MetaConversions::track('AddToCart', $pixelEventId, $pixelParams);

        if ($result['redirect_checkout']) {
            return redirect()->route('checkout');
        }

        return back()->with('success', 'প্রোডাক্টটি কার্টে যোগ হয়েছে।');
    }

    public function updateCart(Request $request): RedirectResponse
    {
        $this->cartService->update($request->input('quantities', []));

        return back()->with('success', 'কার্ট আপডেট হয়েছে।');
    }

    public function removeFromCart(Request $request): RedirectResponse
    {
        $this->cartService->remove($request->string('cart_key')->toString());

        return back()->with('success', 'প্রোডাক্টটি কার্ট থেকে সরানো হয়েছে।');
    }

    public function saveForLater(Request $request): RedirectResponse
    {
        $request->validate(['cart_key' => ['required', 'string']]);
        $this->cartService->saveForLater($request->string('cart_key')->toString());

        return back()->with('success', 'প্রোডাক্টটি পরে কেনার জন্য রাখা হয়েছে।');
    }

    public function moveToCart(Request $request): RedirectResponse
    {
        $request->validate(['cart_key' => ['required', 'string']]);
        $this->cartService->moveToCart($request->string('cart_key')->toString());

        return back()->with('success', 'প্রোডাক্টটি কার্টে নেওয়া হয়েছে।');
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
        $cartWeight = (float) $cartItems->sum(fn (array $item) => (float) ($item['product']->weight ?? 0) * $item['quantity']);
        // Exact cost per method (same formula the order uses), so the page never shows a different price than is charged.
        $freeDelivery = $this->checkoutService->qualifiesForFreeDelivery($subtotal);
        $shippingCosts = $shippingMethods->mapWithKeys(fn (ShippingMethod $method) => [$method->code => $freeDelivery ? 0.0 : $method->calculateCost($cartWeight)])->all();
        $freeDeliveryMin = $this->checkoutService->freeDeliveryMinimum();
        // No delivery area is pre-selected: the customer must choose, so nobody is silently charged the wrong rate.
        $selectedShipping = $shippingMethods->firstWhere('code', old('shipping_method'));
        $defaultShipping = $selectedShipping ?? $shippingMethods->sortByDesc('base_cost')->first();
        $shippingEstimate = $selectedShipping ? (float) $shippingCosts[$selectedShipping->code] : 0.0;
        $taxEstimate = $this->checkoutService->calculateTax($subtotal);
        $deliveryEstimate = $this->checkoutService->estimateDelivery($defaultShipping?->code);
        $rewards = app(\App\Services\RewardService::class);
        $reward = [
            'enabled' => $rewards->enabled(),
            'balance' => $rewards->balance(auth()->user()),
            'redeemable' => $rewards->redeemablePoints(auth()->user(), $subtotal),
            'earn' => $rewards->pointsForAmount($subtotal),
            'min' => $rewards->minRedeem(),
        ];
        $reward['discount'] = $rewards->discountFor($reward['redeemable']);

        return view('storefront.checkout', compact(
            'cartItems',
            'idempotencyKey',
            'shippingMethods',
            'addresses',
            'subtotal',
            'shippingEstimate',
            'shippingCosts',
            'taxEstimate',
            'deliveryEstimate',
            'freeDelivery',
            'freeDeliveryMin',
            'reward',
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
            'shipping_method' => [
                'required',
                'string',
                Rule::in(ShippingMethod::query()->where('is_active', true)->pluck('code')->all()),
            ],
            'payment_method' => ['required', 'string', Rule::in(['cod'])],
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'use_points' => ['nullable', 'boolean'],
            'terms_agreed' => ['accepted'],
        ], [
            'shipping_method.required' => 'ডেলিভারি এলাকা নির্বাচন করুন।',
            'shipping_method.in' => 'ডেলিভারি এলাকা সঠিক নয়, আবার নির্বাচন করুন।',
            'payment_method.required' => 'পেমেন্ট পদ্ধতি নির্বাচন করুন।',
            'payment_method.in' => 'এই পেমেন্ট পদ্ধতি এখন চালু নেই।',
        ]);

        $idempotencyKey = $data['idempotency_key'];
        unset($data['idempotency_key']);

        $order = $this->checkoutService->placeOrder($data, $idempotencyKey);

        return redirect()->signedRoute('order.confirmed', ['order' => $order])
            ->with('success', 'আপনার অর্ডারটি গ্রহণ করা হয়েছে।');
    }

    public function orderConfirmed(Order $order): View
    {
        $order->confirmIfExpired()->load('items');
        $trackPurchase = MetaPixel::shouldTrackPurchase($order);
        if ($trackPurchase) {
            MetaPixel::matchOrderCustomer($order);
        }

        return view('storefront.order-confirmed', compact('order', 'trackPurchase'));
    }

    public function newsletter(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        NewsletterSubscriber::updateOrCreate(['email' => $data['email']], ['is_active' => true]);

        return back()->with('success', 'সাবস্ক্রাইব করার জন্য ধন্যবাদ।');
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

        return back()->with('success', 'উইশলিস্ট আপডেট হয়েছে।');
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
