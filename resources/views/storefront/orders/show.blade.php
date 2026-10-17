@extends('layouts.storefront')

@section('title', 'অর্ডার ' . $order->order_number)

@php
    $steps = [
        \App\Models\Order::STATUS_PENDING => 'অর্ডার গ্রহণ',
        \App\Models\Order::STATUS_PROCESSING => 'প্রসেসিং',
        \App\Models\Order::STATUS_SHIPPED => 'কুরিয়ারে',
        \App\Models\Order::STATUS_DELIVERED => 'ডেলিভারি',
    ];
    $stepKeys = array_keys($steps);
    $isCancelled = $order->status === \App\Models\Order::STATUS_CANCELLED;
    $timelineStatus = ['confirmed' => 'processing', 'completed' => 'delivered'][$order->status] ?? $order->status;
    $currentStep = array_search($timelineStatus, $stepKeys, true);
    $currentStep = $currentStep === false ? 0 : $currentStep;
    $shippingNames = [
        'inside_dhaka' => 'ঢাকা শহরের ভিতরে', 'standard' => 'ঢাকা শহরের ভিতরে',
        'outside_dhaka' => 'ঢাকা শহরের বাইরে', 'express' => 'ঢাকা শহরের বাইরে',
    ];
    $waLink = whatsapp_link('আমার অর্ডার নম্বর ' . $order->order_number . ' — ');
@endphp

