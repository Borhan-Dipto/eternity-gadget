@extends('layouts.storefront')

@section('title', __('messages.checkout'))

@section('content')
@if($cartItems->isNotEmpty() && ! session()->hasOldInput() && ! $errors->any())
    {{-- Only on the first view; a reload after a form error is the same checkout, not a new one. --}}
    @push('meta_pixel')
        @php
            $icEventId = \App\Support\MetaPixel::newEventId('initiate_checkout');
            $icParams = \App\Support\MetaPixel::cartParams($cartItems);
            \App\Support\MetaConversions::track('InitiateCheckout', $icEventId, $icParams);
        @endphp
        <script>window.egTrack('InitiateCheckout', @json($icParams), @json($icEventId));</script>
    @endpush
@endif
@php
    $defaultShippingCode = old('shipping_method');
    $initialTotal = $subtotal + $shippingEstimate + $taxEstimate;

    $co = [
        'home' => 'Home',
        'shopping_cart' => 'Shopping Cart',
        'checkout' => 'Checkout',
        'form_title' => 'অর্ডার করতে নিচের ফর্মটি পূরণ করুন',
        'name_ph' => 'আপনার নাম লিখুন',
        'phone_ph' => '1XXXXXXXXX',
        'address_ph' => 'আপনার ঠিকানা লিখুন (থানা + জেলা) সহ',
        'notes_ph' => 'কোনো মন্তব্য থাকলে লিখুন',
        'select_area' => 'এলাকা নির্বাচন করুন',
        'select_payment' => 'পেমেন্ট পদ্ধতি নির্বাচন করুন',
        'cod' => 'ক্যাশ অন ডেলিভারি',
        'terms' => 'আমি শর্তাবলী পড়েছি এবং রাজি আছি',
        'confirm_order' => 'অর্ডার কনফার্ম করুন',
        'your_order' => 'আপনার অর্ডার',
        'sub_total' => 'সাবটোটাল',
        'total' => 'মোট',
    ];

    $shippingDisplayNames = [
        'inside_dhaka' => 'ঢাকা শহরের ভিতরে',
        'outside_dhaka' => 'ঢাকা শহরের বাইরে',
        'standard' => 'ঢাকা শহরের ভিতরে',
        'express' => 'ঢাকা শহরের বাইরে',
    ];

    $defaultShippingLabel = $shippingDisplayNames[$defaultShippingCode]
        ?? $shippingMethods->firstWhere('code', $defaultShippingCode)?->name
        ?? 'ডেলিভারি চার্জ';
