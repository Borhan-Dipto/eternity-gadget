@extends('storefront.account.layout', ['activeNav' => 'wishlist'])

@section('title', 'Wish List')
@section('account_breadcrumb', 'My Wish List')

@section('account_content')
<div class="account-box">
    <h1>My Wish List</h1>

    @if($products->isEmpty())
        <p class="account-empty-msg">Your wish list is empty.</p>
        <div class="account-actions" style="justify-content:flex-end;">
            <a href="{{ route('home') }}" class="account-btn">View More Products</a>
        </div>
    @else
        <div class="product-grid" style="margin-top:20px;">
            @foreach($products as $product)
                @include('storefront.partials.product-card', ['product' => $product])
            @endforeach
        </div>
    @endif
</div>
@endsection
