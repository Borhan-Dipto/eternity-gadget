@extends('storefront.account.layout', ['activeNav' => 'orders'])

@section('title', 'Order History')
@section('account_breadcrumb', 'Order History')

@section('account_content')
<div class="account-box">
    <h1>আমার অর্ডার</h1>

    @if($orders->isEmpty())
        <p class="account-empty-msg">আপনি এখনো কোনো অর্ডার করেননি।</p>
        <div class="account-actions" style="justify-content:flex-end;">
            <a href="{{ route('home') }}" class="account-btn">প্রোডাক্ট দেখুন</a>
        </div>
    @else
        @foreach($orders as $order)
            @php $order->confirmIfExpired(); @endphp
            <div style="padding:14px 0;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
                <div>
                    <a href="{{ route('orders.show', $order) }}"><strong>{{ $order->order_number }}</strong></a>
                    — {{ $order->statusLabelBn() }} — {{ money_bd($order->total) }}
                    <br><small>{{ $order->created_at?->timezone('Asia/Dhaka')->format('d M Y') }}</small>
                    @if($order->canCancel())
                        <br><small style="color:#c0392b;">{{ max(1, (int) ceil(now()->diffInSeconds($order->cancelWindowEndsAt()) / 60)) }} মিনিটের মধ্যে বাতিল করা যাবে</small>
                    @endif
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <a href="{{ route('orders.show', $order) }}" class="account-btn" style="padding:6px 14px;font-size:12px;">দেখুন</a>
                    @if($order->canCancel())
                        <form method="post" action="{{ route('orders.cancel', $order) }}" onsubmit="return confirm('আপনি কি অর্ডারটি বাতিল করতে চান?');">
                            @csrf
                            <button type="submit" class="account-btn" style="padding:6px 14px;font-size:12px;background:#c0392b;">বাতিল</button>
                        </form>
                    @endif
                    <a href="{{ $order->invoiceUrl() }}" class="account-btn" style="padding:6px 14px;font-size:12px;">ইনভয়েস</a>
                </div>
            </div>
        @endforeach
        <div style="margin-top:20px;">{{ $orders->links() }}</div>
    @endif
</div>
@endsection
