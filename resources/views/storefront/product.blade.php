@extends('layouts.storefront')

@section('title', $product->meta_title ?: $product->name)
@section('meta_description', $product->meta_description ?: ($product->short_description ?: $product->name . ' — ' . money_bd($product->effectivePrice()) . ' | ' . setting('site_name', 'Eternity Gadget')))
@section('og_type', 'product')
@section('og_image', media_url($product->primaryImage()))
@section('og_extra')
    <meta property="product:price:amount" content="{{ number_format($product->effectivePrice(), 2, '.', '') }}">
    <meta property="product:price:currency" content="BDT">
    <meta property="product:retailer_item_id" content="{{ $product->id }}">
@endsection

@section('content')
@php
    use App\Support\ProductOptionGroups;

    $stockLabels = [
        'in_stock' => __('messages.in_stock'),
        'low_stock' => __('messages.low_stock'),
        'out_of_stock' => __('messages.out_of_stock'),
        'preorder' => __('messages.preorder'),
        'backorder' => __('messages.backorder'),
    ];
    $galleryImages = $product->images->isNotEmpty() ? $product->images->values() : collect([(object)['image' => $product->primaryImage(), 'alt_text' => $product->name, 'option_ids' => []]]);
    // Photos added on a colour / model row join the gallery, so customers can browse every version
    $galleryPaths = $galleryImages->pluck('image')->all();
    foreach ($product->options->sortBy('sort_order') as $opt) {
        if ($opt->image && ! in_array($opt->image, $galleryPaths, true)) {
            $galleryImages->push((object) ['image' => $opt->image, 'alt_text' => $product->name . ' — ' . ($opt->deviceModel?->name ?? $opt->value), 'option_ids' => [$opt->id]]);
            $galleryPaths[] = $opt->image;
        } elseif ($opt->image) {
            // same file already in the gallery: tag that photo with this option too
            $galleryImages = $galleryImages->map(function ($img) use ($opt) {
                if ($img->image === $opt->image) {
                    $ids = (array) ($img->option_ids ?? []);
                    $ids[] = $opt->id;
                    $img = (object) ['image' => $img->image, 'alt_text' => $img->alt_text, 'option_ids' => array_values(array_unique($ids))];
                }
                return $img;
            });
        }
    }
    $galleryUrls = $galleryImages->map(fn ($img) => media_thumb($img->image, 1000))->values();
    // index => option ids that photo shows (used to jump to the right photo when a choice is made)
    $galleryOptionTags = $galleryImages->map(fn ($img) => array_map('intval', (array) ($img->option_ids ?? [])))->values();
    $optionGroups = ProductOptionGroups::storefrontGroups($product->options);

    $defaultOptionIds = $optionGroups
        ->mapWithKeys(fn (array $group) => [
            $group['key'] => (int) (old('option_ids.' . $group['key']) ?: $group['options']->first()?->id),
        ])
        ->filter()
        ->values()
        ->all();
    $selectedOptions = $product->options->whereIn('id', $defaultOptionIds)->values();
    $selectedPricingOption = $product->pricingOptionForSelection($selectedOptions);
    $stockStatus = $product->stockStatus($selectedPricingOption);
    $hasSale = $product->sale_price && $product->price > 0;
    $comparePrice = (float) $product->price + (float) ($selectedPricingOption?->price_adjustment ?? 0);
    $displayPrice = $product->displayPrice($selectedPricingOption);
    $savings = $hasSale && $comparePrice > $displayPrice ? $comparePrice - $displayPrice : 0;
    $savingsPercent = $hasSale && $comparePrice > 0 ? round(($savings / $comparePrice) * 100) : 0;
    $inStock = in_array($stockStatus, ['in_stock', 'low_stock', 'backorder'], true);
    $availableStock = $product->availableStock($selectedPricingOption);
    $reviewCount = $reviews->count();
    $reviewsTabActive = $errors->hasAny(['name', 'body', 'rating', 'captcha', 'image'])
        || old('review_form');
    $optionPayload = $product->options->mapWithKeys(function ($option) use ($product, $stockLabels) {
        $status = $product->stockStatus($option);
        $comparePrice = (float) $product->price + (float) $option->price_adjustment;
        $displayPrice = $product->displayPrice($option);
        $hasOptionSale = $product->sale_price && $comparePrice > $displayPrice;
        $savings = $hasOptionSale ? $comparePrice - $displayPrice : 0;
        $savingsPercent = $hasOptionSale && $comparePrice > 0 ? round(($savings / $comparePrice) * 100) : 0;

        return [
            $option->id => [
                'id' => $option->id,
                'group' => $option->name,
                'value' => $option->deviceModel?->name ?? $option->value,
                'price_adjustment' => (float) $option->price_adjustment,
                'price' => $displayPrice,
                'price_formatted' => money_bd($displayPrice),
                'compare_price' => $comparePrice,
                'compare_price_formatted' => money_bd($comparePrice),
                'has_sale' => $hasOptionSale,
                'savings' => $savings,
                'savings_formatted' => money_bd($savings),
                'savings_percent' => $savingsPercent,
                'stock' => $product->availableStock($option),
                'status' => $status,
                'status_label' => $stockLabels[$status] ?? $status,
                'is_compatibility' => $option->isCompatibilityOption(),
                'is_color' => $option->isColorOption(),
                'color_hex' => $option->color_hex,
            ],
        ];
    });
@endphp

