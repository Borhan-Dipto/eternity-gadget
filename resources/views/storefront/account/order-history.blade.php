@extends('storefront.account.layout', ['activeNav' => 'orders'])

@section('title', 'Order History')
@section('account_breadcrumb', 'Order History')

@section('account_content')
<div class="account-box">
    <h1>Order History</h1>

    @if($orders->isEmpty())
        <p class="account-empty-msg">You have not made any previous orders!</p>
        <div class="account-actions" style="justify-content:flex-end;">
            <a href="{{ route('home') }}" class="account-btn">View More Products</a>
        </div>
    @else
        @foreach($orders as $order)
            @php $order->confirmIfExpired(); @endphp
            <div style="padding:14px 0;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
                <div>
                    <a href="{{ route('orders.show', $order) }}"><strong>{{ $order->order_number }}</strong></a>
                    — {{ ucfirst($order->status) }} — {{ money_bd($order->total) }}
                    <br><small>{{ $order->created_at?->format('M d, Y') }}</small>
                    @if($order->canCancel())
                        <br><small style="color:#c0392b;">Cancel available for {{ $order->cancelWindowEndsAt()->diffForHumans() }}</small>
                    @endif
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <a href="{{ route('orders.show', $order) }}" class="account-btn" style="padding:6px 14px;font-size:12px;">View</a>
                    @if($order->canCancel())
                        <form method="post" action="{{ route('orders.cancel', $order) }}" onsubmit="return confirm('Cancel this order?');">
                            @csrf
                            <button type="submit" class="account-btn" style="padding:6px 14px;font-size:12px;background:#c0392b;">Cancel</button>
                        </form>
                    @endif
                    <a href="{{ $order->invoiceUrl() }}" class="account-btn" style="padding:6px 14px;font-size:12px;">Invoice</a>
                </div>
            </div>
        @endforeach
        <div style="margin-top:20px;">{{ $orders->links() }}</div>
    @endif
</div>
@endsection
