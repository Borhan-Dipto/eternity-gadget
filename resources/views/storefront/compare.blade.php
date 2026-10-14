@extends('layouts.storefront')

@section('title', 'Compare Products')

@section('content')
<div class="container mt-4">
    <h1>Compare Products</h1>
    @if($products->isEmpty())
        <p>No products to compare.</p>
    @else
        <table class="table">
            <thead>
                <tr>
                    <th></th>
                    @foreach($products as $product)
                        <th><a href="{{ route('product.show', $product->slug) }}">{{ $product->name }}</a></th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Image</td>
                    @foreach($products as $product)
                        <td><img src="{{ media_url($product->primaryImage()) }}" alt="{{ $product->name }}" style="max-width:120px"></td>
                    @endforeach
                </tr>
                <tr>
                    <td>Price</td>
                    @foreach($products as $product)
                        <td>{{ money_bd($product->displayPrice()) }}</td>
                    @endforeach
                </tr>
                <tr>
                    <td>Stock</td>
                    @foreach($products as $product)
                        <td>{{ $product->availableStock() }}</td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    @endif
</div>
@endsection
