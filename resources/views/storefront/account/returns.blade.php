@extends('storefront.account.layout', ['activeNav' => 'returns'])

@section('title', 'Returns')
@section('account_breadcrumb', 'Returns')

@section('account_content')
<div class="account-box">
    <h1>Returns</h1>

    @if($returns->isEmpty())
        <p class="account-empty-msg">কোনো রিটার্ন রিকোয়েস্ট নেই। রিটার্নের জন্য আমাদের কল বা WhatsApp করুন।</p>
        <div class="account-actions" style="justify-content:flex-end;">
            <a href="{{ route('home') }}" class="account-btn">প্রোডাক্ট দেখুন</a>
        </div>
    @else
        @foreach($returns as $return)
            <div style="padding:14px 0;border-bottom:1px solid #eee;">
                <strong>Order #{{ $return->order->order_number ?? $return->order_id }}</strong>
                — {{ ucfirst($return->status) }}
                @if($return->refund_amount)<br>ফেরত: {{ money_bd($return->refund_amount) }}@endif
                <br><small>{{ $return->reason }}</small>
            </div>
        @endforeach
    @endif
</div>
@endsection
