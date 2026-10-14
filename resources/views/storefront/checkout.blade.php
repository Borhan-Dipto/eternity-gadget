@extends('layouts.storefront')

@section('title', __('messages.checkout'))

@section('content')
@if($cartItems->isNotEmpty())
    @push('meta_pixel')
        <script>window.egTrack('InitiateCheckout', @json(\App\Support\MetaPixel::cartParams($cartItems)), @json(\App\Support\MetaPixel::newEventId('initiate_checkout')));</script>
    @endpush
@endif
@php
    $defaultShippingCode = old(
        'shipping_method',
        $shippingMethods->sortByDesc('base_cost')->first()?->code ?? $shippingMethods->first()?->code
    );
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
        'terms' => 'I have read and agree to the Terms & Conditions',
        'confirm_order' => 'অর্ডার কনফার্ম করুন',
        'your_order' => 'আপনার অর্ডার',
        'sub_total' => 'Sub-Total',
        'total' => 'Total',
    ];

    $shippingDisplayNames = [
        'inside_dhaka' => 'ঢাকা শহরের ভিতরে',
        'outside_dhaka' => 'ঢাকা শহরের বাইরে',
        'standard' => 'ঢাকা শহরের ভিতরে',
        'express' => 'ঢাকা শহরের বাইরে',
    ];

    $defaultShippingLabel = $shippingDisplayNames[$defaultShippingCode]
        ?? $shippingMethods->firstWhere('code', $defaultShippingCode)?->name
        ?? 'Shipping';
@endphp
<style>
    .co-page {
        padding: 24px 0 56px;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
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
        background: #e57339;
        color: #fff;
        border: 0;
        border-radius: 4px;
        padding: 16px 20px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: background .15s;
    }

    .co-btn-submit:hover { background: #d4652e; }

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
            <a href="{{ route('home') }}" class="co-btn-submit" style="display:inline-block;width:auto;margin-top:16px;text-decoration:none;">Continue Shopping</a>
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
                                <input type="radio" name="shipping_method" value="{{ $method->code }}" data-name="{{ $methodLabel }}" data-cost="{{ $method->base_cost }}" @checked(old('shipping_method', $defaultShippingCode) === $method->code)>
                                <span class="co-radio-label">{{ $methodLabel }}</span>
                                <span class="co-radio-price">{{ money_bd($method->base_cost) }}</span>
                            </label>
                        @endforeach
                    </div>

                    <h2 class="co-section-title">{{ $co['select_payment'] }}</h2>
                    <div class="co-radio-list">
                        @if(setting('cod_enabled', '1') === '1')
                            <label class="co-radio-option">
                                <input type="radio" name="payment_method" value="cod" @checked(old('payment_method', 'cod') === 'cod')>
                                <span class="co-radio-label">{{ $co['cod'] }}</span>
                            </label>
                        @endif
                    </div>

                    <label class="co-terms">
                        <input type="checkbox" name="terms_agreed" value="1" @checked(old('terms_agreed')) required>
                        <span>{!! str_replace('Terms & Conditions', '<a href="' . route('page.show', 'terms-conditions') . '" target="_blank">Terms & Conditions</a>', e($co['terms'])) !!}</span>
                    </label>

                    <button class="co-btn-submit" type="submit" id="checkout-submit-btn">
                        {{ $co['confirm_order'] }} ({{ money_bd($initialTotal) }})
                    </button>
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
                                <button type="submit" class="co-remove" aria-label="Remove item">&times;</button>
                            </form>

                            <div class="co-product-row">
                                <img class="co-product-thumb" src="{{ media_url($item['product']->primaryImage()) }}" alt="{{ $item['product']->name }}">
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
                                        <button type="button" class="co-qty-minus" aria-label="Decrease quantity">&minus;</button>
                                        <span class="co-qty-display">{{ $item['quantity'] }}</span>
                                        <button type="button" class="co-qty-plus" aria-label="Increase quantity">+</button>
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
                        <div class="co-total-line">
                            <span id="summary-shipping-label">{{ $defaultShippingLabel }}</span>
                            <span id="summary-shipping">{{ money_bd($shippingEstimate) }}</span>
                        </div>
                        <div class="co-total-line grand">
                            <span>{{ $co['total'] }}</span>
                            <span id="summary-total">{{ money_bd($initialTotal) }}</span>
                        </div>
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
    const taxEstimate = {{ (float) $taxEstimate }};
    const confirmLabel = @json($co['confirm_order']);

    function formatMoney(amount) {
        return '৳' + Number(amount).toLocaleString('en-BD', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }

    function updateSummary() {
        const selectedShipping = document.querySelector('input[name="shipping_method"]:checked');
        const shipping = selectedShipping ? parseFloat(selectedShipping.dataset.cost || '0') : 0;
        const total = subtotal + shipping + taxEstimate;

        document.getElementById('summary-shipping').textContent = formatMoney(shipping);
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
