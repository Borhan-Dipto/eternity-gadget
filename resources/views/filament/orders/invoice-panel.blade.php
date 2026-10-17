<div class="order-invoice-panel">
    @php
        $order = $getRecord();
        $order->loadMissing(['items.product.images']);
    @endphp
    <style>
        .order-invoice-panel {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 28px;
            color: #111;
        }
        .order-invoice-panel .inv-top {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            flex-wrap: wrap;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid #111;
        }
        .order-invoice-panel .inv-brand {
            font-size: 1.5rem;
            font-weight: 800;
            margin: 0 0 4px;
        }
        .order-invoice-panel .inv-muted { color: #6b7280; font-size: .9rem; line-height: 1.5; }
        .order-invoice-panel .inv-meta { text-align: right; }
        .order-invoice-panel .inv-meta h2 { margin: 0 0 6px; font-size: 1.25rem; letter-spacing: .04em; }
        .order-invoice-panel .inv-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }
        .order-invoice-panel .inv-box h3 {
            margin: 0 0 8px;
            font-size: .8rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #6b7280;
        }
        .order-invoice-panel table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .order-invoice-panel th,
        .order-invoice-panel td {
            border: 1px solid #e5e7eb;
            padding: 10px 12px;
            text-align: left;
            vertical-align: middle;
            font-size: .92rem;
        }
        .order-invoice-panel th { background: #f9fafb; font-weight: 700; }
        .order-invoice-panel .inv-thumb {
            width: 48px;
            height: 48px;
            object-fit: cover;
            border-radius: 8px;
            background: #f3f4f6;
        }
        .order-invoice-panel .inv-totals {
            width: 280px;
            margin-left: auto;
        }
        .order-invoice-panel .inv-totals td { border: 0; padding: 6px 0; }
        .order-invoice-panel .inv-totals .label { color: #6b7280; }
        .order-invoice-panel .inv-totals .value { text-align: right; font-weight: 700; }
        .order-invoice-panel .inv-totals .grand td {
            border-top: 2px solid #111;
            padding-top: 10px;
            font-size: 1.05rem;
        }
        @media (max-width: 768px) {
            .order-invoice-panel .inv-grid { grid-template-columns: 1fr; }
            .order-invoice-panel .inv-meta { text-align: left; }
        }
    </style>

    <div class="inv-top">
        <div>
            <p class="inv-brand">{{ setting('site_name', 'Eternity Gadget') }}</p>
            <div class="inv-muted">
                {{ setting('address') }}<br>
                {{ setting('phone') }}<br>
                {{ setting('email') }}
            </div>
        </div>
        <div class="inv-meta">
            <h2>INVOICE</h2>
            <div><strong>#{{ $order->order_number }}</strong></div>
            <div class="inv-muted">{{ $order->created_at?->format('d M Y, h:i A') }}</div>
            <div class="inv-muted">Status: {{ ucfirst($order->status) }}</div>
            <div class="inv-muted">Payment: {{ strtoupper($order->payment_method ?: 'N/A') }} ({{ $order->payment_status }})</div>
        </div>
    </div>

    <div class="inv-grid">
        <div class="inv-box">
            <h3>Bill To</h3>
            <div>
                <strong>{{ $order->customer_name }}</strong><br>
                {{ $order->customer_phone }}<br>
                @if($order->customer_email){{ $order->customer_email }}<br>@endif
                {!! nl2br(e($order->shipping_address)) !!}
            </div>
        </div>
        <div class="inv-box">
            <h3>Shipping</h3>
            <div>
                {{ $order->shipping_method ?: 'Standard' }}
                @if($order->notes)
                    <br><br><strong>Notes</strong><br>{{ $order->notes }}
                @endif
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:64px;">Image</th>
                <th>Product</th>
                <th>Options</th>
                <th style="width:60px;">Qty</th>
                <th style="width:110px;">Unit</th>
                <th style="width:110px;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td>
                        <img class="inv-thumb" src="{{ media_url($item->displayImage()) }}" alt="">
                    </td>
                    <td>{{ $item->product_name }}</td>
                    <td>{{ $item->option_summary ?: '—' }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ money_bd($item->unit_price) }}</td>
                    <td>{{ money_bd($item->total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="inv-totals">
        <tr><td class="label">Subtotal</td><td class="value">{{ money_bd($order->subtotal) }}</td></tr>
        @if($order->discount > 0)
            <tr><td class="label">Discount</td><td class="value">-{{ money_bd($order->discount) }}</td></tr>
        @endif
        <tr><td class="label">Shipping</td><td class="value">{{ money_bd($order->shipping_cost) }}</td></tr>
        @if($order->tax_amount > 0)
            <tr><td class="label">Tax</td><td class="value">{{ money_bd($order->tax_amount) }}</td></tr>
        @endif
        <tr class="grand"><td class="label">Total</td><td class="value">{{ money_bd($order->total) }}</td></tr>
    </table>
</div>
