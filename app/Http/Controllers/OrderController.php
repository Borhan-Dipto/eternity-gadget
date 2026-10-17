<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService,
    ) {}

    public function show(Order $order): View
    {
        $this->authorizeOrder($order);
        $order->confirmIfExpired()->load(['items.product.images']);

        return view('storefront.orders.show', compact('order'));
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeOrder($order);
        $order->confirmIfExpired();

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $this->orderService->cancel($order, $data['reason'] ?? null);

        return back()->with('success', 'অর্ডারটি বাতিল করা হয়েছে।');
    }

    public function reorder(Order $order): RedirectResponse
    {
        $this->authorizeOrder($order);
        $this->orderService->reorder($order);

        return redirect()->route('cart')->with('success', 'স্টকে থাকা প্রোডাক্টগুলো কার্টে যোগ করা হয়েছে।');
    }

    public function invoice(Order $order): View
    {
        $this->authorizeOrder($order);
        $order->load(['items.product.images']);

        return view('storefront.orders.invoice', compact('order'));
    }

    public function confirmDelivery(Order $order): RedirectResponse
    {
        $this->authorizeOrder($order);

        if ($order->status !== Order::STATUS_SHIPPED) {
            return back()->withErrors(['order' => 'শুধু পাঠানো (শিপড) অর্ডারই ডেলিভারি কনফার্ম করা যায়।']);
        }

        $order->update([
            'status' => Order::STATUS_DELIVERED,
            'delivered_at' => now(),
        ]);

        $this->orderService->sendStatusNotification($order->fresh(['items', 'user']));

        return back()->with('success', 'ডেলিভারি কনফার্ম হয়েছে। ধন্যবাদ!');
    }

    private function authorizeOrder(Order $order): void
    {
        if (request()->hasValidSignature()) {
            return;
        }

        $guestSessionOrderIds = array_values(array_unique(array_filter([
            session('last_order_id'),
            session('tracked_order'),
            ...array_values(session('checkout_idempotency_orders', [])),
        ])));

        if ($order->isAccessibleTo(auth()->id(), $guestSessionOrderIds)) {
            return;
        }

        abort(403);
    }
}
