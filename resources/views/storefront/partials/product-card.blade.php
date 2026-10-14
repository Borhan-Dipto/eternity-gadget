<div class="product-card">
    <div class="product-card-image-wrap">
        <a href="{{ route('product.show', $product->slug) }}">
            <img src="{{ media_url($product->primaryImage()) }}" alt="{{ $product->name }}">
        </a>
        @if($product->sale_price && $product->price > 0)
            <span class="badge-sale">{{ round((($product->price - $product->sale_price) / $product->price) * 100) }}% OFF</span>
        @endif
    </div>
    <h4 class="product-card-title"><a href="{{ route('product.show', $product->slug) }}">{{ $product->name }}</a></h4>
    <p class="product-card-price">
        <strong class="price-current">{{ money_bd($product->displayPrice()) }}</strong>
        @if($product->sale_price)
            <span class="price-old">{{ money_bd($product->price) }}</span>
        @endif
    </p>
    <div class="product-card-order">
        <a href="{{ route('product.show', $product->slug) }}" class="btn-order-now">{{ __('messages.order_now') }}</a>
    </div>
</div>
