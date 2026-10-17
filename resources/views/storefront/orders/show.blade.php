@extends('layouts.storefront')

@section('title', 'Order ' . $order->order_number)

@section('content')
<style>
    .order-page { padding: 28px 0 56px; font-family: var(--font-sans); }
    .order-page h1 { font-size: 26px; margin-bottom: 8px; }
    .order-meta { color: #555; margin-bottom: 20px; }
    .order-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
    .order-table th, .order-table td { border-bottom: 1px solid #eee; padding: 12px 8px; text-align: left; }
    .order-actions-row { display: flex; flex-wrap: wrap; gap: 10px; margin: 20px 0; }
    .order-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 10px 18px;
        border: 0;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        color: #fff;
        background: #1a5c32;
    }
    .order-btn-danger { background: #c0392b; }
    .order-btn-secondary { background: #444; }
    .order-cancel-box {
        background: #fafafa;
        border: 1px solid #e8e8e8;
        border-radius: 8px;
        padding: 18px;
        margin: 20px 0;
    }
    .order-cancel-note, .order-status-note { color: #555; margin: 0 0 14px; }
</style>

<div class="container order-page">
    <h1>Order {{ $order->order_number }}</h1>
    <p class="order-meta">
        Status: <strong>{{ ucfirst($order->status) }}</strong>
        @if($order->status === \App\Models\Order::STATUS_PENDING && $order->isWithinCancelWindow())
            — waiting period (cancel within 10 minutes)
        @endif
        | Payment: {{ ucfirst($order->payment_status ?? 'pending') }}
    </p>
    @if($order->tracking_number)
        <p>Tracking: {{ $order->carrier }} — {{ $order->tracking_number }}</p>
    @endif

    <table class="order-table">
        <thead>
            <tr><th style="width:72px;">Image</th><th>Product</th><th>Options</th><th>Qty</th><th>Total</th></tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td>
                        <img src="{{ media_url($item->product?->primaryImage()) }}" alt="" style="width:56px;height:56px;object-fit:cover;border-radius:8px;background:#f3f3f3;">
                    </td>
                    <td>{{ $item->product_name }}</td>
                    <td>{{ $item->option_summary ?: '—' }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ money_bd($item->total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p>Subtotal: {{ money_bd($order->subtotal) }}@if((float) $order->discount > 0) | Discount: -{{ money_bd($order->discount) }}@endif | Shipping: {{ money_bd($order->shipping_cost) }}@if((float) $order->tax_amount > 0) | Tax: {{ money_bd($order->tax_amount) }}@endif | Total: <strong>{{ money_bd($order->total) }}</strong></p>

    <div class="order-actions-row">
        <a href="{{ $order->invoiceUrl() }}" class="order-btn" target="_blank" rel="noopener">Invoice</a>
        @auth
            <form method="post" action="{{ route('orders.reorder', $order) }}">@csrf<button class="order-btn order-btn-secondary" type="submit">Reorder</button></form>
        @endauth
        @if($order->status === \App\Models\Order::STATUS_SHIPPED)
            <form method="post" action="{{ route('orders.confirm-delivery', $order) }}">@csrf<button class="order-btn" type="submit">Confirm Delivery</button></form>
        @endif
    </div>

    @include('storefront.partials.order-actions', ['order' => $order])

    <p class="mt-4"><a href="{{ auth()->check() ? route('account.orders') : route('home') }}">Back</a></p>
</div>
@endsection
