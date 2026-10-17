@extends('layouts.storefront')

@section('title', __('messages.cart'))

@section('content')
<div class="container mt-4">
    <h1>{{ __('messages.cart') }}</h1>
    @if($cartItems->isEmpty() && $savedItems->isEmpty())
        <p>{{ __('messages.empty_cart') }}</p>
    @else
        @if($cartItems->isNotEmpty())
            <form method="post" action="{{ route('cart.update') }}">
                @csrf
                <table class="table">
                    <thead><tr><th>প্রোডাক্ট</th><th>অপশন</th><th>পরিমাণ</th><th>দাম</th><th>মোট</th><th></th></tr></thead>
                    <tbody>
                    @foreach($cartItems as $item)
                        <tr>
                            <td>{{ $item['product']->name }}</td>
                            <td>{{ $item['option_summary'] ?: '—' }}</td>
                            <td><input class="form-control" style="max-width:90px" type="number" min="0" name="quantities[{{ $item['cart_key'] }}]" value="{{ $item['quantity'] }}"></td>
                            <td>{{ money_bd($item['unit_price']) }}</td>
                            <td>{{ money_bd($item['line_total']) }}</td>
                            <td>
                                <button form="remove-{{ md5($item['cart_key']) }}" class="btn btn-link" type="submit">সরান</button>
                                <button form="save-{{ md5($item['cart_key']) }}" class="btn btn-link" type="submit">{{ __('messages.save_for_later') }}</button>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                <button class="ig-btn" type="submit">{{ __('messages.update_cart') }}</button>
                <a class="ig-btn" href="{{ route('checkout') }}">{{ __('messages.order_now') }}</a>
            </form>
            @foreach($cartItems as $item)
                <form id="remove-{{ md5($item['cart_key']) }}" method="post" action="{{ route('cart.remove') }}">
                    @csrf
                    <input type="hidden" name="cart_key" value="{{ $item['cart_key'] }}">
                </form>
                <form id="save-{{ md5($item['cart_key']) }}" method="post" action="{{ route('cart.save-for-later') }}">
                    @csrf
                    <input type="hidden" name="cart_key" value="{{ $item['cart_key'] }}">
                </form>
            @endforeach
        @endif

        @if($savedItems->isNotEmpty())
            <h2 class="mt-5">{{ __('messages.save_for_later') }}</h2>
            <table class="table">
                <thead><tr><th>প্রোডাক্ট</th><th>অপশন</th><th>পরিমাণ</th><th>দাম</th><th></th></tr></thead>
                <tbody>
                @foreach($savedItems as $item)
                    <tr>
                        <td>{{ $item['product']->name }}</td>
                        <td>{{ $item['option_summary'] ?: '—' }}</td>
                        <td>{{ $item['quantity'] }}</td>
                        <td>{{ money_bd($item['unit_price']) }}</td>
                        <td>
                            <form method="post" action="{{ route('cart.move-to-cart') }}">
                                @csrf
                                <input type="hidden" name="cart_key" value="{{ $item['cart_key'] }}">
                                <button class="btn btn-link" type="submit">{{ __('messages.move_to_cart') }}</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    @endif
</div>
@endsection
