@extends('storefront.account.layout', ['activeNav' => 'transactions'])

@section('title', 'Transactions')
@section('account_breadcrumb', 'Transactions')

@section('account_content')
<div class="account-box">
    <h1>Transactions</h1>

    @if($orders->isEmpty())
        <p class="account-empty-msg">No transactions yet.</p>
        <div class="account-actions" style="justify-content:flex-end;">
            <a href="{{ route('home') }}" class="account-btn">View More Products</a>
        </div>
    @else
        <table style="width:100%;border-collapse:collapse;font-size:14px;">
            <thead>
                <tr style="border-bottom:2px solid #ddd;text-align:left;">
                    <th style="padding:10px 8px;">Order</th>
                    <th style="padding:10px 8px;">Date</th>
                    <th style="padding:10px 8px;">Payment</th>
                    <th style="padding:10px 8px;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $order)
                    <tr style="border-bottom:1px solid #eee;">
                        <td style="padding:10px 8px;"><a href="{{ route('orders.show', $order) }}">{{ $order->order_number }}</a></td>
                        <td style="padding:10px 8px;">{{ $order->created_at?->format('M d, Y') }}</td>
                        <td style="padding:10px 8px;">{{ ucfirst($order->payment_status) }} ({{ $order->payment_method }})</td>
                        <td style="padding:10px 8px;">{{ money_bd($order->total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div style="margin-top:20px;">{{ $orders->links() }}</div>
    @endif
</div>
@endsection
