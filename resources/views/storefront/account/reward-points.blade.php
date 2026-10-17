@extends('storefront.account.layout', ['activeNav' => 'rewards'])

@section('title', 'Reward Points')
@section('account_breadcrumb', 'Reward Points')

@section('account_content')
@php $labels = \App\Models\RewardPointTransaction::typeLabelsBn(); @endphp
<style>
    .rp-hero { text-align: center; padding: 26px 16px; border-radius: 14px; background: linear-gradient(135deg, #fff4ec, #fffaf6); border: 1px solid #f6dcc7; margin-bottom: 18px; }
    .rp-hero .rp-label { color: #8a5a35; font-size: 14px; margin: 0 0 4px; }
    .rp-hero .rp-points { font-size: 44px; font-weight: 700; color: #e8642c; margin: 0; line-height: 1.1; }
    .rp-hero .rp-worth { color: #555; font-size: 14px; margin: 6px 0 0; }
    .rp-how { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px; }
    .rp-how div { border: 1px solid #eee; border-radius: 12px; padding: 14px; font-size: 14px; line-height: 1.6; color: #444; }
    .rp-how strong { display: block; color: #111; margin-bottom: 4px; }
    .rp-table { width: 100%; border-collapse: collapse; font-size: 14px; }
    .rp-table th, .rp-table td { padding: 10px 6px; border-bottom: 1px solid #f0f0f0; text-align: left; }
    .rp-table th { color: #777; font-weight: 600; font-size: 13px; }
    .rp-plus { color: #1a7f45; font-weight: 700; }
    .rp-minus { color: #c0392b; font-weight: 700; }
    @media (max-width: 600px) { .rp-how { grid-template-columns: 1fr; } }
</style>

<div class="account-box">
    <h1>রিওয়ার্ড পয়েন্ট</h1>

    <div class="rp-hero">
        <p class="rp-label">আপনার পয়েন্ট</p>
        <p class="rp-points">{{ number_format($points) }}</p>
        @if($points > 0 && $pointValue > 0)
            <p class="rp-worth">মূল্য প্রায় {{ money_bd($points * $pointValue) }}</p>
        @endif
    </div>

    <div class="rp-how">
        <div>
            <strong>🎁 কীভাবে পয়েন্ট পাবেন</strong>
            প্রতি {{ money_bd(100) }} কেনাকাটায় {{ rtrim(rtrim(number_format($earnPer100, 2), '0'), '.') }} পয়েন্ট। অর্ডার ডেলিভারি হলে পয়েন্ট যোগ হয়।
        </div>
        <div>
            <strong>🛒 কীভাবে ব্যবহার করবেন</strong>
            {{ $minRedeem }} পয়েন্ট হলে চেকআউটে ব্যবহার করতে পারবেন। ১ পয়েন্ট = {{ money_bd($pointValue) }}, প্রতি অর্ডারের সর্বোচ্চ {{ rtrim(rtrim(number_format($maxPercent, 2), '0'), '.') }}% পর্যন্ত।
        </div>
    </div>

    <h2 style="font-size:16px;margin:0 0 8px;">পয়েন্টের হিসাব</h2>
    @if($history->isEmpty())
        <p style="color:#777;font-size:14px;">এখনো কোনো পয়েন্ট নেই। প্রথম অর্ডার ডেলিভারি হলেই পয়েন্ট পাবেন।</p>
    @else
        <table class="rp-table">
            <thead><tr><th>তারিখ</th><th>বিবরণ</th><th style="text-align:right;">পয়েন্ট</th></tr></thead>
            <tbody>
                @foreach($history as $row)
                    <tr>
                        <td>{{ $row->created_at->timezone('Asia/Dhaka')->format('d M Y') }}</td>
                        <td>{{ $labels[$row->type] ?? $row->type }}@if($row->order) — {{ $row->order->order_number }}@elseif($row->note) — {{ $row->note }}@endif</td>
                        <td style="text-align:right;" class="{{ $row->points >= 0 ? 'rp-plus' : 'rp-minus' }}">{{ $row->points >= 0 ? '+' : '' }}{{ $row->points }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
