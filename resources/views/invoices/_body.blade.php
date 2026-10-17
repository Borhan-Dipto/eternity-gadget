{{--
    One invoice design for the customer page, printing and the admin PDF (mPDF).
    Table-based layout on purpose: mPDF does not support flex/grid.
    $forPdf = true → images are read from disk (fast, works offline in the PDF engine).
--}}
@php
    $forPdf = $forPdf ?? false;
    $img = function (?string $path, int $width = 300) use ($forPdf) {
        if (! $path) return null;
        if ($forPdf && ! preg_match('#^https?://#', $path)) {
            $file = storage_path('app/public/' . ltrim($path, '/'));
            return is_file($file) ? $file : null;
        }
        return $forPdf ? $path : media_thumb($path, $width);
    };
    $logo = $img(setting('logo'), 300);
    $shopName = setting('site_name', 'Eternity Gadget');
    $shippingNames = ['inside_dhaka' => 'ঢাকা শহরের ভিতরে', 'standard' => 'ঢাকা শহরের ভিতরে', 'outside_dhaka' => 'ঢাকা শহরের বাইরে', 'express' => 'ঢাকা শহরের বাইরে'];
    $isPaid = $order->payment_status === \App\Models\Order::PAYMENT_STATUS_PAID;
    $phone = preg_replace('/^\+?880/', '0', (string) $order->customer_phone);
    $statusColors = ['pending' => '#b45309', 'processing' => '#1d4ed8', 'shipped' => '#6d28d9', 'delivered' => '#15803d', 'cancelled' => '#b91c1c'];
    $statusColor = $statusColors[$order->status] ?? '#374151';
@endphp
<div class="inv">
    <table class="inv-head" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td class="inv-brand" valign="middle">
                @if($logo)<img src="{{ $logo }}" alt="{{ $shopName }}" class="inv-logo">@else<div class="inv-shop">{{ $shopName }}</div>@endif
            </td>
            <td class="inv-title" valign="middle" align="right">
                <div class="inv-word">INVOICE</div>
                <div class="inv-word-bn">ইনভয়েস</div>
            </td>
        </tr>
    </table>

    <table class="inv-meta" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td class="inv-box" width="50%" valign="top">
                <div class="inv-label">From / বিক্রেতা</div>
                <div class="inv-strong">{{ $shopName }}</div>
                @if(setting('address'))<div>{{ setting('address') }}</div>@endif
                @if(setting('phone'))<div>Phone: {{ setting('phone') }}</div>@endif
                @if(setting('whatsapp_number'))<div>WhatsApp: {{ setting('whatsapp_number') }}</div>@endif
                @if(setting('email'))<div>{{ setting('email') }}</div>@endif
                <div>{{ preg_replace('#^https?://#', '', rtrim(config('app.url'), '/')) }}</div>
            </td>
            <td class="inv-box" width="50%" valign="top">
                <div class="inv-label">Bill to / গ্রাহক</div>
                <div class="inv-strong">{{ $order->customer_name }}</div>
                <div>Phone: {{ $phone }}</div>
                @if($order->customer_email)<div>{{ $order->customer_email }}</div>@endif
                <div>{!! nl2br(e($order->shipping_address)) !!}</div>
                @if($order->shipping_method)<div>এলাকা: {{ $shippingNames[$order->shipping_method] ?? $order->shipping_method }}</div>@endif
            </td>
        </tr>
    </table>

    <table class="inv-facts" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td><span class="inv-label">Invoice no.</span><br><span class="inv-strong">#{{ $order->order_number }}</span></td>
            <td><span class="inv-label">Order date / তারিখ</span><br><span class="inv-strong">{{ $order->created_at->timezone('Asia/Dhaka')->format('d M Y, h:i A') }}</span></td>
            <td><span class="inv-label">Status / অবস্থা</span><br><span class="inv-strong" style="color: {{ $statusColor }};">{{ $order->statusLabelBn() }}</span></td>
            <td><span class="inv-label">Payment / পেমেন্ট</span><br><span class="inv-strong">{{ $order->payment_method === 'cod' ? 'Cash on Delivery' : strtoupper((string) $order->payment_method) }}</span></td>
        </tr>
    </table>

    <table class="inv-items" width="100%" cellspacing="0" cellpadding="0">
        <thead>
            <tr>
                <th width="5%" align="left">#</th>
                <th width="11%" align="left">Photo</th>
                <th align="left">Product / প্রোডাক্ট</th>
                <th width="8%" align="center">Qty</th>
                <th width="15%" align="right">Price</th>
                <th width="15%" align="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                @php $photo = $img($item->displayImage(), 300); @endphp
                <tr>
                    <td valign="middle" class="inv-muted">{{ $loop->iteration }}</td>
                    <td valign="middle">@if($photo)<img src="{{ $photo }}" alt="" class="inv-thumb" width="48" height="48">@endif</td>
                    <td valign="middle">
                        <div class="inv-strong">{{ $item->product_name }}</div>
                        @if($item->option_summary)<div class="inv-muted">{{ $item->option_summary }}</div>@endif
                    </td>
                    <td valign="middle" align="center">{{ $item->quantity }}</td>
                    <td valign="middle" align="right">{{ money_bd($item->unit_price) }}</td>
                    <td valign="middle" align="right" class="inv-strong">{{ money_bd($item->total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table width="100%" cellspacing="0" cellpadding="0" class="inv-bottom">
        <tr>
            <td width="52%" valign="top" class="inv-notes">
                <div class="inv-label">Notes / তথ্য</div>
                @if($order->notes)<div>গ্রাহকের মন্তব্য: {{ $order->notes }}</div>@endif
                @if(setting('return_policy_text'))<div>{{ setting('return_policy_text') }}</div>@endif
                <div>প্রয়োজনে যোগাযোগ: {{ setting('phone', '01867-683114') }}</div>
                <div class="inv-thanks">Thank you for shopping with {{ $shopName }}! ধন্যবাদ।</div>
            </td>
            <td width="48%" valign="top">
                <table width="100%" cellspacing="0" cellpadding="0" class="inv-totals">
                    <tr><td>Subtotal / সাবটোটাল</td><td align="right">{{ money_bd($order->subtotal) }}</td></tr>
                    @if((float) $order->discount > 0)
                        <tr><td>Discount / ছাড়@if($order->coupon_code) ({{ $order->coupon_code }})@endif</td><td align="right">−{{ money_bd($order->discount) }}</td></tr>
                    @endif
                    @if((float) ($order->reward_discount ?? 0) > 0)
                        <tr><td>Reward points ({{ $order->reward_points_used }})</td><td align="right">−{{ money_bd($order->reward_discount) }}</td></tr>
                    @endif
                    <tr><td>Delivery / ডেলিভারি</td><td align="right">{{ (float) $order->shipping_cost > 0 ? money_bd($order->shipping_cost) : 'Free' }}</td></tr>
                    @if((float) $order->tax_amount > 0)
                        <tr><td>Tax / ট্যাক্স</td><td align="right">{{ money_bd($order->tax_amount) }}</td></tr>
                    @endif
                    <tr class="inv-grand"><td>Total / মোট</td><td align="right">{{ money_bd($order->total) }}</td></tr>
                    <tr class="inv-due">
                        <td>{{ $isPaid ? 'Paid / পরিশোধিত' : 'Amount due / পরিশোধ করতে হবে' }}</td>
                        <td align="right">{{ $isPaid ? money_bd($order->total) : money_bd($order->total) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="inv-foot">This is a computer-generated invoice and needs no signature. · {{ $shopName }} · {{ now('Asia/Dhaka')->format('d M Y') }}</div>
</div>
