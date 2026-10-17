@extends('layouts.storefront')

@section('title', $category->meta_title ?: $category->name)
@section('meta_description', $category->meta_description ?: ($category->name . ' — ' . setting('site_name', 'Eternity Gadget') . ', Dhaka. Cash on delivery all over Bangladesh.'))

@section('content')
<h1 style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;">{{ $category->name }}</h1>
<style>
    .cat-page { padding: 24px 0 48px; }

    .cat-breadcrumb {
        text-align: center;
        margin-bottom: 22px;
        font-size: 14px;
        color: #111;
    }

    .cat-breadcrumb a { color: #111; text-decoration: none; }
    .cat-breadcrumb a:hover { color: var(--link-hover-color, #83B735); }
    .cat-breadcrumb .current { color: #f5924c; }
    .cat-breadcrumb svg { width: 14px; height: 14px; vertical-align: -2px; fill: currentColor; }

    .cat-layout { display: flex; gap: 28px; align-items: flex-start; }

    .cat-mobile-toolbar {
        display: none;
        margin-bottom: 16px;
    }

    .cat-mobile-filter-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: 1px solid #111;
        background: #fff;
        color: #111;
        padding: 10px 14px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
    }

    .cat-mobile-filter-btn svg {
        width: 16px;
        height: 16px;
        fill: currentColor;
    }

    .cat-mobile-filter-overlay {
        display: none;
    }

    .cat-sidebar {
        flex: 0 0 260px;
        background: #fff;
    }

    .cat-sidebar-header {
        display: none;
    }

    .cat-sidebar-title {
        font-size: 14px;
        font-weight: 700;
        letter-spacing: .4px;
        margin: 0 0 14px;
        text-transform: uppercase;
    }

    .cat-sidebar-group { margin-bottom: 4px; }

    .cat-sidebar-group:hover .cat-sidebar-children,
    .cat-sidebar-group.is-open .cat-sidebar-children { display: block; }

    .cat-sidebar-parent {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 10px 0;
        color: #111;
        text-decoration: none;
        font-size: 15px;
        border-bottom: 1px solid #eee;
    }

    .cat-sidebar-parent:hover { color: #f5924c; }

    .cat-sidebar-parent.is-active { color: #f5924c; font-weight: 700; }

    .cat-sidebar-children {
        display: none;
        list-style: none;
        margin: 0;
        padding: 4px 0 8px 14px;
    }

    .cat-sidebar-group.is-open .cat-sidebar-children { display: block; }

    .cat-sidebar-children a {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        padding: 7px 0;
        color: #333;
        text-decoration: none;
        font-size: 14px;
        border-bottom: 1px solid #f3f3f3;
    }

    .cat-sidebar-children a:hover,
    .cat-sidebar-children a.is-active { color: #f5924c; }

    .cat-count-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 28px;
        height: 22px;
        padding: 0 7px;
        border-radius: 3px;
        background: #111;
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        flex-shrink: 0;
    }

    .cat-sidebar-parent.is-active .cat-count-badge,
    .cat-sidebar-children a.is-active .cat-count-badge { background: #f5924c; }

    .cat-price-box {
        margin-top: 28px;
        padding-top: 22px;
        border-top: 1px solid #eee;
    }

    .cat-price-range {
        width: 100%;
        accent-color: #f5924c;
        margin: 12px 0 8px;
    }

    .cat-filter-btn {
        display: block;
        width: 100%;
        background: #111;
        color: #fff;
        border: 0;
        padding: 10px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
    }

    .cat-filter-btn:hover { background: #333; }

    .cat-main { flex: 1 1 auto; min-width: 0; }

    .cat-sub-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 22px;
    }

    .cat-pill {
        display: inline-block;
        padding: 9px 16px;
        border: 1px solid #ddd;
        border-radius: 4px;
        background: #fff;
        color: #111;
        text-decoration: none;
        font-size: 13px;
        transition: border-color .15s, color .15s;
    }

    .cat-pill:hover,
    .cat-pill.is-active {
        border-color: #f5924c;
        color: #f5924c;
    }

    .cat-toolbar {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 14px;
        margin-bottom: 22px;
        flex-wrap: wrap;
    }

    .cat-toolbar label {
        font-size: 13px;
        color: #444;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .cat-toolbar select {
        border: 1px solid #ddd;
        padding: 7px 10px;
        font-size: 13px;
        background: #fff;
        color: #111;
    }

    .cat-product-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 18px;
    }

    .cat-empty {
        grid-column: 1 / -1;
        padding: 40px 0;
        text-align: center;
        color: #666;
    }

    .sf-pagination-wrap { display: flex; justify-content: center; margin-top: 28px; }
    .sf-pagination {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px;
        background: #fff;
        border: 1px solid #e8e8e8;
        border-radius: 999px;
        box-shadow: 0 8px 24px rgba(0,0,0,.06);
    }
    .sf-page-list {
        display: flex;
        align-items: center;
        gap: 4px;
        list-style: none;
        margin: 0;
        padding: 0;
    }
    .sf-page-num,
    .sf-page-btn {
        min-width: 38px;
        height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        text-decoration: none;
        color: #222;
        font-weight: 700;
        font-size: 14px;
        transition: background .15s, color .15s;
    }
    .sf-page-num:hover,
    .sf-page-btn:hover { background: #f3f4f6; color: #111; }
    .sf-page-num.is-active {
        background: #111;
        color: #fff;
    }
    .sf-page-btn.is-disabled { opacity: .35; pointer-events: none; }
    .sf-page-ellipsis { padding: 0 6px; color: #999; font-weight: 700; }

    @media (max-width: 1199px) {
        .cat-product-grid { grid-template-columns: repeat(3, 1fr); }
    }

    @media (max-width: 991px) {
        .cat-layout { flex-direction: column; }
        .cat-mobile-toolbar { display: flex; }
        .cat-sub-pills { display: none; }
        .cat-mobile-filter-overlay {
            display: block;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity .2s ease, visibility .2s ease;
            z-index: 190;
        }
        .cat-mobile-filter-overlay.is-open {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }
        .cat-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: min(360px, 88vw);
            max-width: 88vw;
            height: 100vh;
            overflow-y: auto;
            background: #fff;
            padding: 18px 16px 24px;
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.18);
            transform: translateX(-100%);
            visibility: hidden;
            pointer-events: none;
            transition: transform .24s ease, visibility .24s ease;
            z-index: 200;
        }
        .cat-sidebar.is-open {
            transform: translateX(0);
            visibility: visible;
            pointer-events: auto;
        }
        .cat-sidebar-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }
        .cat-sidebar-close {
            border: 0;
            background: transparent;
            font-size: 28px;
            line-height: 1;
            cursor: pointer;
            color: #111;
            padding: 0;
        }
        .cat-product-grid { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 575px) {
        .cat-product-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
    }
</style>

<div class="container cat-page">
    <nav class="cat-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('home') }}" aria-label="Home">
            <svg viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
        </a>
        &rsaquo;
        @if($rootCategory && $rootCategory->id !== $category->id)
            <a href="{{ route('category.show', $rootCategory->slug) }}">{{ $rootCategory->name }}</a>
            &rsaquo;
        @endif
        <span class="current">{{ $category->name }}</span>
    </nav>

    <div class="cat-mobile-toolbar">
        <button type="button" class="cat-mobile-filter-btn" id="cat-mobile-filter-open" aria-expanded="false" aria-controls="cat-sidebar">
            <svg viewBox="0 0 24 24"><path d="M3 5h18v2H3zm3 6h12v2H6zm3 6h6v2H9z"/></svg>
            <span>ফিল্টার</span>
        </button>
    </div>

    <div class="cat-mobile-filter-overlay" id="cat-mobile-filter-overlay"></div>

    <div class="cat-layout">
        <aside class="cat-sidebar" id="cat-sidebar" aria-hidden="true">
            <div class="cat-sidebar-header">
                <h2 class="cat-sidebar-title">ফিল্টার</h2>
                <button type="button" class="cat-sidebar-close" id="cat-mobile-filter-close" aria-label="বন্ধ করুন">&times;</button>
            </div>
            <h2 class="cat-sidebar-title">Categories</h2>

            @foreach($rootCategories as $root)
                @php
                    $isRootActive = ($rootCategory && $rootCategory->id === $root->id);
                    $rootCount = $categoryCounts[$root->id] ?? 0;
                @endphp
                <div class="cat-sidebar-group {{ $isRootActive ? 'is-open' : '' }}">
                    <a href="{{ route('category.show', $root->slug) }}" class="cat-sidebar-parent {{ $isRootActive && $category->id === $root->id ? 'is-active' : '' }}">
                        <span>{{ $root->name }}</span>
                        <span class="cat-count-badge">{{ $rootCount }}</span>
                    </a>
                    @if($root->children->isNotEmpty())
                        <ul class="cat-sidebar-children">
                            @foreach($root->children as $child)
                                <li>
                                    <a href="{{ route('category.show', $child->slug) }}" class="{{ $category->id === $child->id ? 'is-active' : '' }}">
                                        <span>{{ $child->name }}</span>
                                        <span class="cat-count-badge">{{ $categoryCounts[$child->id] ?? 0 }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach

            <div class="cat-price-box">
                <h2 class="cat-sidebar-title">দাম</h2>
                <form method="get" action="{{ route('category.show', $category->slug) }}" id="cat-price-form">
                    @if(request('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
                    @if(request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}">@endif
                    @if(request('per_page'))<input type="hidden" name="per_page" value="{{ request('per_page') }}">@endif
                    <input type="hidden" name="price_filter" value="1">
                    <input type="hidden" name="min_price" id="cat-min-price" value="{{ $filterMin }}">
                    <input type="hidden" name="max_price" id="cat-max-price" value="{{ $filterMax }}">

                    <input type="range" class="cat-price-range" id="cat-price-slider" min="{{ $boundMin }}" max="{{ $boundMax }}" value="{{ $filterMax }}">
                    <div class="cat-price-values">
                        {{ money_bd($filterMin) }} – {{ money_bd($filterMax) }}
                    </div>
                    <button type="submit" class="cat-filter-btn">দেখুন</button>
                </form>
            </div>
        </aside>

        <div class="cat-main">
            @if($subcategories->isNotEmpty())
                <div class="cat-sub-pills">
                    @if($rootCategory)
                        <a href="{{ route('category.show', $rootCategory->slug) }}" class="cat-pill {{ $category->id === $rootCategory->id ? 'is-active' : '' }}">
                            All {{ $rootCategory->name }} ({{ $categoryCounts[$rootCategory->id] ?? 0 }})
                        </a>
                    @endif
                    @foreach($subcategories as $sub)
                        <a href="{{ route('category.show', $sub->slug) }}" class="cat-pill {{ $category->id === $sub->id ? 'is-active' : '' }}">
                            {{ $sub->name }} ({{ $categoryCounts[$sub->id] ?? 0 }})
                        </a>
                    @endforeach
                </div>
            @endif

            <form method="get" class="cat-toolbar">
                @if(request('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
                @if(request('price_filter'))<input type="hidden" name="price_filter" value="1">@endif
                @if(request('min_price'))<input type="hidden" name="min_price" value="{{ request('min_price') }}">@endif
                @if(request('max_price'))<input type="hidden" name="max_price" value="{{ request('max_price') }}">@endif

                <label>
                    সাজান
                    <select name="sort" onchange="this.form.submit()">
                        <option value="">ডিফল্ট</option>
                        <option value="price_asc" @selected(request('sort') === 'price_asc')>দাম: কম থেকে বেশি</option>
                        <option value="price_desc" @selected(request('sort') === 'price_desc')>দাম: বেশি থেকে কম</option>
                        <option value="name_asc" @selected(request('sort') === 'name_asc')>নাম: A থেকে Z</option>
                    </select>
                </label>
                <label>
                    দেখান
                    <select name="per_page" onchange="this.form.submit()">
                        <option value="24" @selected($perPage === 24)>24</option>
                        <option value="50" @selected($perPage === 50)>50</option>
                        <option value="100" @selected($perPage === 100)>100</option>
                    </select>
                </label>
            </form>

            <div class="cat-product-grid">
                @forelse($products as $product)
                    @include('storefront.partials.product-card', ['product' => $product])
                @empty
                    <p class="cat-empty">এই ক্যাটাগরিতে এখন কোনো প্রোডাক্ট নেই।</p>
                @endforelse
            </div>

            @if($products->hasPages())
                <div class="sf-pagination-wrap">{{ $products->onEachSide(1)->links('vendor.pagination.storefront') }}</div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const slider = document.getElementById('cat-price-slider');
    const minInput = document.getElementById('cat-min-price');
    const maxInput = document.getElementById('cat-max-price');
    const values = document.querySelector('.cat-price-values');
    if (!slider || !minInput || !maxInput) return;

    const boundMin = Number(slider.min);
    const format = (n) => '৳' + Number(n).toLocaleString('en-BD');

    slider.addEventListener('input', () => {
        maxInput.value = slider.value;
        minInput.value = boundMin;
        if (values) values.textContent = format(boundMin) + ' – ' + format(slider.value);
    });

    const sidebar = document.getElementById('cat-sidebar');
    const overlay = document.getElementById('cat-mobile-filter-overlay');
    const openButton = document.getElementById('cat-mobile-filter-open');
    const closeButton = document.getElementById('cat-mobile-filter-close');

    function openFilters() {
        if (!sidebar || !overlay || window.innerWidth > 991) return;
        sidebar.classList.add('is-open');
        overlay.classList.add('is-open');
        sidebar.setAttribute('aria-hidden', 'false');
        openButton?.setAttribute('aria-expanded', 'true');
        document.body.classList.add('mobile-nav-open');
    }

    function closeFilters() {
        if (!sidebar || !overlay) return;
        sidebar.classList.remove('is-open');
        overlay.classList.remove('is-open');
        sidebar.setAttribute('aria-hidden', 'true');
        openButton?.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('mobile-nav-open');
    }

    openButton?.addEventListener('click', openFilters);
    closeButton?.addEventListener('click', closeFilters);
    overlay?.addEventListener('click', closeFilters);
    window.addEventListener('resize', () => {
        if (window.innerWidth > 991) {
            closeFilters();
        }
    });
})();
</script>
@endpush
