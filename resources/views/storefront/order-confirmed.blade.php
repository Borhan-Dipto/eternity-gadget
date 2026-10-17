@extends('layouts.storefront')

@section('title', 'অর্ডার সম্পন্ন')

@section('content')
@if($trackPurchase ?? false)
    @push('meta_pixel')
        <script>window.egTrack('Purchase', @json(\App\Support\MetaPixel::orderParams($order)), @json(\App\Support\MetaPixel::purchaseEventId($order)));</script>
    @endpush
@endif
@php
    $statusLabel = $order->statusLabelBn();
    $showGuestSignup = auth()->guest() && ! $order->user_id;
@endphp

<style>
    .oc-page {
        padding: 24px 0 56px;
        font-family: var(--font-sans);
    }

    .oc-wrap {
        max-width: 640px;
        margin: 0 auto;
    }

    .oc-success {
        text-align: center;
        background: #f0faf3;
        border: 1px solid #c8e6c9;
        border-radius: 8px;
        padding: 28px 20px;
        margin-bottom: 24px;
    }

    .oc-success-icon {
        width: 52px;
        height: 52px;
        margin: 0 auto 14px;
        border-radius: 50%;
        background: #1a5c32;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 26px;
        line-height: 1;
    }

    .oc-success h1 {
        font-size: 22px;
        font-weight: 700;
        margin: 0 0 8px;
        color: #111;
    }

    .oc-success p {
        margin: 0;
        font-size: 14px;
        color: #444;
        line-height: 1.5;
    }

    .oc-order-meta {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 10px 20px;
        margin-top: 14px;
        font-size: 14px;
    }

    .oc-order-meta strong { color: #111; }

    .oc-status {
        display: inline-block;
        background: #fff3e0;
        color: #e65100;
        padding: 3px 10px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .3px;
    }

    .oc-card {
        border: 1px solid #e0e0e0;
        background: #fff;
        border-radius: 8px;
        overflow: hidden;
        margin-bottom: 20px;
    }

    .oc-card-title {
        background: #f5f5f5;
        padding: 12px 18px;
        font-size: 14px;
        font-weight: 700;
        color: #111;
        border-bottom: 1px solid #e8e8e8;
        text-transform: uppercase;
        letter-spacing: .4px;
    }

    .oc-item {
        display: flex;
        gap: 14px;
        padding: 16px 18px;
        border-bottom: 1px solid #eee;
        align-items: flex-start;
    }

    .oc-item:last-child { border-bottom: 0; }
    .oc-item-img { width: 56px; height: 56px; object-fit: cover; border-radius: 12px; background: #f3f3f3; flex-shrink: 0; margin-right: 12px; }

    .oc-item-body { flex: 1; min-width: 0; }

    .oc-item-name {
        font-size: 14px;
        font-weight: 600;
        color: #111;
        line-height: 1.4;
        margin-bottom: 4px;
    }

    .oc-item-option {
        font-size: 12px;
        color: #666;
        margin-bottom: 6px;
    }

    .oc-item-qty {
        font-size: 12px;
        color: #888;
    }

    .oc-item-price {
        font-size: 14px;
        font-weight: 700;
        color: #111;
        white-space: nowrap;
        flex-shrink: 0;
    }

    .oc-totals {
        padding: 14px 18px;
        border-top: 1px solid #e8e8e8;
        background: #fafafa;
    }

    .oc-total-line {
        display: flex;
        justify-content: space-between;
        padding: 5px 0;
        font-size: 14px;
        color: #444;
    }

    .oc-total-line.grand {
        font-size: 17px;
        font-weight: 700;
        color: #111;
        border-top: 1px solid #e0e0e0;
        margin-top: 8px;
        padding-top: 12px;
    }

    .oc-actions {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-bottom: 28px;
    }

    .oc-btn {
        display: block;
        width: 100%;
        text-align: center;
        padding: 13px 20px;
        font-size: 14px;
        font-weight: 700;
        text-decoration: none;
        border-radius: 4px;
        cursor: pointer;
        border: 0;
        box-sizing: border-box;
        line-height: 1.2;
    }

    .oc-btn-primary {
        background: var(--brand);
        color: #fff !important;
    }

    .oc-btn-primary:hover { background: var(--brand-dark); }

    .oc-btn-secondary {
        background: #fff;
        color: #111 !important;
        border: 1px solid #111;
    }

    .oc-btn-secondary:hover { background: #f8f8f8; }

    .oc-btn-link {
        background: transparent;
        color: #666 !important;
        font-weight: 600;
        padding: 10px;
    }

    .oc-btn-link:hover { color: #f5924c !important; }

    .oc-signup {
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        background: #fff;
        padding: 24px 20px;
    }

    .oc-signup h2 {
        font-size: 17px;
        font-weight: 700;
        text-align: center;
        margin: 0 0 6px;
        color: #111;
    }

    .oc-signup-lead {
        text-align: center;
        font-size: 13px;
        color: #666;
        margin: 0 0 20px;
        line-height: 1.5;
    }

    .oc-field { margin-bottom: 12px; }

    .oc-field input {
        width: 100%;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 12px 14px;
        font-size: 15px;
        color: #111;
        background: #fafafa;
        box-sizing: border-box;
    }

    .oc-field input:focus {
        outline: none;
        border-color: #1a5c32;
        background: #fff;
    }

    .oc-checkbox {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        font-size: 13px;
        color: #444;
        margin: 16px 0 18px;
        cursor: pointer;
    }

    .oc-checkbox input {
        width: 18px;
        height: 18px;
        margin-top: 1px;
        flex-shrink: 0;
        accent-color: #1a5c32;
    }

    @media (max-width: 767px) {
        .oc-page { padding-left: 12px; padding-right: 12px; }
        .oc-actions .oc-btn { width: 70%; margin: 0 auto; }
        .oc-signup { padding: 20px 16px; }
    }
</style>

<div class="container oc-page">
    <div class="oc-wrap">
        <div class="oc-success">
            <div class="oc-success-icon" aria-hidden="true">&#10003;</div>
            <h1>ধন্যবাদ! আপনার অর্ডারটি সম্পন্ন হয়েছে</h1>
            <p>অর্ডার কনফার্ম করতে আমরা শীঘ্রই আপনাকে কল করব। পণ্য হাতে পেয়ে টাকা পরিশোধ করবেন।</p>
            <div class="oc-order-meta">
                <span>অর্ডার নম্বর <strong>{{ $order->order_number }}</strong></span>
                <span class="oc-status">{{ $statusLabel }}</span>
            </div>
        </div>

        <div class="oc-card">
            <div class="oc-card-title">অর্ডারের হিসাব</div>

            @foreach($order->items as $item)
                <div class="oc-item">
                    <img src="{{ media_thumb($item->displayImage(), 300) }}" alt="" class="oc-item-img" width="56" height="56">
                    <div class="oc-item-body">
                        <div class="oc-item-name">{{ $item->product_name }}</div>
                        @if($item->option_summary)
                            <div class="oc-item-option">{{ $item->option_summary }}</div>
                        @endif
                        <div class="oc-item-qty">পরিমাণ: {{ $item->quantity }} &times; {{ money_bd($item->unit_price) }}</div>
                    </div>
                    <div class="oc-item-price">{{ money_bd($item->total) }}</div>
                </div>
            @endforeach

            <div class="oc-totals">
                <div class="oc-total-line">
                    <span>সাবটোটাল</span>
                    <span>{{ money_bd($order->subtotal) }}</span>
                </div>
                @if((float) $order->discount > 0)
                    <div class="oc-total-line">
                        <span>ছাড়</span>
                        <span>−{{ money_bd($order->discount) }}</span>
                    </div>
                @endif
                @if((float) $order->reward_discount > 0)
                    <div class="oc-total-line">
                        <span>রিওয়ার্ড পয়েন্ট ছাড়</span>
                        <span>−{{ money_bd($order->reward_discount) }}</span>
                    </div>
                @endif
                <div class="oc-total-line">
                    <span>ডেলিভারি চার্জ</span>
                    <span>{{ (float) $order->shipping_cost > 0 ? money_bd($order->shipping_cost) : 'ফ্রি' }}</span>
                </div>
                @if((float) $order->tax_amount > 0)
                    <div class="oc-total-line">
                        <span>ট্যাক্স</span>
                        <span>{{ money_bd($order->tax_amount) }}</span>
                    </div>
                @endif
                <div class="oc-total-line grand">
                    <span>মোট</span>
                    <span>{{ money_bd($order->total) }}</span>
                </div>
            </div>
        </div>

        <div class="oc-actions">
            <a href="{{ route('orders.show', $order) }}" class="oc-btn oc-btn-primary">অর্ডার দেখুন</a>
            <a href="{{ $order->invoiceUrl() }}" class="oc-btn oc-btn-secondary" target="_blank" rel="noopener">ইনভয়েস ডাউনলোড</a>
            <a href="{{ route('home') }}" class="oc-btn oc-btn-link">আরও কেনাকাটা করুন</a>
        </div>

        <div style="margin-top:24px;">
            @include('storefront.partials.order-actions', ['order' => $order])
        </div>

        @if($showGuestSignup)
            <div class="oc-signup">
                <h2>অ্যাকাউন্ট খুলুন (ইচ্ছা হলে)</h2>
                <p class="oc-signup-lead">অর্ডার ট্র্যাক করুন, আগের অর্ডার দেখুন আর পরের বার দ্রুত অর্ডার করুন।</p>

                <form method="post" action="{{ route('account.convert-guest', $order) }}">
                    @csrf
                    <div class="oc-field">
                        <input type="text" name="name" value="{{ old('name', $order->customer_name) }}" placeholder="আপনার নাম" required autocomplete="name">
                    </div>
                    <div class="oc-field">
                        <input type="email" name="email" value="{{ old('email', $order->customer_email) }}" placeholder="ইমেইল ঠিকানা" required autocomplete="email">
                    </div>
                    @include('storefront.partials.phone-input', [
                        'name' => 'phone',
                        'value' => old('phone', $order->customer_phone),
                        'variant' => 'oc-field',
                    ])
                    <div class="oc-field">
                        <input type="password" name="password" placeholder="পাসওয়ার্ড" required autocomplete="new-password">
                    </div>
                    <div class="oc-field">
                        <input type="password" name="password_confirmation" placeholder="পাসওয়ার্ড আবার লিখুন" required autocomplete="new-password">
                    </div>
                    <label class="oc-checkbox">
                        <input type="checkbox" name="marketing_opt_in" value="1" @checked(old('marketing_opt_in'))>
                        <span>অফার ও আপডেট পেতে চাই</span>
                    </label>
                    <button type="submit" class="oc-btn oc-btn-primary">অ্যাকাউন্ট খুলুন</button>
                </form>
            </div>
        @endif
    </div>
</div>
@endsection
