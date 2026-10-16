<?php

namespace App\Services;

use App\Jobs\NewOrderAlertJob;
use App\Support\AdSource;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        private CartService $cartService,
        private CouponService $couponService,
        private PhoneService $phoneService,
        private NotificationService $notificationService,
    ) {}

    public function calculateShipping(Collection $cartItems, ?string $shippingMethodCode = null): float
    {
        if ($shippingMethodCode) {
            $method = ShippingMethod::query()
                ->where('code', $shippingMethodCode)
                ->where('is_active', true)
                ->first();

            if ($method) {
                $weight = $cartItems->sum(fn (array $item) => (float) ($item['product']->weight ?? 0) * $item['quantity']);

                return $method->calculateCost($weight);
            }
        }

        return (float) setting('shipping_cost', '0');
    }

    public function calculateTax(float $subtotal, float $discount = 0): float
    {
        $rate = (float) setting('tax_rate', '0');
        $taxable = max(0, $subtotal - $discount);

        return round($taxable * ($rate / 100), 2);
    }

    /**
     * @return array{coupon: \App\Models\Coupon|null, discount: float}
     */
    public function applyCoupon(?string $code, float $subtotal): array
    {
        if (! $code) {
            return ['coupon' => null, 'discount' => 0.0];
        }

        $result = $this->couponService->validate($code, $subtotal);

        return [
            'coupon' => $result['coupon'],
            'discount' => $result['discount'],
        ];
    }

    public function validatePhone(?string $phone): string
    {
        $normalized = $this->phoneService->normalizePhone($phone);

        if (! User::isValidBangladeshMobile($normalized)) {
            throw ValidationException::withMessages([
                'customer_phone' => 'Enter a valid Bangladesh mobile number (11 digits after +88).',
            ]);
        }

        return $normalized;
    }

    /**
     * @return array{min: \Illuminate\Support\Carbon, max: \Illuminate\Support\Carbon, label: string}
     */
    public function estimateDelivery(?string $shippingMethodCode = null): array
    {
        $method = $shippingMethodCode
            ? ShippingMethod::query()->where('code', $shippingMethodCode)->where('is_active', true)->first()
            : null;

        $minDays = (int) ($method?->min_days ?? setting('delivery_min_days', '2'));
        $maxDays = (int) ($method?->max_days ?? setting('delivery_max_days', '5'));

        $min = now()->addDays($minDays)->startOfDay();
        $max = now()->addDays($maxDays)->startOfDay();

        return [
            'min' => $min,
            'max' => $max,
            'label' => $min->equalTo($max)
                ? $min->format('M j, Y')
                : $min->format('M j') . ' – ' . $max->format('M j, Y'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function placeOrder(array $data, string $idempotencyKey): Order
    {
        $completedOrders = session('checkout_idempotency_orders', []);

        if (isset($completedOrders[$idempotencyKey])) {
            return Order::query()->findOrFail($completedOrders[$idempotencyKey]);
        }

        $cartItems = $this->cartService->getItems();
        if ($cartItems->isEmpty()) {
            throw ValidationException::withMessages(['checkout' => 'Cart is empty.']);
        }

        if ($idempotencyKey !== session('checkout_idempotency_key')) {
            throw ValidationException::withMessages([
                'checkout' => 'This checkout session has expired. Please review your cart and try again.',
            ]);
        }

        $data['customer_phone'] = $this->validatePhone($data['customer_phone'] ?? null);

        $lock = Cache::lock('checkout_idempotency:' . $idempotencyKey, 30);

        try {
            $lock->block(10);

            $completedOrders = session('checkout_idempotency_orders', []);
            if (isset($completedOrders[$idempotencyKey])) {
                return Order::query()->findOrFail($completedOrders[$idempotencyKey]);
            }

            $subtotal = (float) $cartItems->sum(fn (array $item) => $item['line_total']);
            $couponResult = $this->applyCoupon($data['coupon_code'] ?? null, $subtotal);
            $discount = $couponResult['discount'];
            $shipping = $this->calculateShipping($cartItems, $data['shipping_method'] ?? null);
            $tax = $this->calculateTax($subtotal, $discount);
            $total = max(0, $subtotal - $discount + $shipping + $tax);
            $delivery = $this->estimateDelivery($data['shipping_method'] ?? null);

            $order = DB::transaction(function () use ($data, $cartItems, $subtotal, $discount, $shipping, $tax, $total, $couponResult, $delivery) {
                $order = Order::create([
                    'customer_name' => $data['customer_name'],
                    'customer_email' => $data['customer_email'] ?? null,
                    'customer_phone' => $data['customer_phone'],
                    'shipping_address' => $data['shipping_address'],
                    'payment_method' => $data['payment_method'] ?? null,
                    'payment_status' => Order::PAYMENT_STATUS_PENDING,
                    'shipping_method' => $data['shipping_method'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'order_number' => 'IG-' . now()->format('ymd') . '-' . Str::upper(Str::random(6)),
                    'user_id' => Auth::id(),
                    'status' => Order::STATUS_PENDING,
                    ...AdSource::forOrder(request()),
                    'subtotal' => $subtotal,
                    'discount' => $discount,
                    'coupon_code' => $couponResult['coupon']?->code,
                    'shipping_cost' => $shipping,
                    'tax_amount' => $tax,
                    'total' => $total,
                    'estimated_delivery' => $delivery['max'],
                ]);

                foreach ($cartItems as $item) {
                    $this->decrementStockWithLock($item);

                    $order->items()->create([
                        'product_id' => $item['product']->id,
                        'product_name' => $item['product']->name,
                        'option_summary' => $item['option_summary'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'total' => $item['line_total'],
                    ]);
                }

                if ($couponResult['coupon']) {
                    $this->couponService->incrementUsage($couponResult['coupon']);
                }

                return $order;
            });

            $completedOrders[$idempotencyKey] = $order->id;
            session([
                'checkout_idempotency_orders' => $completedOrders,
                'last_order_id' => $order->id,
            ]);
            session()->forget('checkout_idempotency_key');
            $this->cartService->clear();

            $this->notificationService->notify(
                Auth::user(),
                'order_placed',
                'Order placed',
                'Your order ' . $order->order_number . ' has been placed successfully.',
                ['order_id' => $order->id]
            );
            $this->notificationService->sendOrderEmail($order);
            NewOrderAlertJob::dispatch($order->id)->afterResponse();
            $this->notificationService->sendSms(
                $order->customer_phone,
                'Your Eternity Gadget order ' . $order->order_number . ' has been received. Total: ' . money_bd($order->total)
            );

            return $order;
        } finally {
            $lock->release();
        }
    }

    public function prepareCheckout(): string
    {
        $existingKey = session('checkout_idempotency_key');

        if (is_string($existingKey) && $existingKey !== '') {
            return $existingKey;
        }

        $idempotencyKey = (string) Str::uuid();
        session(['checkout_idempotency_key' => $idempotencyKey]);

        return $idempotencyKey;
    }

    private function decrementStockWithLock(array $item): void
    {
        if ($item['option']) {
            $option = ProductOption::query()->whereKey($item['option']->id)->lockForUpdate()->firstOrFail();

            if ($item['quantity'] > $option->stock) {
                throw ValidationException::withMessages([
                    'quantity' => 'Not enough stock for ' . $item['product']->name . '.',
                ]);
            }

            $option->decrement('stock', $item['quantity']);

            return;
        }

        $product = Product::query()->whereKey($item['product']->id)->lockForUpdate()->firstOrFail();

        if ($item['quantity'] > $product->stock) {
            throw ValidationException::withMessages([
                'quantity' => 'Not enough stock for ' . $item['product']->name . '.',
            ]);
        }

        $product->decrement('stock', $item['quantity']);
    }
}
