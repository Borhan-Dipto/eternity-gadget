<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex">
    <title>ইনভয়েস {{ $order->order_number }}</title>
    <style>
        @font-face { font-family: 'Hind Siliguri'; font-weight: 400; font-display: swap; src: url('/fonts/site/hind-siliguri-bengali-400-normal.woff2') format('woff2'); }
        @font-face { font-family: 'Hind Siliguri'; font-weight: 700; font-display: swap; src: url('/fonts/site/hind-siliguri-bengali-700-normal.woff2') format('woff2'); }
        body { font-family: 'Hind Siliguri', Arial, sans-serif; color: #111; margin: 24px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f5f5f5; }
        .totals { margin-top: 16px; max-width: 320px; margin-left: auto; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()">প্রিন্ট / PDF সেভ</button>
    <h1>{{ setting('site_name', 'Eternity Gadget') }}</h1>
    <h2>ইনভয়েস #{{ $order->order_number }}</h2>
    <p>তারিখ: {{ $order->created_at->timezone('Asia/Dhaka')->format('d M Y') }} &nbsp;|&nbsp; অবস্থা: {{ $order->statusLabelBn() }} &nbsp;|&nbsp; পেমেন্ট: {{ $order->paymentLabelBn() }}</p>
    <p>
        <strong>{{ $order->customer_name }}</strong><br>
        {{ $order->customer_email }}<br>
        {{ $order->customer_phone }}<br>
        {!! nl2br(e($order->shipping_address)) !!}
    </p>

    <table>
        <thead>
            <tr><th style="width:64px;">ছবি</th><th>প্রোডাক্ট</th><th>অপশন</th><th>পরিমাণ</th><th>দাম</th><th>মোট</th></tr>
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
        <p>সাবটোটাল: {{ money_bd($order->subtotal) }}</p>
        @if($order->discount > 0)<p>ছাড়: -{{ money_bd($order->discount) }}</p>@endif
        <p>ডেলিভারি চার্জ: {{ money_bd($order->shipping_cost) }}</p>
        @if($order->tax_amount > 0)<p>ট্যাক্স: {{ money_bd($order->tax_amount) }}</p>@endif
        <p><strong>মোট: {{ money_bd($order->total) }}</strong></p>
    </div>
</body>
</html>
