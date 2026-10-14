@extends('storefront.account.layout', ['activeNav' => 'guest-tracking'])

@section('title', 'Guest Order Tracking')
@section('account_breadcrumb', 'Guest Order Tracking')

@section('account_content')
<div class="account-box">
    <h1>Guest Order Tracking</h1>

    <form method="post" action="{{ route('account.guest-tracking.lookup') }}">
        @csrf
        <div class="account-field">
            <label for="order_number"><span class="req">*</span> Order Number</label>
            <input type="text" id="order_number" name="order_number" value="{{ old('order_number') }}" required>
        </div>
        <div class="account-field">
            <label for="email"><span class="req">*</span> E-Mail Address</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required>
        </div>
        <button class="account-btn" type="submit">Track Order</button>
    </form>

    @if($order ?? null)
        <div style="margin-top:28px;padding-top:20px;border-top:1px solid #eee;">
            <h2 style="font-size:18px;">Order {{ $order->order_number }}</h2>
            <p>Status: <strong>{{ ucfirst($order->status) }}</strong></p>
            <p>Payment: {{ ucfirst($order->payment_status) }}</p>
            <p>Total: {{ money_bd($order->total) }}</p>
            @if($order->tracking_number)
                <p>Tracking: {{ $order->carrier }} — {{ $order->tracking_number }}</p>
            @endif
            <h3 style="font-size:16px;margin-top:16px;">Items</h3>
            <ul>
                @foreach($order->items as $item)
                    <li>{{ $item->product_name }} × {{ $item->quantity }} — {{ money_bd($item->total) }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
@endsection