<script type="application/ld+json">
{!! json_encode(array_filter([
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $product->name,
    'description' => strip_tags($product->short_description ?: $product->description),
    'sku' => $product->sku,
    'image' => media_url($product->primaryImage()),
    'offers' => [
        '@type' => 'Offer',
        'priceCurrency' => 'BDT',
        'price' => $displayPrice,
        'availability' => $inStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        'url' => route('product.show', $product->slug),
    ],
    'aggregateRating' => $reviews->isNotEmpty() ? [
        '@type' => 'AggregateRating',
        'ratingValue' => round((float) $averageRating, 1),
        'reviewCount' => $reviews->count(),
    ] : null,
]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
</script>

<style>
    .pd-page { padding: 24px 0 48px; }

    .pd-breadcrumb {
        text-align: center;
        margin-bottom: 22px;
        font-size: 14px;
        color: #111;
    }

    .pd-breadcrumb a { color: #111; text-decoration: none; }
    .pd-breadcrumb a:hover { color: var(--link-hover-color, #83B735); }
    .pd-breadcrumb .current { color: #f5924c; }
    .pd-breadcrumb svg { width: 14px; height: 14px; vertical-align: -2px; fill: currentColor; }

    .pd-top { display: flex; gap: 36px; align-items: flex-start; margin-bottom: 36px; }

    .pd-gallery { flex: 0 0 48%; max-width: 48%; }

    .pd-gallery-stage {
        position: relative;
        touch-action: pan-y;
        user-select: none;
        -webkit-user-select: none;
    }

    .pd-gallery-main {
        width: 100%;
        display: block;
        border: 1px solid #eee;
        background: #fff;
        cursor: zoom-in;
        aspect-ratio: 1 / 1;
        object-fit: contain;
    }

    .pd-gallery-dots {
        display: none;
        position: absolute;
        left: 0;
        right: 0;
        bottom: 12px;
        justify-content: center;
        gap: 6px;
        pointer-events: none;
    }

    .pd-gallery-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: rgba(255,255,255,.7);
        box-shadow: 0 0 0 1px rgba(0,0,0,.12);
    }

    .pd-gallery-dot.active { background: #f5924c; }

    .pd-gallery-thumbs {
        display: flex;
        gap: 10px;
        margin-top: 12px;
        flex-wrap: wrap;
    }

    .pd-gallery-thumb {
        width: 72px;
        height: 72px;
        object-fit: contain;
        object-position: center;
        background: #f8f8f8;
        border: 2px solid #ddd;
        cursor: pointer;
    }

    .pd-gallery-thumb.active,
    .pd-gallery-thumb:hover { border-color: #f5924c; }

    .pd-info { flex: 1 1 auto; min-width: 0; }

    .pd-title {
        font-size: 28px;
        font-weight: 400;
        line-height: 1.3;
        margin: 0 0 18px;
    }

    .pd-price-row {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 16px;
    }

    .pd-price-current {
        font-size: 32px;
        font-weight: 700;
        color: #c0392b;
    }

    .pd-price-old {
        font-size: 18px;
        color: #888;
        text-decoration: line-through;
    }

    .pd-save-badge {
        background: #f5924c;
        color: #fff;
        padding: 6px 12px;
        border-radius: 4px;
        font-size: 13px;
        font-weight: 700;
    }

    .pd-btn-whatsapp { display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 10px; padding: 12px 16px; border-radius: 6px; background: #25D366; color: #fff !important; font-weight: 600; font-size: 14px; text-decoration: none; }
    .pd-btn-whatsapp:hover { background: #1ebe5a; }
    .pd-trust { list-style: none; margin: 16px 0 0; padding: 12px 14px; border: 1px solid #eee; border-radius: 10px; background: #fafafa; }
    .pd-trust li { display: flex; gap: 10px; font-size: 13px; line-height: 1.55; color: #444; padding: 6px 0; }
    .pd-trust li + li { border-top: 1px dashed #e6e6e6; }
    .pd-trust strong { color: #111; }
    .pd-trust-icon { flex-shrink: 0; width: 20px; text-align: center; }
    .pd-sticky { display: none; }
    @media (max-width: 767px) {
        .pd-sticky { display: flex; align-items: center; gap: 10px; position: fixed; left: 0; right: 0; bottom: calc(58px + env(safe-area-inset-bottom, 0px)); z-index: 79; padding: 10px 12px; background: rgba(255,255,255,.96); -webkit-backdrop-filter: blur(16px); backdrop-filter: blur(16px); border-top: 1px solid #eee; box-shadow: 0 -6px 20px rgba(0,0,0,.08); transform: translateY(140%); transition: transform .25s ease; }
        .pd-sticky.is-visible { transform: translateY(0); }
        .pd-sticky-price { flex: 1; min-width: 0; display: flex; flex-direction: column; line-height: 1.25; }
        .pd-sticky-name { font-size: 12px; color: #666; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .pd-sticky-price strong { font-size: 17px; color: #c0392b; }
        .pd-sticky-wa { width: 44px; height: 44px; border-radius: 10px; background: #25D366; color: #fff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .pd-sticky-btn { flex-shrink: 0; height: 44px; padding: 0 22px; border: 0; border-radius: 10px; background: var(--brand); color: #fff; font-weight: 700; font-size: 15px; font-family: inherit; cursor: pointer; }
        body.page-product .chat-widget { display: none; } /* the sticky bar has WhatsApp; avoids a button covering the page */
        body.page-product { padding-bottom: calc(130px + env(safe-area-inset-bottom, 0px)); }
    }
    .pd-reward-note { display: inline-block; margin: 0 0 12px; padding: 6px 12px; border-radius: 999px; background: #fff4ec; color: #8a4b1f; font-size: 13px; }
    .pd-meta {
        font-size: 14px;
        color: #333;
        margin-bottom: 8px;
    }

    .pd-meta strong { font-weight: 700; }

    .pd-in-stock { color: #1a5c32; font-weight: 700; }

    .pd-out-stock { color: #c0392b; font-weight: 700; }

    .pd-option-group { margin: 22px 0; }

    .pd-option-label {
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 10px;
        text-transform: uppercase;
    }

    .pd-option-label .req { color: #c0392b; }

    .pd-option-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: stretch;
    }

    .pd-option-pill {
        position: relative;
        flex: 0 1 auto;
        max-width: calc((100% - 16px) / 3);
        min-width: 0;
    }

    .pd-option-pill input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .pd-option-pill span {
        display: block;
        width: 100%;
        box-sizing: border-box;
        padding: 7px 12px;
        border: 1px solid #ccc;
        border-radius: 999px;
        background: #fff;
        color: #111;
        font-size: 12px;
        line-height: 1.3;
        text-align: center;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        cursor: pointer;
        transition: all .15s;
    }

    .pd-option-pill input:checked + span,
    .pd-option-pill span:hover {
        background: #f5924c;
        border-color: #f5924c;
        color: #fff;
    }

    .pd-option-pill.is-color span {
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .pd-color-swatch {
        width: 14px;
        height: 14px;
        border-radius: 50%;
        border: 1px solid rgba(0, 0, 0, .18);
        flex-shrink: 0;
        box-sizing: border-box;
    }

    .pd-option-pill input:checked + span .pd-color-swatch {
        border-color: rgba(255, 255, 255, .85);
        box-shadow: 0 0 0 1px rgba(0, 0, 0, .12);
    }

    .pd-qty-row {
        display: flex;
        align-items: center;
        gap: 12px;
        margin: 22px 0;
    }

    .pd-qty-label { font-size: 14px; font-weight: 700; }

    .pd-qty-control {
        display: inline-flex;
        align-items: center;
        border: 1px solid #ccc;
    }

    .pd-qty-btn {
        width: 36px;
        height: 36px;
        border: 0;
        background: #f5f5f5;
        font-size: 18px;
        cursor: pointer;
        line-height: 1;
    }

    .pd-qty-btn:hover { background: #eee; }

    .pd-qty-input {
        width: 48px;
        height: 36px;
        border: 0;
        border-left: 1px solid #ccc;
        border-right: 1px solid #ccc;
        text-align: center;
        font-size: 14px;
    }

    .pd-actions { display: flex; flex-direction: column; gap: 8px; margin-top: 8px; }

    .pd-btn-order {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        background: var(--brand);
        color: #fff;
        border: 0;
        padding: 14px 18px;
        font-size: 15px;
        font-weight: 700;
        cursor: pointer;
        line-height: 1.2;
    }

    .pd-btn-order:hover { background: var(--brand-dark); }

    .pd-btn-cart {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        background: #fff;
        color: #111;
        border: 1px solid #111;
        padding: 14px 18px;
        font-size: 15px;
        font-weight: 700;
        cursor: pointer;
        line-height: 1.2;
    }

    .pd-btn-cart:hover { background: #f8f8f8; }

    .pd-tabs {
        display: flex;
        justify-content: center;
        gap: 28px;
        border-bottom: 1px solid #ddd;
        margin-bottom: 0;
    }

    .pd-tab {
        background: none;
        border: 0;
        padding: 12px 0;
        font-size: 14px;
        font-weight: 700;
        letter-spacing: .3px;
        text-transform: uppercase;
        color: #888;
        cursor: pointer;
        border-bottom: 3px solid transparent;
        margin-bottom: -1px;
    }

    .pd-tab.active {
        color: #f5924c;
        border-bottom-color: #f5924c;
    }

    .pd-tab-panel {
        display: none;
        border: 1px solid #ddd;
        border-top: 0;
        padding: 28px 32px;
        background: #fff;
    }

    .pd-tab-panel.active { display: block; }

    .pd-description { font-size: 15px; line-height: 1.6; }
    .pd-description img { max-width: 100%; height: auto; display: block; margin: 0 auto 16px; }

    .pd-reviews-empty {
        font-size: 14px;
        color: #444;
        margin-bottom: 24px;
    }

    .pd-review-item {
        padding: 16px 0;
        border-bottom: 1px solid #eee;
    }

    .pd-review-item:last-of-type { border-bottom: 0; }

    .pd-review-item-head {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 8px;
        flex-wrap: wrap;
    }

    .pd-review-item-name { font-weight: 700; font-size: 15px; }

    .pd-review-stars-display { color: #f5b301; font-size: 16px; letter-spacing: 1px; }

    .pd-review-item-body { font-size: 14px; line-height: 1.6; color: #333; margin: 0; }

    .pd-review-item-image {
        margin-top: 10px;
        max-width: 120px;
        border: 1px solid #eee;
    }

    .pd-review-form-title {
        font-size: 22px;
        font-weight: 400;
        margin: 0 0 22px;
    }

    .pd-review-field { margin-bottom: 18px; }

    .pd-review-field label {
        display: block;
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 8px;
        color: #111;
    }

    .pd-review-field label .req { color: #c0392b; }

    .pd-review-field input[type="text"],
    .pd-review-field textarea {
        width: 100%;
        border: 1px solid #ccc;
        padding: 10px 12px;
        font-size: 14px;
        color: #111;
        background: #fff;
        box-sizing: border-box;
    }

    .pd-review-field textarea { min-height: 120px; resize: vertical; }

    .pd-review-html-note {
        font-size: 12px;
        color: #c0392b;
        margin-top: 6px;
    }

    .pd-stars {
        display: flex;
        gap: 4px;
    }

    .pd-star {
        background: none;
        border: 0;
        padding: 0;
        font-size: 28px;
        line-height: 1;
        color: #f5b301;
        cursor: pointer;
        opacity: .45;
        transition: opacity .15s;
    }

    .pd-star.active,
    .pd-star:hover { opacity: 1; }

    .pd-review-image-upload {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .pd-review-image-btn {
        width: 52px;
        height: 52px;
        border: 1px solid #ccc;
        background: #fafafa;
        font-size: 28px;
        line-height: 1;
        color: #666;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .pd-review-image-btn:hover { border-color: #999; }

    .pd-review-image-preview {
        width: 52px;
        height: 52px;
        object-fit: cover;
        border: 1px solid #ccc;
        display: none;
    }

    .pd-review-image-preview.visible { display: block; }

    .pd-captcha-row {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 10px;
    }

    .pd-captcha-row input {
        flex: 1 1 200px;
        border: 1px solid #ccc;
        padding: 10px 12px;
        font-size: 14px;
        min-width: 160px;
    }

    .pd-captcha-img {
        display: block;
        border: 1px solid #ddd;
        cursor: pointer;
        margin-top: 4px;
    }

    .pd-review-submit-wrap {
        display: flex;
        justify-content: flex-end;
        margin-top: 24px;
    }

    .pd-review-submit {
        background: #111;
        color: #fff;
        border: 0;
        padding: 12px 32px;
        font-size: 14px;
        font-weight: 700;
        letter-spacing: .5px;
        cursor: pointer;
        text-transform: uppercase;
    }

    .pd-review-submit:hover { background: #333; }

    .pd-field-error { font-size: 12px; color: #c0392b; margin-top: 4px; }

    .pd-related {
        margin-top: 42px;
    }

    .pd-related-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 18px;
    }

    .pd-related-head h2 {
        font-size: 24px;
        font-weight: 400;
        margin: 0;
    }

    .pd-related-nav {
        display: flex;
        gap: 8px;
    }

    .pd-related-nav button {
        width: 36px;
        height: 36px;
        border: 1px solid #ccc;
        border-radius: 50%;
        background: #fff;
        cursor: pointer;
        font-size: 18px;
        line-height: 1;
    }

    .pd-related-nav button:hover { border-color: #f5924c; color: #f5924c; }

    .pd-related-track-wrap { overflow: hidden; }

    .pd-related-track {
        display: flex;
        gap: 18px;
        transition: transform .35s ease;
    }

    .pd-related-track .product-card {
        flex: 0 0 calc(20% - 15px);
        min-width: calc(20% - 15px);
    }

    .pd-lightbox {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,.9);
        z-index: 200;
        display: none;
        align-items: center;
        justify-content: center;
    }

    .pd-lightbox.open { display: flex; }

    .pd-lightbox img { max-width: 90%; max-height: 90%; }

    .pd-lightbox-close,
    .pd-lightbox-prev,
    .pd-lightbox-next {
        position: absolute;
        background: transparent;
        border: 0;
        color: #fff;
        font-size: 32px;
        cursor: pointer;
    }

    .pd-lightbox-close { top: 20px; right: 30px; }
    .pd-lightbox-prev { left: 20px; }
    .pd-lightbox-next { right: 20px; }

    @media (max-width: 991px) {
        .pd-top { flex-direction: column; }
        .pd-gallery { flex: 1 1 auto; max-width: 100%; width: 100%; }
        .pd-gallery-dots { display: flex; }
        .pd-related-track .product-card {
            flex: 0 0 calc(50% - 9px);
            min-width: calc(50% - 9px);
        }
    }

    @media (max-width: 767px) {
        .pd-actions {
            align-items: stretch;
            gap: 10px;
        }

        .pd-btn-order,
        .pd-btn-cart {
            width: 100%;
            max-width: none;
            padding: 13px 16px;
            font-size: 14px;
            gap: 8px;
        }

        .pd-btn-order svg,
        .pd-btn-cart svg {
            width: 18px;
            height: 18px;
        }
    }

    @media (max-width: 575px) {
        .pd-option-pill {
            max-width: calc((100% - 8px) / 2);
        }

        .pd-title { font-size: 22px; }
        .pd-related-track {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            transform: none !important;
        }
        .pd-related-track .product-card {
            flex: unset;
            min-width: 0;
            width: 100%;
        }
        .pd-related-nav { display: none; }
    }
</style>

<div class="container pd-page">
    @if($product->category)
        <nav class="pd-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" aria-label="Home">
                <svg viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
            </a>
            &rsaquo;
            <a href="{{ route('category.show', $product->category->slug) }}">{{ $product->category->name }}</a>
            &rsaquo;
            <span class="current">{{ $product->name }}</span>
        </nav>
    @endif

    <div class="pd-top">
        <div class="pd-gallery" id="pd-gallery">
            <div class="pd-gallery-stage" id="pd-gallery-stage">
                <img class="pd-gallery-main" id="pd-gallery-main" src="{{ media_thumb($galleryImages->first()->image, 1000) }}" fetchpriority="high" alt="{{ $product->name }}">
                @if($galleryImages->count() > 1)
                    <div class="pd-gallery-dots" id="pd-gallery-dots" aria-hidden="true">
                        @foreach($galleryImages as $index => $image)
                            <span class="pd-gallery-dot @if($index === 0) active @endif" data-index="{{ $index }}"></span>
                        @endforeach
                    </div>
                @endif
            </div>
            @if($galleryImages->count() > 1)
                <div class="pd-gallery-thumbs">
                    @foreach($galleryImages as $index => $image)
                        <img
                            class="pd-gallery-thumb @if($index === 0) active @endif"
                            src="{{ media_thumb($image->image, 300) }}"
                            loading="lazy"
                            alt="{{ $image->alt_text ?? $product->name }}"
                            data-index="{{ $index }}"
                        >
                    @endforeach
                </div>
            @endif
        </div>

        <div class="pd-info">
            <h1 class="pd-title">{{ $product->name }}</h1>

            <div class="pd-price-row">
                <span class="pd-price-current" id="pd-price-current">{{ money_bd($displayPrice) }}</span>
                <span class="pd-price-old" id="pd-price-old" @if(!$hasSale || $savings <= 0) hidden @endif>{{ money_bd($comparePrice) }}</span>
                <span class="pd-save-badge" id="pd-save-badge" @if(!$hasSale || $savings <= 0) hidden @endif>{{ money_bd($savings) }} সাশ্রয় ({{ $savingsPercent }}%)</span>
            </div>

            @php $pdPoints = app(\App\Services\RewardService::class)->pointsForAmount((float) $displayPrice); @endphp
            @if($pdPoints > 0)
                <p class="pd-reward-note">🎁 এটি কিনলে <strong>{{ $pdPoints }} রিওয়ার্ড পয়েন্ট</strong> পাবেন</p>
            @endif
            <p class="pd-meta"><strong>প্রোডাক্ট কোড:</strong> {{ $product->sku ?: 'N/A' }}</p>
            <p class="pd-meta">
                <strong>স্টক:</strong>
                <span id="pd-availability-top" class="{{ $inStock ? 'pd-in-stock' : 'pd-out-stock' }}">{{ $stockLabels[$stockStatus] ?? $stockStatus }}</span>
            </p>

            <form action="{{ route('cart.add', $product) }}" method="post" id="pd-product-form" class="js-cart-add-form" data-base-price="{{ $product->effectivePrice() }}" data-base-price-formatted="{{ money_bd($product->effectivePrice()) }}" data-option-map='@json($optionPayload)' data-base-status="{{ $stockStatus }}" data-base-status-label="{{ $stockLabels[$stockStatus] ?? $stockStatus }}" data-base-stock="{{ $availableStock }}">
                @csrf

                @foreach($optionGroups as $group)
                    <div class="pd-option-group">
                        <div class="pd-option-label"><span class="req">*</span> {{ $group['label'] }}</div>
                        <div class="pd-option-pills">
                            @foreach($group['options'] as $index => $option)
                                <label class="pd-option-pill {{ $option->isCompatibilityOption() ? 'is-compatibility' : '' }}{{ $option->isColorOption() ? ' is-color' : '' }}">
                                    <input
                                        type="radio"
                                        name="option_ids[{{ $group['key'] }}]"
                                        value="{{ $option->id }}"
                                        @checked(old('option_ids.'.$group['key']) ? old('option_ids.'.$group['key']) == $option->id : $index === 0)
                                        @if($index === 0) required @endif
                                        data-option-id="{{ $option->id }}"
                                        data-is-compatibility="{{ $option->isCompatibilityOption() ? 1 : 0 }}"
                                        data-is-color="{{ $option->isColorOption() ? 1 : 0 }}"
                                        data-price-adjustment="{{ (float) $option->price_adjustment }}"
                                    >
                                    <span>
                                        @if($option->isColorOption() && $option->color_hex)
                                            <i class="pd-color-swatch" style="background-color: {{ $option->color_hex }};" aria-hidden="true"></i>
                                        @endif
                                        {{ $option->deviceModel?->name ?? $option->value }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <p class="pd-meta" hidden>
                    <strong>স্টক:</strong>
                    <span id="pd-availability-inline" class="{{ $inStock ? 'pd-in-stock' : 'pd-out-stock' }}">{{ $stockLabels[$stockStatus] ?? $stockStatus }}</span>
                </p>

                <div class="pd-qty-row">
                    <span class="pd-qty-label">পরিমাণ</span>
                    <div class="pd-qty-control">
                        <button type="button" class="pd-qty-btn" id="pd-qty-minus" aria-label="কমান">−</button>
                        <input type="number" name="quantity" id="pd-qty" value="{{ old('quantity', 1) }}" min="1" @if($availableStock > 0) max="{{ $availableStock }}" @endif class="pd-qty-input" required>
                        <button type="button" class="pd-qty-btn" id="pd-qty-plus" aria-label="বাড়ান">+</button>
                    </div>
                </div>

                <div class="pd-actions">
                    <button class="pd-btn-order" id="pd-btn-order" type="submit" name="checkout" value="1" @disabled(!$inStock)>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49A1.003 1.003 0 0020 4H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/></svg>
                        {{ __('messages.order_now') }}
                    </button>
                    <button class="pd-btn-cart" id="pd-btn-cart" type="submit" @disabled(!$inStock)>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                        {{ __('messages.add_to_cart') }}
                    </button>
                </div>
            </form>

            @if($stockStatus === 'out_of_stock')
                <form action="{{ route('product.stock-alert', $product) }}" method="post" class="mt-3">
                    @csrf
                    <p class="pd-meta"><strong>{{ __('messages.stock_alert') }}</strong></p>
                    <input type="email" name="email" class="form-control mb-2" placeholder="আপনার ইমেইল" value="{{ auth()->user()?->email }}" required>
                    <button class="pd-btn-order" type="submit" style="max-width:280px;">{{ __('messages.stock_alert') }}</button>
                </form>
            @endif

            @php
                $pdWhatsapp = whatsapp_link('আমি এই প্রোডাক্টটি অর্ডার করতে চাই: ' . $product->name . ' — ' . route('product.show', $product->slug));
                $pdFreeMin = (float) setting('free_delivery_min', '0');
            @endphp
            @if($pdWhatsapp && $inStock)
                <a href="{{ $pdWhatsapp }}" class="pd-btn-whatsapp" target="_blank" rel="noopener">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M17.5 14.4c-.3-.1-1.8-.9-2-1-.3-.1-.5-.1-.7.1-.2.3-.8 1-.9 1.2-.2.2-.3.2-.6.1-.3-.1-1.3-.5-2.4-1.5-.9-.8-1.5-1.8-1.7-2.1-.2-.3 0-.5.1-.6l.4-.5c.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5-.1-.1-.7-1.6-.9-2.2-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.5s1.1 2.9 1.2 3.1c.1.2 2.1 3.2 5.1 4.5.7.3 1.3.5 1.7.6.7.2 1.4.2 1.9.1.6-.1 1.8-.7 2-1.4.2-.7.2-1.3.2-1.4-.1-.1-.3-.2-.6-.3zM12 21.8c-1.8 0-3.5-.5-5-1.4l-.4-.2-3.7 1 1-3.6-.2-.4C2.7 15.6 2.2 13.8 2.2 12 2.2 6.6 6.6 2.2 12 2.2c2.6 0 5.1 1 6.9 2.9 1.8 1.8 2.9 4.3 2.9 6.9 0 5.4-4.4 9.8-9.8 9.8zM20.5 3.5C18.2 1.2 15.2 0 12 0 5.4 0 .1 5.3.1 11.9c0 2.1.6 4.2 1.6 6L0 24l6.3-1.6c1.8 1 3.8 1.5 5.7 1.5 6.6 0 11.9-5.3 11.9-11.9 0-3.2-1.2-6.2-3.4-8.5z"/></svg>
                    WhatsApp-এ অর্ডার / প্রশ্ন করুন
                </a>
            @endif

            @if(setting('trust_bar_enabled', '1') === '1')
                <ul class="pd-trust">
                    <li><span class="pd-trust-icon">🚚</span><span><strong>ডেলিভারি:</strong> {{ setting('delivery_time_text') ?: 'সারা বাংলাদেশে হোম ডেলিভারি' }}@if($pdFreeMin > 0) · {{ money_bd($pdFreeMin) }}+ অর্ডারে ডেলিভারি ফ্রি @endif</span></li>
                    <li><span class="pd-trust-icon">💵</span><span><strong>ক্যাশ অন ডেলিভারি:</strong> পণ্য হাতে পেয়ে টাকা দিন</span></li>
                    @if(setting('return_policy_text'))
                        <li><span class="pd-trust-icon">↩️</span><span><strong>বাতিল / ফেরত:</strong> {{ setting('return_policy_text') }}</span></li>
                    @endif
                </ul>
            @endif
        </div>
    </div>

    @if($inStock)
        <div class="pd-sticky" id="pd-sticky" aria-hidden="true">
            <div class="pd-sticky-price">
                <span class="pd-sticky-name">{{ \Illuminate\Support\Str::limit($product->name, 34) }}</span>
                <strong id="pd-sticky-price">{{ money_bd($displayPrice) }}</strong>
            </div>
            @if($pdWhatsapp)
                <a href="{{ $pdWhatsapp }}" class="pd-sticky-wa" target="_blank" rel="noopener" aria-label="WhatsApp">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor"><path d="M17.5 14.4c-.3-.1-1.8-.9-2-1-.3-.1-.5-.1-.7.1-.2.3-.8 1-.9 1.2-.2.2-.3.2-.6.1-.3-.1-1.3-.5-2.4-1.5-.9-.8-1.5-1.8-1.7-2.1-.2-.3 0-.5.1-.6l.4-.5c.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5-.1-.1-.7-1.6-.9-2.2-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.5s1.1 2.9 1.2 3.1c.1.2 2.1 3.2 5.1 4.5.7.3 1.3.5 1.7.6.7.2 1.4.2 1.9.1.6-.1 1.8-.7 2-1.4.2-.7.2-1.3.2-1.4-.1-.1-.3-.2-.6-.3zM12 21.8c-1.8 0-3.5-.5-5-1.4l-.4-.2-3.7 1 1-3.6-.2-.4C2.7 15.6 2.2 13.8 2.2 12 2.2 6.6 6.6 2.2 12 2.2c2.6 0 5.1 1 6.9 2.9 1.8 1.8 2.9 4.3 2.9 6.9 0 5.4-4.4 9.8-9.8 9.8zM20.5 3.5C18.2 1.2 15.2 0 12 0 5.4 0 .1 5.3.1 11.9c0 2.1.6 4.2 1.6 6L0 24l6.3-1.6c1.8 1 3.8 1.5 5.7 1.5 6.6 0 11.9-5.3 11.9-11.9 0-3.2-1.2-6.2-3.4-8.5z"/></svg>
                </a>
            @endif
            <button type="button" class="pd-sticky-btn" id="pd-sticky-btn">{{ __('messages.order_now') }}</button>
        </div>
    @endif

    <div class="pd-tabs-wrap" id="reviews">
        <div class="pd-tabs" role="tablist">
            <button type="button" class="pd-tab @if(!$reviewsTabActive) active @endif" data-tab="description" role="tab">বিস্তারিত</button>
            <button type="button" class="pd-tab @if($reviewsTabActive) active @endif" data-tab="reviews" role="tab">রিভিউ ({{ $reviewCount }})</button>
        </div>

        <div class="pd-tab-panel @if(!$reviewsTabActive) active @endif" id="pd-panel-description" role="tabpanel">
            <div class="pd-description">
                @if($product->description)
                    {!! $product->description !!}
                @elseif($product->short_description)
                    <p>{{ $product->short_description }}</p>
                @else
                    <p>এই প্রোডাক্টের বিস্তারিত তথ্য শীঘ্রই যোগ করা হবে।</p>
                @endif
            </div>
            @if($product->video_url)
                <div class="mt-4">
                    <iframe width="100%" height="420" src="{{ $product->video_url }}" title="{{ $product->name }} video" frameborder="0" allowfullscreen loading="lazy"></iframe>
                </div>
            @endif
        </div>

        <div class="pd-tab-panel @if($reviewsTabActive) active @endif" id="pd-panel-reviews" role="tabpanel">
            @if($reviews->isEmpty())
                <p class="pd-reviews-empty">এখনো কোনো রিভিউ নেই। প্রথম রিভিউটি আপনিই লিখুন!</p>
            @else
                @foreach($reviews as $review)
                    <div class="pd-review-item">
                        <div class="pd-review-item-head">
                            <span class="pd-review-item-name">{{ $review->name }}</span>
                            <span class="pd-review-stars-display" aria-label="{{ $review->rating }} out of 5 stars">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</span>
                        </div>
                        @if($review->body)
                            <p class="pd-review-item-body">{{ $review->body }}</p>
                        @endif
                        @if($review->image)
                            <img class="pd-review-item-image" src="{{ media_url($review->image) }}" alt="Review image">
                        @endif
                    </div>
                @endforeach
            @endif

            <form action="{{ route('product.review', $product) }}" method="post" enctype="multipart/form-data" class="pd-review-form" id="pd-review-form">
                @csrf
                <input type="hidden" name="review_form" value="1">

                <h2 class="pd-review-form-title">রিভিউ লিখুন</h2>

                <div class="pd-review-field">
                    <label for="pd-review-name"><span class="req">*</span> আপনার নাম</label>
                    <input type="text" id="pd-review-name" name="name" value="{{ old('name', auth()->user()?->name) }}" required>
                    @error('name')<div class="pd-field-error">{{ $message }}</div>@enderror
                </div>

                <div class="pd-review-field">
                    <label for="pd-review-body"><span class="req">*</span> আপনার রিভিউ</label>
                    <textarea id="pd-review-body" name="body" required>{{ old('body') }}</textarea>
                    
                    @error('body')<div class="pd-field-error">{{ $message }}</div>@enderror
                </div>

                <div class="pd-review-field">
                    <label><span class="req">*</span> রেটিং</label>
                    <div class="pd-stars" id="pd-stars" role="group" aria-label="রেটিং">
                        @for($i = 1; $i <= 5; $i++)
                            <button type="button" class="pd-star" data-value="{{ $i }}" aria-label="{{ $i }} star{{ $i > 1 ? 's' : '' }}">☆</button>
                        @endfor
                    </div>
                    <input type="hidden" name="rating" id="pd-rating-input" value="{{ old('rating', '') }}" required>
                    @error('rating')<div class="pd-field-error">{{ $message }}</div>@enderror
                </div>

                <div class="pd-review-field">
                    <label>ছবি (ইচ্ছা হলে)</label>
                    <div class="pd-review-image-upload">
                        <button type="button" class="pd-review-image-btn" id="pd-review-image-btn" aria-label="ছবি দিন">+</button>
                        <img class="pd-review-image-preview" id="pd-review-image-preview" alt="">
                        <input type="file" name="image" id="pd-review-image-input" accept="image/*" hidden>
                    </div>
                    @error('image')<div class="pd-field-error">{{ $message }}</div>@enderror
                </div>

                <div class="pd-review-field">
                    <label>ক্যাপচা</label>
                    <div class="pd-captcha-row">
                        <span><span class="req">*</span> ছবির কোডটি নিচের বক্সে লিখুন</span>
                        <input type="text" name="captcha" id="pd-captcha-input" value="{{ old('captcha') }}" required autocomplete="off">
                    </div>
                    <img src="{{ route('captcha.review') }}?t={{ time() }}" alt="ক্যাপচা কোড" class="pd-captcha-img" id="pd-captcha-img" width="130" height="44" title="নতুন কোড পেতে ক্লিক করুন">
                    @error('captcha')<div class="pd-field-error">{{ $message }}</div>@enderror
                </div>

                <div class="pd-review-submit-wrap">
                    <button type="submit" class="pd-review-submit">রিভিউ পাঠান</button>
                </div>
            </form>
        </div>
    </div>

    @if($related->isNotEmpty())
        <section class="pd-related">
            <div class="pd-related-head">
                <h2>{{ __('messages.related_products') }}</h2>
                <div class="pd-related-nav">
                    <button type="button" id="pd-related-prev" aria-label="Previous products">&#8249;</button>
                    <button type="button" id="pd-related-next" aria-label="Next products">&#8250;</button>
                </div>
            </div>
            <div class="pd-related-track-wrap">
                <div class="pd-related-track" id="pd-related-track">
                    @foreach($related as $relatedProduct)
                        @include('storefront.partials.product-card', ['product' => $relatedProduct])
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</div>

<div class="pd-lightbox" id="pd-lightbox" aria-hidden="true">
    <button type="button" class="pd-lightbox-close" aria-label="বন্ধ করুন">&times;</button>
    <button type="button" class="pd-lightbox-prev" aria-label="Previous">&#8249;</button>
    <img id="pd-lightbox-img" src="" alt="">
    <button type="button" class="pd-lightbox-next" aria-label="Next">&#8250;</button>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const images = @json($galleryUrls);
    const imageTags = @json($galleryOptionTags);
    let current = 0;
    const main = document.getElementById('pd-gallery-main');
    const stage = document.getElementById('pd-gallery-stage');
    const thumbs = document.querySelectorAll('.pd-gallery-thumb');
    const dots = document.querySelectorAll('.pd-gallery-dot');
    const lightbox = document.getElementById('pd-lightbox');
    const lightboxImg = document.getElementById('pd-lightbox-img');
    let touchStartX = 0;
    let touchStartY = 0;
    let touchActive = false;

    function showImage(index) {
        if (!images.length) return;
        current = (index + images.length) % images.length;
        if (main) main.src = images[current];
        thumbs.forEach((t, i) => t.classList.toggle('active', i === current));
        dots.forEach((d, i) => d.classList.toggle('active', i === current));
        if (lightbox?.classList.contains('open') && lightboxImg) lightboxImg.src = images[current];
    }

    thumbs.forEach((thumb) => {
        thumb.addEventListener('click', () => showImage(parseInt(thumb.dataset.index, 10)));
    });

    // Show the photo that best matches the chosen colour / model:
    // a photo qualifies when every option it is tagged with is chosen; the most specific one wins.
    window.pdShowImageForOptions = function (selectedIds) {
        const chosen = new Set(selectedIds.map(Number));
        let best = -1, bestScore = 0;
        imageTags.forEach((tags, i) => {
            if (!tags.length || !tags.every((id) => chosen.has(id))) return;
            if (tags.length > bestScore) { best = i; bestScore = tags.length; }
        });
        if (best >= 0 && best !== current) {
            showImage(best);
            thumbs[best]?.scrollIntoView({ block: 'nearest', inline: 'center', behavior: 'smooth' });
        }
    };

    main?.addEventListener('click', () => {
        if (!lightbox || !lightboxImg) return;
        lightbox.classList.add('open');
        lightboxImg.src = images[current];
    });

    document.querySelector('.pd-lightbox-close')?.addEventListener('click', () => lightbox?.classList.remove('open'));
    document.querySelector('.pd-lightbox-prev')?.addEventListener('click', () => showImage(current - 1));
    document.querySelector('.pd-lightbox-next')?.addEventListener('click', () => showImage(current + 1));

    if (stage && images.length > 1) {
        stage.addEventListener('touchstart', (event) => {
            const touch = event.changedTouches[0];
            touchStartX = touch.clientX;
            touchStartY = touch.clientY;
            touchActive = true;
        }, { passive: true });

        stage.addEventListener('touchend', (event) => {
            if (!touchActive) return;
            touchActive = false;
            const touch = event.changedTouches[0];
            const dx = touch.clientX - touchStartX;
            const dy = touch.clientY - touchStartY;
            if (Math.abs(dx) < 40 || Math.abs(dx) < Math.abs(dy)) return;
            showImage(current + (dx < 0 ? 1 : -1));
        }, { passive: true });
    }

    const qty = document.getElementById('pd-qty');
    const productForm = document.getElementById('pd-product-form');
    const priceCurrent = document.getElementById('pd-price-current');
    const priceOld = document.getElementById('pd-price-old');
    const saveBadge = document.getElementById('pd-save-badge');
    const availabilityTop = document.getElementById('pd-availability-top');
    const availabilityInline = document.getElementById('pd-availability-inline');
    const addToCartButton = document.getElementById('pd-btn-cart');
    const orderNowButton = document.getElementById('pd-btn-order');

    function syncSelectedOptionState() {
        if (!productForm) return;

        const optionMap = JSON.parse(productForm.dataset.optionMap || '{}');
        const checkedOptions = Array.from(productForm.querySelectorAll('input[type="radio"]:checked'));

        if (!checkedOptions.length) {
            return;
        }

        const selected = checkedOptions
            .map((input) => optionMap[input.value])
            .filter(Boolean);

        const pricingOption = selected.find((option) => option.is_compatibility)
            || selected.find((option) => Number(option.price_adjustment) !== 0)
            || selected[0];

        if (!pricingOption) {
            return;
        }

        if (priceCurrent) {
            priceCurrent.textContent = pricingOption.price_formatted;
        }

        if (pricingOption.has_sale) {
            if (priceOld) {
                priceOld.textContent = pricingOption.compare_price_formatted;
                priceOld.hidden = false;
            }
            if (saveBadge) {
                saveBadge.textContent = pricingOption.savings_formatted + ' সাশ্রয় (' + pricingOption.savings_percent + '%)';
                saveBadge.hidden = false;
            }
        } else {
            if (priceOld) {
                priceOld.hidden = true;
            }
            if (saveBadge) {
                saveBadge.hidden = true;
            }
        }

        [availabilityTop, availabilityInline].forEach((el) => {
            if (!el) return;
            el.textContent = pricingOption.status_label;
            el.classList.toggle('pd-in-stock', ['in_stock', 'low_stock', 'backorder'].includes(pricingOption.status));
            el.classList.toggle('pd-out-stock', !['in_stock', 'low_stock', 'backorder'].includes(pricingOption.status));
        });

        const hasStock = ['in_stock', 'low_stock', 'backorder'].includes(pricingOption.status);
        if (qty) {
            qty.max = pricingOption.stock > 0 ? String(pricingOption.stock) : '';
            if (pricingOption.stock > 0 && Number(qty.value) > pricingOption.stock) {
                qty.value = String(pricingOption.stock);
            }
        }

        [addToCartButton, orderNowButton].forEach((button) => {
            if (button) {
                button.disabled = !hasStock;
            }
        });
    }
    document.getElementById('pd-qty-minus')?.addEventListener('click', () => {
        if (qty) qty.value = Math.max(1, parseInt(qty.value || '1', 10) - 1);
    });
    document.getElementById('pd-qty-plus')?.addEventListener('click', () => {
        if (qty) qty.value = Math.max(1, parseInt(qty.value || '1', 10) + 1);
    });

    document.querySelectorAll('#pd-product-form input[type="radio"]').forEach((input) => {
        input.addEventListener('change', syncSelectedOptionState);
        // the customer picked a colour / model → bring up its photo (not on page load: the main photo stays first)
        input.addEventListener('change', () => {
            const ids = Array.from(document.querySelectorAll('#pd-product-form input[type="radio"]:checked')).map((i) => i.value);
            window.pdShowImageForOptions?.(ids);
        });
    });

    syncSelectedOptionState();

    document.querySelectorAll('.pd-tab').forEach((tab) => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.pd-tab').forEach((t) => t.classList.remove('active'));
            document.querySelectorAll('.pd-tab-panel').forEach((p) => p.classList.remove('active'));
            tab.classList.add('active');
            document.getElementById('pd-panel-' + tab.dataset.tab)?.classList.add('active');
        });
    });

    const ratingInput = document.getElementById('pd-rating-input');
    const stars = document.querySelectorAll('.pd-star');

    function setRating(value) {
        if (!ratingInput) return;
        ratingInput.value = value;
        stars.forEach((star) => {
            const starValue = parseInt(star.dataset.value, 10);
            const active = starValue <= value;
            star.textContent = active ? '★' : '☆';
            star.classList.toggle('active', active);
        });
    }

    stars.forEach((star) => {
        star.addEventListener('click', () => setRating(parseInt(star.dataset.value, 10)));
    });

    if (ratingInput?.value) {
        setRating(parseInt(ratingInput.value, 10));
    }

    const imageBtn = document.getElementById('pd-review-image-btn');
    const imageInput = document.getElementById('pd-review-image-input');
    const imagePreview = document.getElementById('pd-review-image-preview');

    imageBtn?.addEventListener('click', () => imageInput?.click());

    imageInput?.addEventListener('change', () => {
        const file = imageInput.files?.[0];
        if (!file || !imagePreview) return;
        imagePreview.src = URL.createObjectURL(file);
        imagePreview.classList.add('visible');
    });

    const captchaImg = document.getElementById('pd-captcha-img');
    captchaImg?.addEventListener('click', () => {
        captchaImg.src = '{{ route('captcha.review') }}?t=' + Date.now();
    });

    if (window.location.hash === '#reviews') {
        document.querySelector('.pd-tab[data-tab="reviews"]')?.click();
    }

    document.getElementById('pd-review-form')?.addEventListener('submit', (e) => {
        if (!ratingInput?.value || parseInt(ratingInput.value, 10) < 1) {
            e.preventDefault();
            alert('অনুগ্রহ করে রেটিং (স্টার) দিন।');
        }
    });

    const track = document.getElementById('pd-related-track');
    const prev = document.getElementById('pd-related-prev');
    const next = document.getElementById('pd-related-next');
    let offset = 0;

    function slideRelated(direction) {
        if (!track) return;
        const card = track.querySelector('.product-card');
        if (!card) return;
        const step = card.offsetWidth + 18;
        const maxOffset = Math.max(0, track.scrollWidth - track.parentElement.clientWidth);
        offset = Math.max(0, Math.min(maxOffset, offset + direction * step));
        track.style.transform = 'translateX(-' + offset + 'px)';
    }

    prev?.addEventListener('click', () => slideRelated(-1));
    next?.addEventListener('click', () => slideRelated(1));
})();

// Mobile: sticky "order" bar appears once the main order button scrolls out of view
(function () {
    const bar = document.getElementById('pd-sticky');
    const actions = document.querySelector('.pd-actions');
    const mainBtn = document.getElementById('pd-btn-order');
    if (!bar || !actions || !mainBtn || !('IntersectionObserver' in window)) return;

    new IntersectionObserver((entries) => {
        const hidden = !entries[0].isIntersecting && entries[0].boundingClientRect.top < 0;
        bar.classList.toggle('is-visible', hidden);
        bar.setAttribute('aria-hidden', hidden ? 'false' : 'true');
    }).observe(actions);

    document.getElementById('pd-sticky-btn')?.addEventListener('click', () => mainBtn.click());

    const price = document.getElementById('pd-price-current');
    const stickyPrice = document.getElementById('pd-sticky-price');
    if (price && stickyPrice) {
        new MutationObserver(() => { stickyPrice.textContent = price.textContent; })
            .observe(price, { childList: true, characterData: true, subtree: true });
    }
})();
</script>
@endpush

@push('meta_pixel')
    @php
        $vcEventId = \App\Support\MetaPixel::newEventId('view_content');
        $vcParams = \App\Support\MetaPixel::productParams($product);
        \App\Support\MetaConversions::track('ViewContent', $vcEventId, $vcParams);
    @endphp
    <script>window.egTrack('ViewContent', @json($vcParams), @json($vcEventId));</script>
@endpush
