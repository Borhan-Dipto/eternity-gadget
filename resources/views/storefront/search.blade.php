@extends('layouts.storefront')

@section('title', 'প্রোডাক্ট খুঁজুন')

@section('content')
<style>
    .sf-pagination-wrap { display: flex; justify-content: center; margin: 28px 0; }
    .sf-pagination {
        display: inline-flex; align-items: center; gap: 8px; padding: 6px;
        background: #fff; border: 1px solid #e8e8e8; border-radius: 999px;
        box-shadow: 0 8px 24px rgba(0,0,0,.06);
    }
    .sf-page-list { display: flex; align-items: center; gap: 4px; list-style: none; margin: 0; padding: 0; }
    .sf-page-num, .sf-page-btn {
        min-width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center;
        border-radius: 999px; text-decoration: none; color: #222; font-weight: 700; font-size: 14px;
    }
    .sf-page-num.is-active { background: #111; color: #fff; }
    .sf-page-btn.is-disabled { opacity: .35; pointer-events: none; }
    .sf-page-ellipsis { padding: 0 6px; color: #999; font-weight: 700; }
</style>
<div class="container mt-4">
    <h1>প্রোডাক্ট খুঁজুন</h1>
    <form class="ig-search mb-4">
        <input name="q" value="{{ $term }}" placeholder="প্রোডাক্টের নাম বা মডেল লিখুন...">
        <button type="submit">খুঁজুন</button>
    </form>
    @if($products->isEmpty() && filled($term))
        <p style="text-align:center;padding:30px 10px;color:#666;">"{{ $term }}" এর জন্য কোনো প্রোডাক্ট পাওয়া যায়নি। অন্য নাম বা মডেল লিখে চেষ্টা করুন।</p>
    @endif
    <div class="product-grid">
        @foreach($products as $product)
            @include('storefront.partials.product-card', ['product' => $product])
        @endforeach
    </div>
    @if($products->hasPages())
        <div class="sf-pagination-wrap">{{ $products->onEachSide(1)->links('vendor.pagination.storefront') }}</div>
    @endif
</div>
@endsection