@endphp
<style>
    .co-page {
        padding: 24px 0 56px;
        font-family: var(--font-sans);
    }

    .co-breadcrumb {
        text-align: center;
        margin-bottom: 28px;
        font-size: 14px;
        color: #999;
    }

    .co-breadcrumb a { color: #999; text-decoration: none; }
    .co-breadcrumb a:hover { color: #f5924c; }
    .co-breadcrumb .current { color: #f5924c; }
    .co-breadcrumb .sep { margin: 0 6px; color: #bbb; }

    .co-layout {
        display: flex;
        gap: 32px;
        align-items: flex-start;
    }

    .co-main { flex: 1 1 58%; min-width: 0; }

    .co-sidebar {
        flex: 0 0 42%;
        max-width: 42%;
        position: sticky;
        top: calc(var(--header-height, 84px) + 20px);
    }

    .co-form-title {
        text-align: center;
        font-size: 20px;
        font-weight: 700;
        margin: 0 0 22px;
        color: #111;
    }

    .co-input-wrap {
        display: flex;
        align-items: stretch;
        margin-bottom: 12px;
        background: #f3f3f3;
        border: 1px solid #e8e8e8;
    }

    .co-input-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 48px;
        flex-shrink: 0;
        background: #ebebeb;
        color: #666;
    }

    .co-input-icon svg { width: 18px; height: 18px; fill: currentColor; }

    .co-input-wrap input,
    .co-input-wrap textarea {
        flex: 1;
        border: 0;
        background: transparent;
        padding: 14px 14px;
        font-size: 14px;
        color: #111;
        resize: none;
        min-height: 48px;
        box-sizing: border-box;
    }

    .co-input-wrap textarea { min-height: 52px; padding-top: 14px; }

    .co-input-wrap input:focus,
    .co-input-wrap textarea:focus { outline: none; }

    .co-input-wrap input::placeholder,
    .co-input-wrap textarea::placeholder { color: #888; }

    #phone-feedback { font-size: 13px; min-height: 18px; margin: -4px 0 10px; }

    .co-phone-prefix {
        display: flex;
        align-items: center;
        padding: 0 14px;
        background: #ebebeb;
        color: #111;
        font-weight: 700;
        font-size: 14px;
        flex-shrink: 0;
        border-right: 1px solid #ddd;
    }

    .co-phone-input {
        letter-spacing: 0.5px;
    }

    .co-section-title {
        font-size: 15px;
        font-weight: 700;
        margin: 22px 0 12px;
        color: #111;
    }

    .co-radio-list {
        border: 1px solid #e0e0e0;
        background: #fff;
    }

    .co-radio-option {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 16px;
        border-bottom: 1px solid #eee;
        cursor: pointer;
        margin: 0;
    }

    .co-radio-option:last-child { border-bottom: 0; }

    .co-radio-option input[type="radio"] {
        width: 18px;
        height: 18px;
        margin: 0;
        flex-shrink: 0;
        accent-color: #4caf50;
        cursor: pointer;
    }

    .co-radio-label { flex: 1; font-size: 14px; color: #222; }

    .co-radio-price {
        font-size: 14px;
        color: #333;
        white-space: nowrap;
    }

    .co-terms {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        margin: 22px 0 18px;
        font-size: 14px;
        color: #333;
        cursor: pointer;
    }

    .co-terms input {
        width: 18px;
        height: 18px;
        margin-top: 2px;
        flex-shrink: 0;
        cursor: pointer;
    }

    .co-terms a { color: #333; text-decoration: underline; }

    .co-btn-submit {
        width: 100%;
        background: var(--brand);
        color: #fff;
        border: 0;
        border-radius: 4px;
        padding: 16px 20px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: background .15s;
    }

    .co-btn-submit:hover { background: var(--brand-dark); }

    .co-order-box {
        border: 1px solid #e0e0e0;
        background: #fff;
        padding: 22px 24px;
    }

    .co-order-title {
        text-align: center;
        font-size: 18px;
        font-weight: 700;
        margin: 0 0 18px;
        color: #111;
    }

    .co-product-card {
        background: #f5f5f5;
        padding: 14px;
        margin-bottom: 18px;
        position: relative;
    }

    .co-product-row {
        display: flex;
        gap: 12px;
        align-items: flex-start;
    }

    .co-product-thumb {
        width: 64px;
        height: 64px;
        object-fit: cover;
        background: #fff;
        border: 1px solid #e8e8e8;
        flex-shrink: 0;
    }

    .co-product-info { flex: 1; min-width: 0; padding-right: 28px; }

    .co-product-name {
        font-size: 13px;
        font-weight: 600;
        color: #111;
        line-height: 1.4;
        margin-bottom: 6px;
    }

    .co-product-options {
        list-style: none;
        margin: 0;
        padding: 0;
        font-size: 12px;
        color: #555;
        line-height: 1.5;
    }

    .co-product-options li::before { content: '- '; }

    .co-product-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 12px;
        gap: 12px;
    }

    .co-qty {
        display: inline-flex;
        align-items: stretch;
        border: 1px solid #ddd;
        background: #fff;
    }

    .co-qty button {
        width: 32px;
        border: 0;
        background: #f0f0f0;
        color: #333;
        font-size: 16px;
        line-height: 1;
        cursor: pointer;
        padding: 0;
    }

    .co-qty button.co-qty-plus { background: #111; color: #fff; }

    .co-qty button:hover { opacity: .85; }

    .co-qty span {
        min-width: 36px;
        text-align: center;
        line-height: 32px;
        font-size: 14px;
        border-left: 1px solid #ddd;
        border-right: 1px solid #ddd;
    }

    .co-product-prices {
        display: flex;
        gap: 16px;
        font-size: 14px;
        font-weight: 600;
        color: #111;
    }

    .co-remove {
        position: absolute;
        top: 10px;
        right: 10px;
        width: 26px;
        height: 26px;
        border: 1px solid #ccc;
        border-radius: 50%;
        background: #fff;
        color: #666;
        font-size: 16px;
        line-height: 1;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
    }

    .co-remove:hover { border-color: #999; color: #111; }

    .co-totals { border-top: 1px solid #eee; padding-top: 4px; }
    .co-reward { display: flex; gap: 10px; align-items: flex-start; margin: 18px 0 4px; padding: 12px 14px; border: 1px dashed #f5924c; background: #fff8f2; border-radius: 10px; cursor: pointer; font-size: 14px; line-height: 1.5; }
    .co-reward input { margin-top: 4px; width: 18px; height: 18px; accent-color: #1a7f45; flex-shrink: 0; }
    .co-reward small { display: block; color: #777; font-size: 12px; }
    .co-reward-info { cursor: default; border-style: solid; border-color: #f1e2d3; }
    .co-delivery-note { margin: 10px 2px 0; font-size: 13px; color: #555; text-align: center; }
    .co-policy-note { margin: 12px 2px 0; font-size: 12.5px; color: #666; text-align: center; line-height: 1.55; }
    .co-coupon { margin: 16px 0 0; }
    .co-coupon-toggle { background: none; border: 0; color: var(--brand); font-weight: 600; font-size: 14px; cursor: pointer; padding: 4px 0; font-family: inherit; }
    .co-coupon-row { display: flex; gap: 8px; }
    .co-coupon-row input { flex: 1; min-width: 0; padding: 11px 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; text-transform: uppercase; }
    .co-coupon-row button { padding: 0 16px; border: 0; border-radius: 8px; background: #111; color: #fff; font-size: 14px; font-family: inherit; cursor: pointer; }
    .co-coupon-msg { font-size: 13px; margin-top: 6px; }
    .co-coupon-msg.ok { color: #1a7f45; }
    .co-coupon-msg.err { color: #c0392b; }
    .co-free-note { margin-top: 10px; padding: 9px 12px; border-radius: 8px; background: #edf8f1; color: #1a5c32; font-size: 13px; text-align: center; }

    .co-total-line {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        font-size: 14px;
        color: #333;
    }

    .co-total-line.grand {
        font-size: 16px;
        font-weight: 700;
        color: #111;
        border-top: 1px solid #eee;
        margin-top: 4px;
        padding-top: 14px;
    }

    .co-empty {
        text-align: center;
        padding: 48px 20px;
        border: 1px solid #ddd;
        background: #fff;
    }

    @media (max-width: 991px) {
        .co-layout {
            flex-direction: column;
            align-items: center;
            max-width: 480px;
            margin: 0 auto;
        }
        .co-sidebar { flex: 1 1 auto; max-width: 100%; width: 100%; position: static; }
        .co-main { flex: 1 1 auto; width: 100%; }
        .co-form-title,
        .co-order-title,
        .co-section-title { text-align: center; }
    }

    @media (max-width: 767px) {
        .co-page { padding-left: 12px; padding-right: 12px; }
        .co-layout { max-width: 100%; }
        .co-input-wrap input,
        .co-input-wrap textarea { font-size: 16px; }
    }
</style>

<div class="container co-page">
    <nav class="co-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('home') }}">{{ $co['home'] }}</a>
        <span class="sep">&rsaquo;</span>
        <a href="{{ route('cart') }}">{{ $co['shopping_cart'] }}</a>
        <span class="sep">&rsaquo;</span>
        <span class="current">{{ $co['checkout'] }}</span>
    </nav>

    @if($cartItems->isEmpty())
        <div class="co-empty">
            <p>{{ __('messages.empty_cart') }}</p>
            <a href="{{ route('home') }}" class="co-btn-submit" style="display:inline-block;width:auto;margin-top:16px;text-decoration:none;">কেনাকাটা শুরু করুন</a>
        </div>
    @else
        <div class="co-layout">
            <div class="co-main">
                <form method="post" action="{{ route('checkout.place') }}" id="checkout-form" novalidate>
                    @csrf
                    <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">
                    <input type="hidden" name="customer_email" value="{{ old('customer_email', auth()->user()->email ?? '') }}">

                    <h1 class="co-form-title">{{ $co['form_title'] }}</h1>

                    <div class="co-input-wrap">
                        <span class="co-input-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
                        </span>
                        <input id="customer_name" name="customer_name" type="text" placeholder="{{ $co['name_ph'] }}" value="{{ old('customer_name', auth()->user()->name ?? $addresses->first()?->name ?? '') }}" required>
                    </div>

                    @include('storefront.partials.phone-input', [
                        'name' => 'customer_phone',
                        'id' => 'customer_phone',
                        'value' => old('customer_phone', auth()->user()->phone ?? ''),
                        'placeholder' => $co['phone_ph'],
                        'variant' => 'checkout',
                        'feedbackId' => 'phone-feedback',
                    ])
                    <div id="phone-feedback"></div>

                    <div class="co-input-wrap">
                        <span class="co-input-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M12 2C8.1 2 5 5.1 5 9c0 5.2 7 13 7 13s7-7.8 7-13c0-3.9-3.1-7-7-7zm0 9.5c-1.4 0-2.5-1.1-2.5-2.5S10.6 6.5 12 6.5s2.5 1.1 2.5 2.5S13.4 11.5 12 11.5z"/></svg>
                        </span>
                        <textarea id="shipping_address" name="shipping_address" rows="1" placeholder="{{ $co['address_ph'] }}" required>{{ old('shipping_address', $addresses->first() ? trim($addresses->first()->line1 . ', ' . $addresses->first()->city) : '') }}</textarea>
                    </div>

                    <div class="co-input-wrap">
                        <span class="co-input-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H5.2L4 17.2V4h16v12z"/></svg>
                        </span>
                        <textarea id="notes" name="notes" rows="1" placeholder="{{ $co['notes_ph'] }}">{{ old('notes') }}</textarea>
                    </div>

                    <h2 class="co-section-title">{{ $co['select_area'] }}</h2>
                    <div class="co-radio-list">
                        @foreach($shippingMethods as $method)
                            @php
                                $methodLabel = $shippingDisplayNames[$method->code] ?? $method->name;
                            @endphp
                            <label class="co-radio-option">
                                <input type="radio" name="shipping_method" value="{{ $method->code }}" data-name="{{ $methodLabel }}" data-cost="{{ $shippingCosts[$method->code] ?? $method->base_cost }}" @checked($defaultShippingCode === $method->code) @if($loop->first) required @endif>
                                <span class="co-radio-label">{{ $methodLabel }}</span>
                                <span class="co-radio-price">@if($freeDelivery)<s style="color:#999;font-weight:400;">{{ money_bd($method->base_cost) }}</s> <b style="color:#1a7f45;">ফ্রি</b>@else{{ money_bd($shippingCosts[$method->code] ?? $method->base_cost) }}@endif</span>
                            </label>
                        @endforeach
                    </div>
                    @if(setting('delivery_time_text'))
                        <p class="co-delivery-note">🚚 ডেলিভারি সময়: {{ setting('delivery_time_text') }}</p>
                    @endif

                    <h2 class="co-section-title">{{ $co['select_payment'] }}</h2>
                    <div class="co-radio-list">
                        @if(setting('cod_enabled', '1') === '1')
                            <label class="co-radio-option">
                                <input type="radio" name="payment_method" value="cod" @checked(old('payment_method', 'cod') === 'cod')>
                                <span class="co-radio-label">{{ $co['cod'] }}</span>
                            </label>
                        @endif
                    </div>

                    @if(\App\Models\Coupon::query()->where('is_active', true)->exists())
                        <div class="co-coupon" id="co-coupon">
                            <button type="button" class="co-coupon-toggle" id="co-coupon-toggle" @if(old('coupon_code')) hidden @endif>🏷️ কুপন কোড আছে?</button>
                            <div class="co-coupon-row" id="co-coupon-row" @if(! old('coupon_code')) hidden @endif>
                                <input type="text" id="co-coupon-input" value="{{ old('coupon_code') }}" placeholder="কুপন কোড লিখুন" autocomplete="off" autocapitalize="characters">
                                <button type="button" id="co-coupon-apply">প্রয়োগ করুন</button>
                            </div>
                            <input type="hidden" name="coupon_code" id="co-coupon-code" value="{{ old('coupon_code') }}">
                            <div class="co-coupon-msg" id="co-coupon-msg" aria-live="polite"></div>
                        </div>
                    @endif

                    @if($reward['enabled'])
                        @if($reward['redeemable'] > 0)
                            <label class="co-reward">
                                <input type="checkbox" name="use_points" value="1" id="use-points" data-discount="{{ $reward['discount'] }}" @checked(old('use_points'))>
                                <span>
                                    <strong>{{ $reward['redeemable'] }} রিওয়ার্ড পয়েন্ট ব্যবহার করে {{ money_bd($reward['discount']) }} ছাড় নিন</strong>
                                    <small>আপনার মোট পয়েন্ট: {{ $reward['balance'] }}</small>
                                </span>
                            </label>
                        @elseif($reward['earn'] > 0)
                            <div class="co-reward co-reward-info">
                                <span>🎁</span>
                                <span>
                                    এই অর্ডারটি ডেলিভারি হলে <strong>{{ $reward['earn'] }} রিওয়ার্ড পয়েন্ট</strong> পাবেন@guest — <a href="{{ route('account.login') }}">লগইন</a> বা অর্ডারের পরে অ্যাকাউন্ট খুলুন@endguest।
                                    @auth @if($reward['balance'] > 0)<small>আপনার পয়েন্ট: {{ $reward['balance'] }} ({{ $reward['min'] }} পয়েন্ট হলে ব্যবহার করতে পারবেন)</small>@endif @endauth
                                </span>
                            </div>
                        @endif
                    @endif

                    <label class="co-terms">
                        <input type="checkbox" name="terms_agreed" value="1" @checked(old('terms_agreed')) required>
                        <span>{!! str_replace('শর্তাবলী', '<a href="' . route('page.show', 'terms-conditions') . '" target="_blank">শর্তাবলী</a>', e($co['terms'])) !!}</span>
                    </label>

                    <button class="co-btn-submit" type="submit" id="checkout-submit-btn">
                        {{ $co['confirm_order'] }} ({{ money_bd($initialTotal) }})
                    </button>
                    @if(setting('return_policy_text'))
                        <p class="co-policy-note">↩️ {{ setting('return_policy_text') }}</p>
                    @endif
                </form>
            </div>

            <aside class="co-sidebar">
                <div class="co-order-box">
                    <h2 class="co-order-title">{{ $co['your_order'] }}</h2>

                    @foreach($cartItems as $item)
                        @php
                            $optionLines = $item['option_summary']
                                ? collect(preg_split('/,\s*/', $item['option_summary']))->filter()
                                : collect();
                        @endphp
                        <div class="co-product-card" data-cart-key="{{ $item['cart_key'] }}" data-unit-price="{{ $item['unit_price'] }}">
                            <form method="post" action="{{ route('cart.remove') }}" class="co-remove-form">
                                @csrf
                                <input type="hidden" name="cart_key" value="{{ $item['cart_key'] }}">
                                <button type="submit" class="co-remove" aria-label="সরান">&times;</button>
                            </form>

                            <div class="co-product-row">
                                <img class="co-product-thumb" src="{{ media_thumb($item['image_path'] ?? $item['product']->primaryImage(), 300) }}" alt="{{ $item['product']->name }}">
                                <div class="co-product-info">
                                    <div class="co-product-name">{{ $item['product']->name }}</div>
                                    @if($optionLines->isNotEmpty())
                                        <ul class="co-product-options">
                                            @foreach($optionLines as $line)
                                                <li>{{ $line }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            </div>

                            <div class="co-product-actions">
                                <form method="post" action="{{ route('cart.update') }}" class="co-qty-form">
                                    @csrf
                                    <input type="hidden" name="quantities[{{ $item['cart_key'] }}]" value="{{ $item['quantity'] }}" class="co-qty-input">
                                    <div class="co-qty">
                                        <button type="button" class="co-qty-minus" aria-label="কমান">&minus;</button>
                                        <span class="co-qty-display">{{ $item['quantity'] }}</span>
                                        <button type="button" class="co-qty-plus" aria-label="বাড়ান">+</button>
                                    </div>
                                </form>
                                <div class="co-product-prices">
                                    <span class="co-unit-price">{{ money_bd($item['unit_price']) }}</span>
                                    <span class="co-line-total">{{ money_bd($item['line_total']) }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <div class="co-totals">
                        <div class="co-total-line">
                            <span>{{ $co['sub_total'] }}</span>
                            <span id="summary-subtotal">{{ money_bd($subtotal) }}</span>
                        </div>
                        <div class="co-total-line" id="summary-coupon-line" hidden>
                            <span>কুপন ছাড় <small id="summary-coupon-code"></small></span>
                            <span id="summary-coupon" style="color:#1a7f45;"></span>
                        </div>
                        <div class="co-total-line" id="summary-reward-line" hidden>
                            <span>রিওয়ার্ড পয়েন্ট ছাড়</span>
                            <span id="summary-reward" style="color:#1a7f45;">−{{ money_bd($reward['discount']) }}</span>
                        </div>
                        <div class="co-total-line">
                            <span id="summary-shipping-label">{{ $defaultShippingCode ? $defaultShippingLabel : 'ডেলিভারি চার্জ' }}</span>
                            <span id="summary-shipping">{{ $defaultShippingCode ? money_bd($shippingEstimate) : 'এলাকা নির্বাচন করুন' }}</span>
                        </div>
                        @if((float) $taxEstimate > 0)
                            <div class="co-total-line">
                                <span>ট্যাক্স ({{ rtrim(rtrim(number_format((float) setting('tax_rate', '0'), 2), '0'), '.') }}%)</span>
                                <span id="summary-tax">{{ money_bd($taxEstimate) }}</span>
                            </div>
                        @endif
                        <div class="co-total-line grand">
                            <span>{{ $co['total'] }}</span>
                            <span id="summary-total">{{ money_bd($initialTotal) }}</span>
                        </div>
                        @if($freeDelivery)
                            <div class="co-free-note">🎉 আপনার অর্ডারে ডেলিভারি চার্জ ফ্রি!</div>
                        @elseif($freeDeliveryMin > 0)
                            <div class="co-free-note">আর মাত্র <strong>{{ money_bd($freeDeliveryMin - $subtotal) }}</strong> এর প্রোডাক্ট নিলে ডেলিভারি চার্জ ফ্রি!</div>
                        @endif
                    </div>
                </div>
            </aside>
        </div>
    @endif
</div>

@if($cartItems->isNotEmpty())
<script>
(function () {
    const form = document.getElementById('checkout-form');
    const submitBtn = document.getElementById('checkout-submit-btn');
    const subtotal = {{ (float) $subtotal }};
    const taxRate = {{ (float) setting('tax_rate', '0') }};
    const pointsBox = document.getElementById('use-points');
    let couponDiscount = 0;
    const confirmLabel = @json($co['confirm_order']);

    function formatMoney(amount) {
        return '৳' + Number(amount).toLocaleString('en-BD', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }

    function updateSummary() {
        const selectedShipping = document.querySelector('input[name="shipping_method"]:checked');
        const shipping = selectedShipping ? parseFloat(selectedShipping.dataset.cost || '0') : 0;
        const rewardDiscount = pointsBox && pointsBox.checked ? parseFloat(pointsBox.dataset.discount || '0') : 0;
        const tax = Math.round(Math.max(0, subtotal - couponDiscount - rewardDiscount) * taxRate / 100);
        const total = Math.max(0, subtotal - couponDiscount - rewardDiscount) + shipping + tax;
        const couponLine = document.getElementById('summary-coupon-line');
        if (couponLine) {
            couponLine.hidden = couponDiscount <= 0;
            document.getElementById('summary-coupon').textContent = '−' + formatMoney(couponDiscount);
        }
        const taxEl = document.getElementById('summary-tax');
        if (taxEl) taxEl.textContent = formatMoney(tax);
        const rewardLine = document.getElementById('summary-reward-line');
        if (rewardLine) rewardLine.hidden = rewardDiscount <= 0;

        document.getElementById('summary-shipping').textContent = selectedShipping ? (shipping > 0 ? formatMoney(shipping) : 'ফ্রি') : 'এলাকা নির্বাচন করুন';
        document.getElementById('summary-total').textContent = formatMoney(total);

        const label = document.getElementById('summary-shipping-label');
        if (label && selectedShipping) {
            label.textContent = selectedShipping.dataset.name || label.textContent;
        }

        if (submitBtn) {
            submitBtn.textContent = confirmLabel + ' (' + formatMoney(total) + ')';
        }
    }

    document.querySelectorAll('input[name="shipping_method"]').forEach((radio) => {
        radio.addEventListener('change', updateSummary);
    });
    pointsBox?.addEventListener('change', updateSummary);

    // Coupon: checked on the server, then the totals update (the order is checked again when placed)
    const couponCode = document.getElementById('co-coupon-code');
    const couponInput = document.getElementById('co-coupon-input');
    const couponMsg = document.getElementById('co-coupon-msg');
    document.getElementById('co-coupon-toggle')?.addEventListener('click', (e) => {
        e.currentTarget.hidden = true;
        document.getElementById('co-coupon-row').hidden = false;
        couponInput?.focus();
    });
    async function applyCoupon() {
        const code = (couponInput?.value || '').trim();
        if (!code) { couponMsg.textContent = 'কুপন কোড লিখুন।'; couponMsg.className = 'co-coupon-msg err'; return; }
        couponMsg.textContent = 'যাচাই করা হচ্ছে...'; couponMsg.className = 'co-coupon-msg';
        try {
            const res = await fetch('{{ route('api.checkout.coupon') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                body: JSON.stringify({ code }),
            });
            const data = await res.json();
            if (!data.ok) {
                couponDiscount = 0; couponCode.value = '';
                couponMsg.textContent = data.message || 'কুপন কোডটি সঠিক নয়।'; couponMsg.className = 'co-coupon-msg err';
            } else {
                couponDiscount = parseFloat(data.discount || 0); couponCode.value = data.code;
                document.getElementById('summary-coupon-code').textContent = '(' + data.code + ')';
                couponMsg.textContent = '✓ ' + data.message; couponMsg.className = 'co-coupon-msg ok';
                if (pointsBox) pointsBox.dataset.discount = data.reward_discount || 0;
            }
        } catch (err) {
            couponMsg.textContent = 'এখন যাচাই করা যাচ্ছে না, আবার চেষ্টা করুন।'; couponMsg.className = 'co-coupon-msg err';
        }
        updateSummary();
    }
    document.getElementById('co-coupon-apply')?.addEventListener('click', applyCoupon);
    couponInput?.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); applyCoupon(); } });
    if (couponCode && couponCode.value) applyCoupon();
    updateSummary();

    document.querySelectorAll('.co-qty-form').forEach((qtyForm) => {
        const input = qtyForm.querySelector('.co-qty-input');
        const display = qtyForm.querySelector('.co-qty-display');
        const card = qtyForm.closest('.co-product-card');
        const unitPrice = parseFloat(card?.dataset.unitPrice || '0');

        qtyForm.querySelector('.co-qty-minus')?.addEventListener('click', () => {
            const current = parseInt(input.value, 10) || 1;
            if (current <= 1) return;
            input.value = current - 1;
            display.textContent = input.value;
            qtyForm.submit();
        });

        qtyForm.querySelector('.co-qty-plus')?.addEventListener('click', () => {
            input.value = (parseInt(input.value, 10) || 1) + 1;
            display.textContent = input.value;
            qtyForm.submit();
        });
    });

    form?.addEventListener('submit', (e) => {
        if (!form.checkValidity()) {
            e.preventDefault();
            form.reportValidity();
        }
    });
})();
</script>
@endif
@endsection
