<!DOCTYPE html>
<html lang="bn">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:Arial,Helvetica,sans-serif;color:#111;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f4f5;padding:24px 0;">
<tr><td align="center">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border-radius:8px;overflow:hidden;">
    <tr><td style="background:#111;color:#fff;padding:18px 24px;font-size:18px;font-weight:bold;">{{ setting('site_name', 'Eternity Gadget') }}</td></tr>
    <tr><td style="padding:24px;">
        <p style="margin:0 0 8px;font-size:16px;">প্রিয় {{ $order->customer_name }},</p>
        <p style="margin:0 0 16px;line-height:1.6;">আপনার অর্ডার আমরা পেয়েছি। শিগগিরই ফোন করে কনফার্ম করা হবে।</p>

        <p style="margin:0 0 4px;"><strong>অর্ডার নম্বর:</strong> {{ $order->order_number }}</p>
        <p style="margin:0 0 16px;"><strong>পেমেন্ট:</strong> {{ $order->payment_method === 'cod' ? 'ক্যাশ অন ডেলিভারি' : $order->payment_method }}</p>

        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;font-size:14px;">
            @foreach($order->items as $item)
                <tr>
                    <td style="padding:8px 8px 8px 0;border-bottom:1px solid #eee;width:56px;"><img src="{{ media_thumb($item->displayImage(), 300) }}" alt="" width="48" height="48" style="display:block;width:48px;height:48px;object-fit:cover;border-radius:8px;"></td>
                    <td style="padding:8px 0;border-bottom:1px solid #eee;">{{ $item->product_name }}@if($item->option_summary) <span style="color:#666;">({{ $item->option_summary }})</span>@endif × {{ $item->quantity }}</td>
                    <td style="padding:8px 0;border-bottom:1px solid #eee;text-align:right;white-space:nowrap;">{{ money_bd($item->total) }}</td>
                </tr>
            @endforeach
            <tr><td colspan="2" style="padding:6px 0;color:#555;">সাবটোটাল</td><td style="padding:6px 0;text-align:right;">{{ money_bd($order->subtotal) }}</td></tr>
            @if((float) $order->discount > 0)
                <tr><td colspan="2" style="padding:6px 0;color:#555;">ছাড়</td><td style="padding:6px 0;text-align:right;">-{{ money_bd($order->discount) }}</td></tr>
            @endif
            @if((float) ($order->reward_discount ?? 0) > 0)
                <tr><td colspan="2" style="padding:6px 0;color:#555;">রিওয়ার্ড পয়েন্ট ছাড়</td><td style="padding:6px 0;text-align:right;">-{{ money_bd($order->reward_discount) }}</td></tr>
            @endif
            <tr><td colspan="2" style="padding:6px 0;color:#555;">ডেলিভারি চার্জ</td><td style="padding:6px 0;text-align:right;">{{ (float) $order->shipping_cost > 0 ? money_bd($order->shipping_cost) : 'ফ্রি' }}</td></tr>
            @if((float) $order->tax_amount > 0)
                <tr><td colspan="2" style="padding:6px 0;color:#555;">ট্যাক্স</td><td style="padding:6px 0;text-align:right;">{{ money_bd($order->tax_amount) }}</td></tr>
            @endif
            <tr><td colspan="2" style="padding:10px 0;font-weight:bold;border-top:2px solid #111;">মোট</td><td style="padding:10px 0;text-align:right;font-weight:bold;border-top:2px solid #111;">{{ money_bd($order->total) }}</td></tr>
        </table>

        <p style="margin:16px 0 0;line-height:1.6;"><strong>ডেলিভারি ঠিকানা:</strong><br>{!! nl2br(e($order->shipping_address)) !!}</p>
        @if(setting('phone'))
            <p style="margin:16px 0 0;color:#555;">যেকোনো প্রয়োজনে কল করুন: {{ setting('phone') }}</p>
        @endif
    </td></tr>
</table>
</td></tr>
</table>
</body>
</html>
