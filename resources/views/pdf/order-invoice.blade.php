<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        .brand { font-size: 22px; font-weight: bold; margin: 0 0 4px; }
        .muted { color: #666; }
        .header-table, .items-table, .totals-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: top; padding: 0 0 12px; }
        .items-table th, .items-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .items-table th { background: #f4f4f4; }
        .totals-table { width: 280px; margin-left: auto; margin-top: 16px; }
        .totals-table td { padding: 4px 0; }
        .totals-table .label { color: #555; }
        .totals-table .value { text-align: right; font-weight: bold; }
        .total-row td { font-size: 14px; padding-top: 8px; border-top: 1px solid #ccc; }
        .thumb { width: 42px; height: 42px; object-fit: cover; border-radius: 4px; }
        h2 { margin: 0 0 8px; font-size: 16px; }
    </style>
</head>
<body>
@php
    $fmt = fn ($amount) => 'Tk ' . number_format((float) $amount, 0);
@endphp

<table class="header-table">
    <tr>
        <td>
            <p class="brand">{{ setting('site_name', 'Eternity Gadget') }}</p>
            <div class="muted">{{ setting('address') }}</div>
            <div class="muted">{{ setting('phone') }}</div>
            <div class="muted">{{ setting('email') }}</div>
        </td>
        <td style="text-align:right;">
            <h2>INVOICE</h2>
            <div><strong>#{{ $order->order_number }}</strong></div>
            <div class="muted">Date: {{ $order->created_at?->format('d M Y, h:i A') }}</div>
            <div class="muted">Status: {{ ucfirst($order->status) }}</div>
            <div class="muted">Payment: {{ strtoupper($order->payment_method ?: 'N/A') }} ({{ $order->payment_status }})</div>
        </td>
    </tr>
</table>

<table class="header-table">
    <tr>
        <td>
            <strong>Bill To</strong><br>
            {{ $order->customer_name }}<br>
            {{ $order->customer_phone }}<br>
            @if($order->customer_email){{ $order->customer_email }}<br>@endif
            {!! nl2br(e($order->shipping_address)) !!}
        </td>
        <td>
            <strong>Shipping</strong><br>
            {{ $order->shipping_method ?: 'Standard' }}
            @if($order->notes)
                <br><br><strong>Notes</strong><br>{{ $order->notes }}
            @endif
        </td>
    </tr>
</table>

<table class="items-table">
    <thead>
        <tr>
            <th style="width:52px;">Image</th>
            <th>Product</th>
            <th>Options</th>
            <th style="width:50px;">Qty</th>
            <th style="width:90px;">Unit</th>
            <th style="width:90px;">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($order->items as $item)
            @php
                $imagePath = $item->product?->primaryImage();
                $imageSrc = null;
                if ($imagePath) {
                    if (\Illuminate\Support\Str::startsWith($imagePath, ['http://', 'https://', '//'])) {
                        $imageSrc = $imagePath;
                    } elseif (\Illuminate\Support\Facades\Storage::disk('public')->exists($imagePath)) {
                        $imageSrc = storage_path('app/public/' . ltrim($imagePath, '/'));
                    }
                }
            @endphp
            <tr>
                <td>
                    @if($imageSrc)
                        <img class="thumb" src="{{ $imageSrc }}" alt="">
                    @else
                        —
                    @endif
                </td>
                <td>{{ $item->product_name }}</td>
                <td>{{ $item->option_summary ?: '—' }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ $fmt($item->unit_price) }}</td>
                <td>{{ $fmt($item->total) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<table class="totals-table">
    <tr><td class="label">Subtotal</td><td class="value">{{ $fmt($order->subtotal) }}</td></tr>
    @if($order->discount > 0)
        <tr><td class="label">Discount</td><td class="value">-{{ $fmt($order->discount) }}</td></tr>
    @endif
    <tr><td class="label">Shipping</td><td class="value">{{ $fmt($order->shipping_cost) }}</td></tr>
    @if($order->tax_amount > 0)
        <tr><td class="label">Tax</td><td class="value">{{ $fmt($order->tax_amount) }}</td></tr>
    @endif
    <tr class="total-row"><td class="label">Total</td><td class="value">{{ $fmt($order->total) }}</td></tr>
</table>
</body>
</html>
