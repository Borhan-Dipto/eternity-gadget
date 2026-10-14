@extends('storefront.account.layout', ['activeNav' => 'downloads'])

@section('title', 'Downloads')
@section('account_breadcrumb', 'Account Downloads')

@section('account_content')
<div class="account-box">
    <h1>Account Downloads</h1>

    @if($orders->isEmpty())
        <p class="account-empty-msg">You have not made any previous downloadable orders!</p>
        <div class="account-actions" style="justify-content:flex-end;">
            <a href="{{ route('home') }}" class="account-btn">View More Products</a>
        </div>
    @else
        @foreach($orders as $order)
            <div style="padding:14px 0;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center;">
                <span>{{ $order->order_number }} — {{ $order->created_at?->format('M d, Y') }}</span>
                <a href="{{ $order->invoiceUrl() }}" class="account-btn" style="padding:6px 14px;font-size:12px;">Download Invoice</a>
            </div>
        @endforeach
    @endif
</div>
@endsection