@section('content')
<style>
    .od { padding: 24px 0 56px; font-family: var(--font-sans); max-width: 860px; }
    .od-head { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 18px; }
    .od-head h1 { font-size: 22px; font-weight: 700; margin: 0 0 4px; color: #111; }
    .od-date { color: #777; font-size: 14px; }
    .od-pill { display: inline-block; padding: 6px 12px; border-radius: 999px; font-size: 13px; font-weight: 600; background: #eef6ff; color: #0b5cad; }
    .od-pill.is-cancelled { background: #fdecea; color: #b42318; }
    .od-pill.is-delivered { background: #e8f6ee; color: #1a7f45; }
    .od-card { background: #fff; border: 1px solid #ececec; border-radius: 14px; padding: 18px; margin-bottom: 16px; }
    .od-card-title { font-size: 15px; font-weight: 700; color: #111; margin: 0 0 14px; }
    .od-track { display: flex; justify-content: space-between; position: relative; margin: 6px 4px 2px; }
    .od-track::before { content: ""; position: absolute; top: 13px; left: 12%; right: 12%; height: 3px; background: #e6e6e6; }
    .od-track-fill { position: absolute; top: 13px; left: 12%; height: 3px; background: #1a7f45; transition: width .3s; }
    .od-step { position: relative; z-index: 1; flex: 1; text-align: center; font-size: 12px; color: #999; }
    .od-dot { width: 28px; height: 28px; border-radius: 50%; margin: 0 auto 6px; background: #fff; border: 3px solid #e0e0e0; display: flex; align-items: center; justify-content: center; font-size: 13px; color: #fff; }
    .od-step.done .od-dot { background: #1a7f45; border-color: #1a7f45; }
    .od-step.done { color: #1a7f45; font-weight: 600; }
    .od-cancel-msg { color: #b42318; margin: 0; font-weight: 600; }
    .od-tracking { margin-top: 14px; padding: 10px 12px; background: #f7f7f8; border-radius: 10px; font-size: 14px; }
    .od-item { display: flex; gap: 12px; align-items: center; padding: 10px 0; border-bottom: 1px solid #f2f2f2; }
    .od-item:last-child { border-bottom: 0; }
    .od-item img { width: 60px; height: 60px; object-fit: cover; border-radius: 10px; background: #f3f3f3; flex-shrink: 0; }
    .od-item-info { flex: 1; min-width: 0; }
    .od-item-name { font-weight: 600; font-size: 14px; color: #111; line-height: 1.35; }
    .od-item-meta { font-size: 13px; color: #777; margin-top: 2px; }
    .od-item-price { font-weight: 700; font-size: 14px; white-space: nowrap; }
    .od-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 14px; color: #444; }
    .od-row.total { border-top: 1px solid #eee; margin-top: 6px; padding-top: 12px; font-size: 17px; font-weight: 700; color: #111; }
    .od-row .minus { color: #1a7f45; }
    .od-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .od-info p { margin: 0 0 6px; font-size: 14px; color: #444; line-height: 1.5; }
    .od-info strong { color: #111; }
    .od-actions { display: flex; flex-wrap: wrap; gap: 10px; margin: 6px 0 18px; }
    .order-btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 11px 18px; border: 0; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; color: #fff !important; background: var(--brand); }
    .order-btn-secondary { background: #fff; color: #111 !important; border: 1px solid #d9d9d9; }
    .order-btn-danger { background: #c0392b; }
    .order-btn-wa { background: #25D366; }
    .order-cancel-box { background: #fff8ed; border: 1px solid #f6d7a7; border-radius: 12px; padding: 16px; margin: 0 0 16px; }
    .order-cancel-note, .order-status-note { color: #6b4e16; margin: 0 0 12px; font-size: 14px; }
    .od-back { font-size: 14px; color: #555; text-decoration: none; }
    @media (max-width: 640px) {
        .od-grid { grid-template-columns: 1fr; }
        .od-head h1 { font-size: 19px; }
        .od-step { font-size: 11px; }
        .od-actions .order-btn, .od-actions form { flex: 1 1 100%; }
        .od-actions form .order-btn { width: 100%; }
    }
</style>

<div class="container od">
    <div class="od-head">
        <div>
            <h1>অর্ডার #{{ $order->order_number }}</h1>
            <div class="od-date">{{ $order->created_at->timezone('Asia/Dhaka')->format('d M Y, h:i A') }}</div>
        </div>
        <span class="od-pill @if($isCancelled) is-cancelled @elseif($order->status === \App\Models\Order::STATUS_DELIVERED) is-delivered @endif">{{ $order->statusLabelBn() }}</span>
    </div>

    @include('storefront.partials.order-actions', ['order' => $order])

    <div class="od-card">
        <div class="od-card-title">অর্ডারের অবস্থা</div>
        @if($isCancelled)
            <p class="od-cancel-msg">এই অর্ডারটি বাতিল করা হয়েছে।</p>
        @else
            <div class="od-track">
                <div class="od-track-fill" style="width: {{ $currentStep * 76 / 3 }}%;"></div>
                @foreach($steps as $key => $label)
                    <div class="od-step @if($loop->index <= $currentStep) done @endif">
                        <div class="od-dot">@if($loop->index <= $currentStep)✓@endif</div>
                        {{ $label }}
                    </div>
                @endforeach
            </div>
        @endif
        @if($order->tracking_number)
            <div class="od-tracking">কুরিয়ার: <strong>{{ $order->carrier ?: '—' }}</strong> &nbsp;|&nbsp; ট্র্যাকিং নম্বর: <strong>{{ $order->tracking_number }}</strong></div>
        @endif
    </div>

    <div class="od-card">
        <div class="od-card-title">প্রোডাক্ট ({{ $order->items->sum('quantity') }}টি)</div>
        @foreach($order->items as $item)
            <div class="od-item">
                <img src="{{ media_thumb($item->displayImage(), 300) }}" alt="" loading="lazy">
                <div class="od-item-info">
                    <div class="od-item-name">{{ $item->product_name }}</div>
                    <div class="od-item-meta">
                        @if($item->option_summary){{ $item->option_summary }} · @endif{{ $item->quantity }} × {{ money_bd($item->unit_price) }}
                    </div>
                </div>
                <div class="od-item-price">{{ money_bd($item->total) }}</div>
            </div>
        @endforeach
    </div>

    <div class="od-grid">
        <div class="od-card">
            <div class="od-card-title">টাকার হিসাব</div>
            <div class="od-row"><span>সাবটোটাল</span><span>{{ money_bd($order->subtotal) }}</span></div>
            @if((float) $order->discount > 0)
                <div class="od-row"><span>ছাড়@if($order->coupon_code) ({{ $order->coupon_code }})@endif</span><span class="minus">−{{ money_bd($order->discount) }}</span></div>
            @endif
            @if((float) $order->reward_discount > 0)
                <div class="od-row"><span>রিওয়ার্ড পয়েন্ট ({{ $order->reward_points_used }})</span><span class="minus">−{{ money_bd($order->reward_discount) }}</span></div>
            @endif
            <div class="od-row"><span>ডেলিভারি চার্জ</span><span>{{ (float) $order->shipping_cost > 0 ? money_bd($order->shipping_cost) : 'ফ্রি' }}</span></div>
            @if((float) $order->tax_amount > 0)
                <div class="od-row"><span>ট্যাক্স</span><span>{{ money_bd($order->tax_amount) }}</span></div>
            @endif
            <div class="od-row total"><span>মোট</span><span>{{ money_bd($order->total) }}</span></div>
            <div class="od-row" style="font-size:13px;color:#777;"><span>পেমেন্ট</span><span style="text-align:right;">{{ $order->paymentLabelBn() }}</span></div>
        </div>

        <div class="od-card od-info">
            <div class="od-card-title">ডেলিভারি তথ্য</div>
            <p><strong>{{ $order->customer_name }}</strong></p>
            <p>{{ preg_replace('/^\+?880/', '0', (string) $order->customer_phone) }}</p>
            <p>{!! nl2br(e($order->shipping_address)) !!}</p>
            @if($order->shipping_method)
                <p>এলাকা: {{ $shippingNames[$order->shipping_method] ?? $order->shipping_method }}</p>
            @endif
            @if($order->notes)
                <p>মন্তব্য: {{ $order->notes }}</p>
            @endif
        </div>
    </div>

    <div class="od-actions">
        <a href="{{ $order->invoiceUrl() }}" class="order-btn order-btn-secondary" target="_blank" rel="noopener">ইনভয়েস ডাউনলোড</a>
        @if($waLink)
            <a href="{{ $waLink }}" class="order-btn order-btn-wa" target="_blank" rel="noopener">WhatsApp-এ যোগাযোগ</a>
        @endif
        @auth
            <form method="post" action="{{ route('orders.reorder', $order) }}">@csrf<button class="order-btn order-btn-secondary" type="submit">আবার অর্ডার করুন</button></form>
        @endauth
        @if($order->status === \App\Models\Order::STATUS_SHIPPED)
            <form method="post" action="{{ route('orders.confirm-delivery', $order) }}">@csrf<button class="order-btn" type="submit">প্রোডাক্ট হাতে পেয়েছি</button></form>
        @endif
    </div>

    <a class="od-back" href="{{ auth()->check() ? route('account.orders') : route('home') }}">← {{ auth()->check() ? 'আমার সব অর্ডার' : 'হোম পেজে ফিরুন' }}</a>
</div>
@endsection
