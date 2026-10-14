<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $order->order_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #111; margin: 24px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f5f5f5; }
        .totals { margin-top: 16px; max-width: 320px; margin-left: auto; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()">Print</button>
    <h1>{{ setting('site_name', 'Eternity Gadget') }}</h1>
    <h2>Invoice {{ $order->order_number }}</h2>
    <p>Date: {{ $order->created_at->format('d M Y') }}</p>
    <p>
        <strong>{{ $order->customer_name }}</strong><br>
        {{ $order->customer_email }}<br>
        {{ $order->customer_phone }}<br>
        {!! nl2br(e($order->shipping_address)) !!}
    </p>

    <table>
        <thead>
            <tr><th style="width:64px;">Image</th><th>Product</th><th>Options</th><th>Qty</th><th>Unit</th><th>Total</th></tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                @php
                    $imageUrl = media_url($item->product?->primaryImage());
                @endphp
                <tr>
                    <td>
                        @if($imageUrl)
                            <img src="{{ $imageUrl }}" alt="" style="width:48px;height:48px;object-fit:cover;border-radius:4px;">
                        @else
                            —
                        @endif
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

    <div class="totals">
        <p>Subtotal: {{ money_bd($order->subtotal) }}</p>
        @if($order->discount > 0)<p>Discount: -{{ money_bd($order->discount) }}</p>@endif
        <p>Shipping: {{ money_bd($order->shipping_cost) }}</p>
        @if($order->tax_amount > 0)<p>Tax: {{ money_bd($order->tax_amount) }}</p>@endif
        <p><strong>Total: {{ money_bd($order->total) }}</strong></p>
    </div>
</body>
</html>
