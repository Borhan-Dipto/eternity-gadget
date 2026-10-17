<!DOCTYPE html>

<html lang="{{ app()->getLocale() }}" class="lg">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="description" content="@yield('meta_description', setting('meta_description', 'Eternity Gadget'))">

    <title>@yield('title', setting('site_name', 'Eternity Gadget'))</title>

    @php
        $seoSiteName = setting('site_name', 'Eternity Gadget');
        $seoTitle = trim($__env->yieldContent('title')) ?: $seoSiteName;
        $seoDescription = trim($__env->yieldContent('meta_description')) ?: setting('meta_description', $seoSiteName);
        $seoImage = trim($__env->yieldContent('og_image')) ?: media_url(setting('og_image') ?: setting('logo'));
        $seoNoindex = request()->routeIs('cart', 'checkout', 'checkout.*', 'account.*', 'orders.*', 'order.confirmed', 'wishlist', 'compare', 'search');
    @endphp
    <link rel="canonical" href="{{ url()->current() }}">
    @if($seoNoindex)
        <meta name="robots" content="noindex, follow">
    @endif
    {{-- Facebook / WhatsApp / Messenger link previews --}}
    <meta property="og:site_name" content="{{ $seoSiteName }}">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ \Illuminate\Support\Str::limit(strip_tags($seoDescription), 200) }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $seoImage }}">
    <meta property="og:locale" content="en_US">
    @yield('og_extra')
    <meta name="twitter:card" content="summary_large_image">

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="/brand-icons/favicon-32.png">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    <link rel="manifest" href="{{ asset('manifest.json') }}">

    <meta name="theme-color" content="#ffffff">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Self-hosted fonts: Inter (English) + Hind Siliguri (বাংলা). Loaded from our own server, no Google request. --}}
    <link rel="preload" href="{{ asset('fonts/site/inter-latin-wght-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('fonts/site/hind-siliguri-bengali-400-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
    <style>
        @font-face { font-family: 'Inter'; font-style: normal; font-display: swap; font-weight: 100 900;
            src: url('{{ asset('fonts/site/inter-latin-wght-normal.woff2') }}') format('woff2-variations'), url('{{ asset('fonts/site/inter-latin-wght-normal.woff2') }}') format('woff2');
            unicode-range: U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD; }
        @font-face { font-family: 'Inter'; font-style: normal; font-display: swap; font-weight: 100 900;
            src: url('{{ asset('fonts/site/inter-latin-ext-wght-normal.woff2') }}') format('woff2-variations'), url('{{ asset('fonts/site/inter-latin-ext-wght-normal.woff2') }}') format('woff2');
            unicode-range: U+0100-02BA,U+02BD-02C5,U+02C7-02CC,U+02CE-02D7,U+02DD-02FF,U+1E00-1E9F,U+1EF2-1EFF,U+2020,U+20A0-20AB,U+20AD-20C0,U+2113,U+2C60-2C7F,U+A720-A7FF; }
        @foreach([400, 500, 600, 700] as $w)
        @font-face { font-family: 'Hind Siliguri'; font-style: normal; font-display: swap; font-weight: {{ $w }};
            src: url('{{ asset('fonts/site/hind-siliguri-bengali-' . $w . '-normal.woff2') }}') format('woff2');
            unicode-range: U+0951-0952,U+0964-0965,U+0980-09FE,U+1CD0,U+1CD5-1CD6,U+1CD8,U+1CE1,U+1CEA,U+1CED,U+1CF2,U+1CF5-1CF7,U+200C-200D,U+20B9,U+25CC,U+A8F1; }
        @endforeach
        :root {
            --font-sans: 'Inter', 'Hind Siliguri', system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            --main-font: var(--font-sans);
            --heading-font: var(--font-sans);
            /* colours the old theme stylesheet expects (previously loaded from theme_font.css) */
            --main-color: #888888; --heading-color: #222222; --heading-font-weight: 500;
            --link-color: #222222; --link-hover-color: #0090F0;
            --button-color: #FFFFFF; --button-hover-color: #FFFFFF; --button-bg-color: #222222; --button-bg-hover-color: #0090F0;
        }
    </style>

    <link rel="stylesheet" href="{{ asset('catalog/view/javascript/xboost/digital_one/e0619659da23d9f95c81c72e70bf087e.css') }}?v={{ @filemtime(public_path('catalog/view/javascript/xboost/digital_one/e0619659da23d9f95c81c72e70bf087e.css')) }}">

    <script type="application/ld+json">

    {!! json_encode([

        '@context' => 'https://schema.org',

        '@type' => 'Organization',

        'name' => setting('site_name', 'Eternity Gadget'),

        'url' => url('/'),

        'logo' => media_url(setting('logo')),

        'email' => setting('email', 'support@eternitygadget.com'),

        'telephone' => setting('phone', '01867-683114'),

        'address' => [
            '@type' => 'PostalAddress',
            'addressLocality' => 'Dhaka',
            'addressCountry' => 'BD',
            'streetAddress' => setting('address', 'Dhaka, Bangladesh'),
        ],

        'sameAs' => array_values(array_filter([

            setting('facebook_url'),

            setting('instagram_url'),

            setting('youtube_url'),

        ])),

    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}

    </script>

    <style>

        :root {

            /* Brand colour from the logo; buttons that lead to an order use it everywhere */
            --brand: #F1592A;
            --brand-dark: #D8461A;
            --link-hover-color: {{ setting('accent_color', '#F1592A') }};

            --button-bg-color: {{ setting('button_color', '#000000') }};

            --page-gutter: 18px;

            --page-gutter-lg: 24px;

        }

        body {
            background: #fff;
            color: #111;
            font-family: var(--font-sans);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            font-size: 16px;
            line-height: 1.5;
            -webkit-text-size-adjust: 100%;
        }

        main .container,
        .account-page.container,
        footer > .container {
            box-sizing: border-box;
            width: 100%;
            max-width: 1440px;
            margin-left: auto;
            margin-right: auto;
            padding-left: var(--page-gutter) !important;
            padding-right: var(--page-gutter) !important;
        }

        @media (min-width: 992px) {
            main .container,
            .account-page.container,
            footer > .container {
                padding-left: var(--page-gutter-lg) !important;
                padding-right: var(--page-gutter-lg) !important;
            }
        }

        *, input, button, select, textarea {
            font-family: inherit;
        }

        /* Light frosted-glass header (iOS style) */
        .ig-header {
            background: rgba(255, 255, 255, 0.96);
            color: #1d1d1f;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
            transition: transform .28s ease, background-color .2s ease;
        }

        @supports ((-webkit-backdrop-filter: blur(1px)) or (backdrop-filter: blur(1px))) {
            .ig-header {
                background: rgba(255, 255, 255, 0.72);
                -webkit-backdrop-filter: saturate(180%) blur(20px);
                backdrop-filter: saturate(180%) blur(20px);
            }
        }

        .ig-header.is-hidden { transform: translateY(-100%); }

        body.has-fixed-header { padding-top: var(--header-height, 84px); }

        .ig-header a, .ig-header .btn-link { color: #1d1d1f; text-decoration: none; }

        .ig-header a:hover { color: var(--link-hover-color, #F1592A); }

        .ig-top { display: none; }

        .ig-header-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 0 15px;
            height: 84px;
            flex-wrap: nowrap;
        }

        .ig-logo {
            flex: 0 0 auto;
            height: 100%;
            display: flex;
            align-items: center;
            line-height: 0;
        }

        .ig-logo a {
            display: flex;
            align-items: center;
            height: 100%;
            line-height: 0;
        }

        .ig-logo img {
            max-height: 54px;
            height: auto;
            width: auto;
            display: block;
            object-fit: contain;
        }

        .ig-nav-center {
            flex: 1 1 auto;
            display: flex;
            justify-content: center;
            min-width: 0;
            overflow: visible;
        }

        .ig-menu {
            display: flex;
            flex-wrap: nowrap;
            align-items: center;
            gap: 18px;
            margin: 0;
            padding: 0;
            list-style: none;
            justify-content: center;
            font-family: var(--font-sans);
            font-weight: 700;
            font-size: 13px;
            text-transform: capitalize;
            white-space: nowrap;
        }

        .ig-menu li { position: relative; }

        .ig-menu > li > a,
        .ig-menu-more-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 8px 0;
            border-bottom: 2px solid transparent;
            transition: border-color .2s, color .2s;
            color: #1d1d1f;
            background: none;
            border-left: 0;
            border-right: 0;
            border-top: 0;
            cursor: pointer;
            font: inherit;
            font-weight: 700;
            text-transform: capitalize;
        }

        .ig-menu > li.active > a,
        .ig-menu > li:hover > a,
        .ig-menu-more:hover > .ig-menu-more-btn {
            border-bottom-color: var(--link-hover-color, #F1592A);
            color: var(--link-hover-color, #F1592A);
        }

        .menu-chevron { font-size: 10px; opacity: .85; }

        .ig-menu .ig-submenu,
        .ig-menu .ig-flyout,
        .ig-menu-more-panel {
            display: none;
            position: absolute;
            z-index: 40;
            min-width: 220px;
            background: #fff;
            padding: 10px 0;
            list-style: none;
            box-shadow: 0 10px 30px rgba(0,0,0,.15);
            top: 100%;
            left: 0;
            border-radius: 8px;
            margin: 0;
        }

        .ig-menu > li:hover > .ig-submenu,
        .ig-menu > li:hover > .ig-mega,
        .ig-menu-more:hover > .ig-menu-more-panel,
        .ig-menu-more:focus-within > .ig-menu-more-panel,
        .ig-submenu > li:hover > .ig-flyout {
            display: block;
        }

        .ig-menu .ig-submenu a,
        .ig-menu .ig-flyout a,
        .ig-menu-more-panel > li > a {
            color: #111 !important;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 8px 16px;
            font-weight: 500;
            border-bottom: none;
            white-space: nowrap;
        }

        .ig-menu .ig-submenu a:hover,
        .ig-menu .ig-flyout a:hover,
        .ig-menu-more-panel > li > a:hover {
            color: var(--link-hover-color) !important;
            background: #f8f8f8;
        }

        .ig-flyout {
            top: 0 !important;
            left: 100% !important;
            margin-left: 2px;
        }

        .ig-mega {
            display: none;
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            z-index: 50;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 16px 40px rgba(0,0,0,.18);
            padding: 18px;
            min-width: 480px;
            max-width: min(760px, 90vw);
            grid-template-columns: repeat(2, minmax(180px, 1fr));
            gap: 18px;
        }

        .ig-menu > li.has-mega:hover > .ig-mega {
            display: grid;
        }

        .ig-mega-col { min-width: 0; }

        .ig-mega-heading {
            display: block;
            color: #111 !important;
            font-weight: 800 !important;
            padding: 4px 8px 10px !important;
            border-bottom: 1px solid #eee;
            margin-bottom: 6px;
        }

        .ig-mega-col ul {
            list-style: none;
            margin: 0;
            padding: 0;
            max-height: 320px;
            overflow: auto;
        }

        .ig-mega-col a {
            color: #222 !important;
            display: block;
            padding: 7px 8px;
            font-weight: 500;
            border-radius: 6px;
        }

        .ig-mega-col a:hover {
            background: #f5f5f5;
            color: var(--link-hover-color) !important;
        }

        .ig-menu-more-dots {
            font-size: 22px;
            line-height: 1;
            letter-spacing: 1px;
        }

        .ig-menu-more-panel {
            right: 0;
            left: auto;
            min-width: 240px;
        }

        .ig-menu-more-panel .ig-submenu,
        .ig-menu-more-panel .ig-mega {
            top: 0;
            left: 100%;
            transform: none;
        }

        .ig-menu-more-panel > li:hover > .ig-submenu,
        .ig-menu-more-panel > li:hover > .ig-mega {
            display: block;
        }

        .ig-menu-more-panel > li.has-mega:hover > .ig-mega {
            display: grid;
            min-width: 420px;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 16px;
            justify-content: flex-end;
            flex: 0 0 auto;
            flex-shrink: 0;
        }

        .header-icon-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            background: none;
            border: 0;
            color: #1d1d1f !important;
            cursor: pointer;
            padding: 4px;
            position: relative;
            text-decoration: none;
            flex-shrink: 0;
        }

        .header-icon-btn svg { width: 22px; height: 22px; fill: currentColor; }

        .header-icon-btn:hover { color: var(--link-hover-color, #F1592A) !important; }

        .header-badge {
            position: absolute;
            top: -4px;
            right: -8px;
            background: #f5924c;
            color: #fff;
            font-size: 10px;
            font-weight: bold;
            min-width: 18px;
            height: 18px;
            line-height: 18px;
            border-radius: 50%;
            text-align: center;
            padding: 0 4px;
            font-family: var(--font-sans);
        }

        .header-badge:empty { display: none; }

        .account-dropdown { position: relative; }

        .account-dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            top: calc(100% + 10px);
            width: 240px;
            background: #fff;
            color: #111;
            border-radius: 10px;
            box-shadow: 0 12px 36px rgba(0,0,0,.2);
            z-index: 200;
            padding: 8px 0;
            list-style: none;
            margin: 0;
        }

        .account-dropdown.open .account-dropdown-menu { display: block; }

        .account-dropdown-menu a {
            display: block;
            padding: 10px 18px;
            color: #111;
            font-size: 14px;
        }

        .account-dropdown-menu a:hover { background: #f5f5f5; color: var(--link-hover-color); }

        .account-dropdown-menu hr { margin: 6px 0; border-color: #eee; }

        .search-panel {
            display: none;
            position: absolute;
            left: 0;
            right: 0;
            top: 100%;
            background: rgba(255, 255, 255, 0.97);
            padding: 14px 0;
            z-index: 150;
            border-top: 1px solid rgba(0, 0, 0, 0.06);
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
        }

        .search-panel.open { display: block; }

        @media (max-width: 767px) {
            .search-panel.open {
                position: fixed;
                top: var(--header-height, 64px);
                left: 0;
                right: 0;
                z-index: 160;
                padding: 12px 0 16px;
                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
            }

            .search-panel .ig-search { max-width: none; padding: 0 12px; }
        }

        .ig-search { display: flex; gap: 10px; position: relative; width: 100%; max-width: 600px; margin: 0 auto; }

        .ig-search input {
            border-radius: 28px;
            border: 2px solid var(--link-hover-color);
            padding: 12px 20px;
            width: 100%;
            background: #fff;
            color: #111;
            font-size: 15px;
        }

        .ig-search input::placeholder { color: #666; opacity: 1; }

        .ig-search button, .ig-btn {
            background: var(--button-bg-color);
            color: #fff;
            border: 0;
            padding: 12px 22px;
            border-radius: 28px;
            cursor: pointer;
            white-space: nowrap;
        }

        .search-autocomplete { position:absolute; top:100%; left:0; right:0; background:#fff; border:1px solid #eee; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,.12); z-index:50; max-height:320px; overflow:auto; display:none; margin-top:6px; }

        .search-autocomplete a { display:flex; gap:10px; padding:10px 14px; color:#111; text-decoration:none; align-items:center; }

        .search-autocomplete a:hover { background:#f5f5f5; }

        .search-autocomplete img { width:40px; height:40px; object-fit:cover; border-radius:6px; }

        .mini-cart { position:relative; }

        .mini-cart-dropdown {
            display: none;
            flex-direction: column;
            position: absolute;
            right: 0;
            top: calc(100% + 8px);
            width: 340px;
            max-height: min(70vh, 440px);
            background: #fff;
            color: #111;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,.18);
            z-index: 60;
            overflow: hidden;
        }

        .mini-cart.open .mini-cart-dropdown { display: flex; }

        .mini-cart-items-scroll {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            padding: 14px 14px 8px;
        }

        .mini-cart-footer {
            flex: 0 0 auto;
            padding: 12px 14px 14px;
            border-top: 1px solid #eee;
            background: #fff;
        }

        .mini-cart-item {
            display: flex;
            gap: 10px;
            margin-bottom: 12px;
            font-size: 14px;
            color: #111;
        }

        .mini-cart-item img { width: 52px; height: 52px; object-fit: cover; border: 1px solid #eee; flex-shrink: 0; }

        .mini-cart-item a {
            color: #111 !important;
            font-weight: 600;
            line-height: 1.35;
            display: block;
            margin-bottom: 4px;
        }

        .mini-cart-item a:hover { color: #f5924c !important; }

        .mini-cart-item-price { font-size: 13px; color: #666; }

        .mini-cart-subtotal-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            font-size: 15px;
        }

        .mini-cart-subtotal-row strong { color: #c0392b; font-size: 17px; }

        .mini-cart-actions { display: flex; flex-direction: column; gap: 8px; }

        .mini-cart-btn {
            display: block;
            width: 100%;
            text-align: center;
            padding: 12px 14px;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            border-radius: 0;
        }

        .mini-cart-btn-cart {
            background: #fff;
            color: #111 !important;
            border: 1px solid #111;
        }

        .mini-cart-btn-cart:hover { background: #f8f8f8; color: #111 !important; }

        .mini-cart-btn-checkout {
            background: var(--brand);
            color: #fff !important;
            border: 0;
        }

        .mini-cart-btn-checkout:hover { background: var(--brand-dark); color: #fff !important; }

        .notification-bell { position:relative; cursor:pointer; }

        .notification-dropdown { display:none; position:absolute; right:0; top:100%; width:280px; background:#fff; color:#111; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,.18); z-index:60; padding:12px; margin-top:8px; max-height:300px; overflow:auto; }

        .notification-bell.open .notification-dropdown { display:block; }

        .locale-switcher a { opacity:.7; }

        .locale-switcher a.active { opacity:1; font-weight:bold; text-decoration:underline; }

        .hero-carousel {
            position: relative;
            width: 100%;
            overflow: hidden;
            background: #111;
            touch-action: pan-y pinch-zoom;
            transition: height 0.35s ease;
            line-height: 0;
        }

        .hero-carousel-track {
            display: flex;
            align-items: flex-start;
            transition: transform 0.45s ease;
            will-change: transform;
        }

        .hero-carousel-slide {
            flex: 0 0 100%;
            min-width: 100%;
        }

        .hero-carousel-slide a {
            display: block;
            width: 100%;
            line-height: 0;
        }

        .hero-carousel-slide img {
            width: 100%;
            height: auto;
            max-width: 100%;
            display: block;
            vertical-align: top;
        }

        .hero-carousel-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            z-index: 2;
            width: 44px;
            height: 44px;
            border: 0;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.45);
            color: #fff;
            font-size: 28px;
            line-height: 1;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .hero-carousel-btn:hover {
            background: rgba(0, 0, 0, 0.7);
        }

        .hero-carousel-btn.prev { left: 14px; }
        .hero-carousel-btn.next { right: 14px; }

        .hero-carousel-dots {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 14px;
            z-index: 2;
            display: flex;
            justify-content: center;
            gap: 8px;
        }

        .hero-carousel-dot {
            width: 10px;
            height: 10px;
            border: 0;
            border-radius: 50%;
            padding: 0;
            background: rgba(255, 255, 255, 0.45);
            cursor: pointer;
        }

        .hero-carousel-dot.active {
            background: var(--link-hover-color, #F1592A);
            transform: scale(1.15);
        }

        @media (max-width: 767px) {
            .hero-carousel-btn {
                width: 36px;
                height: 36px;
                font-size: 22px;
            }
        }

        .section-title { display: flex; justify-content: space-between; align-items: center; margin: 40px 0 22px; gap: 12px; }

        .section-title h2 { font-size: 26px; margin: 0; font-weight: 400; line-height: 1.25; }

        .section-title a {
            font-family: var(--font-sans);
            font-size: 14px;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .category-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 16px;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
        }

        .product-scroll-row {
            display: flex;
            gap: 18px;
            overflow-x: auto;
            overflow-y: hidden;
            scroll-snap-type: x mandatory;
            -webkit-overflow-scrolling: touch;
            padding-bottom: 10px;
            scrollbar-width: thin;
            scrollbar-color: #ccc transparent;
        }

        .product-scroll-row::-webkit-scrollbar { height: 6px; }
        .product-scroll-row::-webkit-scrollbar-thumb { background: #ccc; border-radius: 3px; }

        .product-scroll-row .product-card {
            flex: 0 0 calc(25% - 14px);
            min-width: calc(25% - 14px);
            scroll-snap-align: start;
        }

        .floating-cart-widget {
            position: fixed;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            z-index: 85;
            text-decoration: none;
            box-shadow: -2px 2px 10px rgba(0,0,0,.14);
            opacity: 0.55;
            transition: opacity .2s ease;
            border: 0;
            padding: 0;
            cursor: pointer;
            font-family: inherit;
        }

        .floating-cart-top {
            background: #3a3a3a;
            color: #fff;
            padding: 8px 7px;
            min-width: 42px;
            text-align: center;
        }

        .floating-cart-top .fc-icon { display: block; margin-bottom: 3px; }

        .floating-cart-top .fc-icon svg { width: 15px; height: 15px; fill: #fff; }

        .floating-cart-top .fc-items {
            font-size: 8px;
            font-weight: 700;
            font-family: var(--font-sans);
            letter-spacing: .2px;
            line-height: 1.2;
        }

        .floating-cart-bottom {
            background: #f5924c;
            color: #fff;
            padding: 6px 7px;
            text-align: center;
            font-size: 9px;
            font-weight: 700;
            line-height: 1.2;
        }

        .floating-cart-widget:hover { opacity: 0.85; color: #fff; }

        .social-link { display: inline-flex; align-items: center; gap: 6px; margin-right: 12px; }

        .social-link svg { width: 18px; height: 18px; flex-shrink: 0; }

        .social-facebook { color: #1877F2; }

        .social-whatsapp { color: #25D366; }

        .auth-card {
            max-width: 440px;
            margin: 0 auto;
            padding: 32px;
            border: 1px solid #e8e8e8;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 8px 30px rgba(0,0,0,.06);
        }

        .auth-card h1 { font-size: 28px; margin-bottom: 8px; }

        .auth-card .form-control {
            padding: 12px 16px;
            border: 1px solid #ccc;
            border-radius: 8px;
            width: 100%;
            margin-bottom: 14px;
            color: #111;
            background: #fff;
        }

        .auth-card .ig-btn { width: 100%; padding: 14px; font-size: 16px; }

        .auth-links { margin-top: 20px; text-align: center; font-size: 14px; }

        .auth-links a { color: var(--link-hover-color); }

        .category-card {
            border: 1px solid #c4785a;
            background: #fff;
            padding: 18px 12px;
            text-align: center;
            height: 100%;
            border-radius: 8px;
            text-decoration: none;
            color: #111;
            transition: box-shadow .2s, transform .2s;
        }

        .category-card:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,.08);
            transform: translateY(-2px);
            color: #111;
        }

        .category-card img { height: 90px; width: auto; max-width: 100%; object-fit: contain; margin-bottom: 12px; }

        .category-card h4 { font-size: 16px; margin: 0; font-weight: 400; line-height: 1.35; }

        .product-card {
            border: 1px solid #e8e8e8;
            background: #fff;
            padding: 12px;
            text-align: center;
            height: 100%;
            display: flex;
            flex-direction: column;
            transition: border-color .2s;
            overflow: hidden;
            min-width: 0;
        }

        .product-card:hover { border-color: #d4a574; }

        .product-card-image-wrap { position: relative; margin-bottom: 10px; flex-shrink: 0; }

        .product-card img { width: 100%; height: auto; aspect-ratio: 1 / 1; object-fit: contain; object-position: center; background: #f8f8f8; display: block; }

        .product-card-title {
            font-size: 15px;
            margin: 0 0 8px;
            line-height: 1.4;
            font-weight: 400;
            min-height: 2.8em;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-card-title a { color: #111; text-decoration: none; display: block; }

        .product-card-title a:hover { color: var(--link-hover-color); }

        .product-card-price {
            margin: 0 0 12px;
            min-height: 1.5em;
            flex-shrink: 0;
        }

        .product-card-order {
            margin-top: auto;
            width: 100%;
            flex-shrink: 0;
        }

        .price-current { color: #c0392b; font-size: 16px; }

        .price-old { text-decoration: line-through; color: #888; margin-left: 6px; font-size: 13px; }

        .badge-sale {
            position: absolute;
            top: 0;
            right: 0;
            background: #e74c3c;
            color: #fff;
            padding: 4px 8px;
            font-size: 11px;
            font-weight: 700;
            border-radius: 0;
            font-family: var(--font-sans);
        }

        .btn-order-now {
            display: block;
            width: 100%;
            background: var(--brand);
            color: #fff;
            border: 0;
            padding: 10px 16px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            font-family: var(--font-sans);
            transition: background .2s;
        }

        .btn-order-now:hover { background: var(--brand-dark); }

        @keyframes orderNowPulse {
            0%, 100% { transform: scale(1); }
            35% { transform: scale(0.92); }
            55% { transform: scale(1.06); }
        }

        body.page-home .btn-order-now,
        body.page-category .btn-order-now,
        body.page-product button.pd-btn-order[name="checkout"] {
            animation: orderNowPulse 2.4s ease-in-out infinite;
            transform-origin: center center;
        }

        @keyframes homePageEnter {
            from { opacity: 0; transform: translateY(14px); }
            to { opacity: 1; transform: translateY(0); }
        }

        body.page-home main {
            animation: homePageEnter 0.95s ease-out both;
        }

        @media (prefers-reduced-motion: reduce) {
            body.page-home main,
            body.page-home .btn-order-now,
            body.page-category .btn-order-now,
            body.page-product button.pd-btn-order[name="checkout"] {
                animation: none;
            }
        }

        @media (max-width: 1199px) {
            .product-grid { grid-template-columns: repeat(3, 1fr); }
        }

        @media (max-width: 991px) {
            .product-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
        }

        @media (max-width: 767px) {
            .product-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
            .section-title { margin: 32px 0 18px; }
            .section-title h2 { font-size: 22px; }
            .section-title a { font-size: 13px; }
        }

        /* Light footer, same feel as the glass header; the logo stays readable */
        footer { margin-top: 50px; padding: 42px 0 20px; background: #f6f6f7; color: #444; border-top: 1px solid #ececec; }

        footer a { color: #333; text-decoration: none; }

        footer a:hover { color: var(--brand); }

        footer h4 {
            font-size: 17px;
            margin-bottom: 16px;
            font-weight: 600;
            color: #111 !important;
        }

        footer .footer-about { font-family: var(--font-sans); font-size: 14px; line-height: 1.6; color: #555; }

        footer .footer-contact { margin-top: 12px; font-size: 14px; line-height: 1.9; }

        footer .list-unstyled li { margin-bottom: 8px; }

        .footer-copyright {
            font-size: 13px;
            color: #777;
            background: transparent !important;
            text-align: center;
        }

        @media (max-width: 767px) {
            .footer-copyright {
                text-align: center !important;
            }
        }

        .footer-social { display: flex; gap: 14px; margin-top: 18px; }

        .footer-social a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border: 1px solid #d5d5d5;
            border-radius: 50%;
            color: #333;
            transition: border-color .2s, color .2s;
        }

        .footer-social a:hover { border-color: var(--link-hover-color); color: var(--link-hover-color); }

        .footer-social svg { width: 16px; height: 16px; fill: currentColor; }

        .footer-accordion-toggle {
            display: none;
            width: 100%;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 0;
            margin: 0 0 12px;
            border: 0;
            background: transparent;
            color: #111;
            cursor: pointer;
            font-family: inherit;
            text-align: left;
        }

        .footer-accordion-toggle h4 { margin: 0; pointer-events: none; }

        .footer-accordion-icon {
            font-size: 22px;
            line-height: 1;
            transition: transform .25s ease;
            flex-shrink: 0;
        }

        .footer-accordion-col.open .footer-accordion-icon { transform: rotate(45deg); }

        .mobile-bottom-nav {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 80;
            display: none;
            background: #fff;
            border-top: 1px solid #eee;
            box-shadow: 0 -4px 18px rgba(0, 0, 0, 0.08);
            padding-bottom: env(safe-area-inset-bottom, 0px);
        }

        .mobile-bottom-nav-inner {
            display: flex;
            align-items: stretch;
            justify-content: space-around;
        }

        .mobile-nav-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
            padding: 8px 4px 10px;
            color: #333;
            text-decoration: none;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            border: 0;
            background: transparent;
            cursor: pointer;
            font-family: var(--font-sans);
        }

        .mobile-nav-item svg {
            width: 22px;
            height: 22px;
            fill: currentColor;
        }

        .mobile-nav-item.active,
        .mobile-nav-item:hover { color: #e85d04; }

        .chat-widget {
            position: fixed;
            right: 18px;
            bottom: 24px;
            z-index: 90;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .chat-trigger-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 52px;
            height: 52px;
            background: #000;
            border: 0;
            border-radius: 50%;
            color: #fff;
            cursor: pointer;
            box-shadow: 0 4px 18px rgba(0,0,0,.25);
            flex-shrink: 0;
            transition: transform .2s ease;
        }

        .chat-trigger-btn:hover { transform: scale(1.05); }

        .chat-trigger-btn svg { width: 26px; height: 26px; fill: #fff; }

        .chat-popup {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            max-height: 0;
            opacity: 0;
            overflow: hidden;
            pointer-events: none;
            transition: max-height .35s ease, opacity .28s ease, margin-bottom .28s ease;
        }

        .chat-popup.open {
            max-height: 220px;
            opacity: 1;
            pointer-events: auto;
        }

        .chat-popup-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            color: #fff;
            text-decoration: none;
            box-shadow: 0 3px 12px rgba(0,0,0,.2);
            transform: translateY(16px) scale(0.85);
            opacity: 0;
            transition: transform .3s cubic-bezier(.34, 1.4, .64, 1), opacity .25s ease;
        }

        .chat-popup.open .chat-popup-link {
            transform: translateY(0) scale(1);
            opacity: 1;
        }

        .chat-popup.open .chat-popup-link:nth-child(1) { transition-delay: .06s; }
        .chat-popup.open .chat-popup-link:nth-child(2) { transition-delay: .12s; }
        .chat-popup.open .chat-popup-link:nth-child(3) { transition-delay: .18s; }

        .chat-popup-link:hover { transform: translateY(0) scale(1.08); color: #fff; }

        .chat-popup-link svg { width: 22px; height: 22px; fill: #fff; }

        .chat-popup-link.phone { background: #f5924c; }

        .chat-popup-link.facebook { background: #0084ff; }

        .chat-popup-link.whatsapp { background: #25D366; }

        .chat-popup-close { display: none; }

        .header-chat-trigger { margin-left: 2px; }

        .mobile-menu-toggle {
            display: none;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border: 0;
            background: transparent;
            color: #1d1d1f;
            cursor: pointer;
            padding: 0;
            flex-shrink: 0;
        }

        .mobile-menu-toggle svg { width: 26px; height: 26px; fill: currentColor; }

        .mobile-nav-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 180;
            opacity: 0;
            visibility: hidden;
            transition: opacity .25s ease, visibility .25s ease;
        }

        .mobile-nav-overlay.open { opacity: 1; visibility: visible; }

        .mobile-nav-drawer {
            position: fixed;
            top: 0;
            left: 0;
            width: min(320px, 88vw);
            height: 100%;
            background: #fff;
            color: #1d1d1f;
            z-index: 190;
            transform: translateX(-100%);
            transition: transform .3s ease;
            overflow-y: auto;
            font-family: var(--font-sans);
        }

        .mobile-nav-drawer.open { transform: translateX(0); }

        .mobile-nav-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 18px;
            border-bottom: 1px solid #eee;
            font-weight: 700;
            font-size: 16px;
        }

        .mobile-nav-close {
            border: 0;
            background: transparent;
            color: #1d1d1f;
            font-size: 28px;
            line-height: 1;
            cursor: pointer;
            padding: 0;
        }

        .mobile-nav-list {
            list-style: none;
            margin: 0;
            padding: 8px 0 24px;
        }

        .mobile-nav-list > li { border-bottom: 1px solid #f0f0f0; }

        .mobile-nav-list a,
        .mobile-sub-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            padding: 14px 18px;
            color: #1d1d1f;
            text-decoration: none;
            font-size: 15px;
            font-weight: 600;
            background: transparent;
            border: 0;
            text-align: left;
            cursor: pointer;
            font-family: inherit;
        }

        .mobile-nav-list a:hover,
        .mobile-sub-toggle:hover { color: var(--link-hover-color, #F1592A); }

        .mobile-sub-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: none;
            background: #f7f7f8;
        }

        .mobile-sub-list.open { display: block; }

        .mobile-sub-list a {
            padding-left: 32px;
            font-size: 14px;
            font-weight: 500;
        }

        body.mobile-nav-open { overflow: hidden; }

        .ig-phone-wrap { width: 100%; }

        .ig-phone-field {
            display: flex;
            align-items: stretch;
            width: 100%;
            border: 1px solid #ccc;
            background: #fff;
            overflow: hidden;
        }

        .ig-phone-prefix {
            display: flex;
            align-items: center;
            padding: 0 12px;
            background: #ebebeb;
            color: #111;
            font-weight: 700;
            font-size: 14px;
            flex-shrink: 0;
            border-right: 1px solid #ddd;
            user-select: none;
        }

        .ig-phone-local {
            flex: 1;
            min-width: 0;
            border: 0;
            background: transparent;
            padding: 10px 12px;
            font-size: 14px;
            letter-spacing: 0.5px;
        }

        .ig-phone-local:focus { outline: none; }

        .account-field .ig-phone-field,
        .account-detail-row .ig-phone-field {
            border-color: #ccc;
        }

        .account-field .ig-phone-local,
        .account-detail-row .ig-phone-local {
            padding: 10px 12px;
            font-size: 14px;
        }

        .oc-field .ig-phone-field {
            border: 1px solid #e0e0e0;
            border-radius: 4px;
        }

        .oc-field .ig-phone-local {
            padding: 14px 12px;
            font-size: 15px;
        }

        .ig-phone-wrap .form-label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
        }

        .ig-phone-wrap .req { color: #c0392b; }

        @media (max-width: 991px) {
            .mobile-menu-toggle { display: flex; }
            .ig-nav-center { display: none; }
            .ig-header-inner {
                display: grid;
                grid-template-columns: 44px 1fr 44px;
                align-items: center;
                height: 64px;
                min-height: 64px;
                padding: 0 12px;
                gap: 0;
            }
            .mobile-menu-toggle { grid-column: 1; grid-row: 1; }
            .ig-logo {
                grid-column: 2;
                grid-row: 1;
                justify-self: center;
                height: auto;
            }
            .header-actions {
                grid-column: 3;
                grid-row: 1;
                justify-self: end;
                gap: 0;
            }
            .header-actions .header-icon-btn:not(.mini-cart),
            .header-actions .header-chat-trigger,
            .header-actions .notification-bell { display: none !important; }
            body.has-fixed-header { padding-top: 64px; --header-height: 64px; }
            .category-grid { grid-template-columns: repeat(3, 1fr); }
            .product-scroll-row .product-card {
                flex: 0 0 calc(50% - 9px);
                min-width: calc(50% - 9px);
            }
            .footer-accordion-toggle { display: flex; }
            .footer-accordion-panel { display: none; }
            .footer-accordion-col.open .footer-accordion-panel { display: block; }
            .footer-accordion-col > h4 { display: none; }
        }

        @media (max-width: 767px) {

            .ig-logo img { max-height: 42px; }

            .category-grid { grid-template-columns: repeat(3, 1fr); gap: 10px; }

            .category-card { padding: 14px 10px; }

            .category-card img { height: 72px; margin-bottom: 8px; }

            .category-card h4 { font-size: 14px; }

            .product-card { padding: 10px; }

            .product-card-title { font-size: 14px; min-height: auto; }

            .price-current { font-size: 15px; }

            .btn-order-now { font-size: 13px; padding: 11px 10px; }

            .product-scroll-row {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
                overflow-x: visible;
                scroll-snap-type: none;
                padding-bottom: 0;
            }

            .product-scroll-row .product-card {
                flex: unset;
                min-width: 0;
                width: 100%;
            }

            .floating-cart-widget { display: none !important; }

            .mobile-bottom-nav { display: block; }

            body { padding-bottom: calc(58px + env(safe-area-inset-bottom, 0px)); }

            footer { margin-bottom: calc(62px + env(safe-area-inset-bottom, 0px)); }

            .chat-widget { bottom: calc(70px + env(safe-area-inset-bottom, 0px)); right: 12px; }
            .chat-trigger-btn { width: 46px; height: 46px; opacity: .92; }
            .chat-trigger-btn svg { width: 22px; height: 22px; }
        }

        @media (min-width: 768px) and (max-width: 1199px) {
            .category-grid { grid-template-columns: repeat(3, 1fr); }
            .product-scroll-row .product-card {
                flex: 0 0 calc(33.333% - 12px);
                min-width: calc(33.333% - 12px);
            }
        }

    </style>

    {{-- iOS 26 "Liquid Glass" look (loaded last so it sits on top of the theme) --}}
    <link rel="stylesheet" href="{{ asset('css/liquid-glass.css') }}?v={{ @filemtime(public_path('css/liquid-glass.css')) }}">

    @include('storefront.partials.meta-pixel')

</head>

<body class="@if(request()->routeIs('home')) page-home @elseif(request()->routeIs('category.show')) page-category @elseif(request()->routeIs('product.show')) page-product @endif">

<header class="ig-header">

    <div class="container ig-header-inner">

        <div class="ig-logo">

            <a href="{{ route('home') }}"><img src="{{ media_thumb(setting('logo'), 300) }}" alt="{{ setting('site_name', 'Eternity Gadget') }}" width="150" height="70"></a>

        </div>

        <button type="button" class="mobile-menu-toggle" id="mobile-menu-toggle" aria-label="Open menu">
            <svg viewBox="0 0 24 24"><path d="M3 6h18v2H3V6zm0 5h18v2H3v-2zm0 5h18v2H3v-2z"/></svg>
        </button>

        <nav class="ig-nav-center">

            @if($mainMenu ?? null)
                @include('storefront.partials.header-menu')
            @elseif(isset($layoutCategories))
                <ul class="ig-menu">
                    <li class="@if(request()->routeIs('home')) active @endif"><a href="{{ route('home') }}">{{ __('messages.home') }}</a></li>
                    @foreach($layoutCategories->whereNull('parent_id') as $category)
                        @php $hasChildren = $category->children->where('is_active', true)->isNotEmpty(); @endphp
                        <li class="@if(request()->routeIs('category.show') && request()->route('slug') === $category->slug) active @endif">
                            <a href="{{ route('category.show', $category->slug) }}">{{ $category->name }}@if($hasChildren)<span class="menu-chevron">▾</span>@endif</a>
                            @if($hasChildren)
                                <ul class="ig-submenu">
                                    @foreach($category->children->where('is_active', true) as $child)
                                        <li><a href="{{ route('category.show', $child->slug) }}">{{ $child->name }}</a></li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif

        </nav>

        <div class="header-actions">

            <button type="button" class="header-icon-btn" id="search-toggle" aria-label="{{ __('messages.search') }}">

                <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0016 9.5 6.5 6.5 0 109.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C8.01 14 6 11.99 6 9.5S8.01 5 10.5 5 15 7.01 15 9.5 12.99 14 10.5 14z"/></svg>

            </button>

            @auth
                @php
                    try {
                        $unreadCount = auth()->user()->notifications()->whereNull('read_at')->count();
                        $headerNotifications = auth()->user()->notifications()->latest()->limit(5)->get();
                    } catch (\Throwable $e) {
                        $unreadCount = 0;
                        $headerNotifications = collect();
                    }
                @endphp

                <div class="notification-bell header-icon-btn" id="notification-bell" title="{{ __('messages.notifications') }}">

                    <svg viewBox="0 0 24 24"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>

                    @if($unreadCount > 0)<span class="header-badge">{{ $unreadCount }}</span>@endif

                    <div class="notification-dropdown">

                        <strong>{{ __('messages.notifications') }}</strong>

                        @forelse($headerNotifications as $notification)

                            <div class="mt-2 pb-2 border-bottom">

                                <div>{{ $notification->title }}</div>

                                <small>{{ $notification->body }}</small>

                            </div>

                        @empty

                            <p class="mt-2 mb-0">কোনো নোটিফিকেশন নেই।</p>

                        @endforelse

                    </div>

                </div>

            @endauth

            @guest
                <a href="{{ route('account.login') }}" class="header-icon-btn" aria-label="{{ __('messages.my_account') }}">
                    <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                </a>
            @else
                <a href="{{ route('account') }}" class="header-icon-btn" aria-label="{{ __('messages.my_account') }}">
                    <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                </a>
            @endguest

            <a href="{{ route('wishlist') }}" class="header-icon-btn" aria-label="{{ __('messages.wishlist') }}">

                <svg viewBox="0 0 24 24"><path d="M16.5 3c-1.74 0-3.41.81-4.5 2.09C10.91 3.81 9.24 3 7.5 3 4.42 3 2 5.42 2 8.5c0 3.78 3.4 6.86 8.55 11.54L12 21.35l1.45-1.32C18.6 15.36 22 12.28 22 8.5 22 5.42 19.58 3 16.5 3zm-4.4 15.55l-.1.1-.1-.1C7.14 14.24 4 11.39 4 8.5 4 6.5 5.5 5 7.5 5c1.54 0 3.04.99 3.57 2.36h1.87C13.46 5.99 14.96 5 16.5 5c2 0 3.5 1.5 3.5 3.5 0 2.89-3.14 5.74-7.9 10.05z"/></svg>

                <span class="header-badge" id="wishlist-count-badge">0</span>

            </a>

            <div class="mini-cart header-icon-btn" id="mini-cart">

                <a href="#" id="mini-cart-toggle" class="account-toggle" data-open-cart aria-label="{{ __('messages.cart') }}">

                    <svg viewBox="0 0 24 24"><path d="M18 6h-2c0-2.21-1.79-4-4-4S8 3.79 8 6H6c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm-8 4c0 .55-.45 1-1 1s-1-.45-1-1V8h2v2zm2-4c1.1 0 2 .9 2 2h-4c0-1.1.9-2 2-2zm4 4c0 .55-.45 1-1 1s-1-.45-1-1V8h2v2z"/></svg>

                    <span class="header-badge" id="mini-cart-count-badge">0</span>

                </a>

            </div>

            <button type="button" class="header-icon-btn header-chat-trigger" data-chat-trigger aria-label="Chat">

                <svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H5.17L4 17.17V4h16v12z"/><path d="M7 9h10v2H7zm0-3h10v2H7z" opacity=".5" transform="translate(3 3)"/></svg>

            </button>

        </div>

    </div>

    <div class="search-panel" id="search-panel">

        <div class="container">

            <form class="ig-search" action="{{ route('search') }}" id="search-form" autocomplete="off">

                <input type="search" name="q" id="search-input" value="{{ request('q') }}" placeholder="{{ __('messages.search_placeholder') }}">

                <button type="submit">{{ __('messages.search') }}</button>

                <div class="search-autocomplete" id="search-autocomplete"></div>

            </form>

        </div>

    </div>

</header>



<div class="mobile-nav-overlay" id="mobile-nav-overlay" aria-hidden="true"></div>

<nav class="mobile-nav-drawer" id="mobile-nav-drawer" aria-label="Mobile menu" aria-hidden="true">
    <div class="mobile-nav-header">
        <span>Menu</span>
        <button type="button" class="mobile-nav-close" id="mobile-nav-close" aria-label="Close menu">&times;</button>
    </div>
    <ul class="mobile-nav-list">
        <li><a href="{{ route('home') }}">{{ __('messages.home') }}</a></li>
        @if($mainMenu ?? null)
            @foreach($mainMenu->items as $item)
                @php $children = $item->children->where('is_active', true); @endphp
                <li>
                    @if($children->isNotEmpty())
                        <button type="button" class="mobile-sub-toggle" aria-expanded="false">{{ $item->label }} <span>&#9662;</span></button>
                        <ul class="mobile-sub-list">
                            @if($item->url)
                                <li><a href="{{ $item->url }}">All {{ $item->label }}</a></li>
                            @endif
                            @foreach($children as $child)
                                @php $grandchildren = $child->children->where('is_active', true); @endphp
                                @if($grandchildren->isNotEmpty())
                                    <li>
                                        <button type="button" class="mobile-sub-toggle nested" aria-expanded="false">{{ $child->label }} <span>&#9662;</span></button>
                                        <ul class="mobile-sub-list">
                                            @if($child->url)
                                                <li><a href="{{ $child->url }}">All {{ $child->label }}</a></li>
                                            @endif
                                            @foreach($grandchildren as $grandchild)
                                                <li><a href="{{ $grandchild->url }}">{{ $grandchild->label }}</a></li>
                                            @endforeach
                                        </ul>
                                    </li>
                                @else
                                    <li><a href="{{ $child->url }}">{{ $child->label }}</a></li>
                                @endif
                            @endforeach
                        </ul>
                    @else
                        <a href="{{ $item->url }}">{{ $item->label }}</a>
                    @endif
                </li>
            @endforeach
        @elseif(isset($layoutCategories))
            @foreach($layoutCategories->whereNull('parent_id') as $category)
                @php $children = $category->children->where('is_active', true); @endphp
                <li>
                    @if($children->isNotEmpty())
                        <button type="button" class="mobile-sub-toggle" aria-expanded="false">{{ $category->name }} <span>&#9662;</span></button>
                        <ul class="mobile-sub-list">
                            <li><a href="{{ route('category.show', $category->slug) }}">{{ $category->name }}</a></li>
                            @foreach($children as $child)
                                <li><a href="{{ route('category.show', $child->slug) }}">{{ $child->name }}</a></li>
                            @endforeach
                        </ul>
                    @else
                        <a href="{{ route('category.show', $category->slug) }}">{{ $category->name }}</a>
                    @endif
                </li>
            @endforeach
        @endif
        <li><a href="{{ route('search') }}">{{ __('messages.search') }}</a></li>
        <li><a href="{{ route('wishlist') }}">{{ __('messages.wishlist') }}</a></li>
        <li><a href="#" data-open-cart>{{ __('messages.cart') }}</a></li>
        <li><a href="{{ auth()->check() ? route('account') : route('account.login') }}">{{ __('messages.my_account') }}</a></li>
    </ul>
</nav>



@if(session('success'))

    <div class="container mt-3"><div class="alert alert-success">{{ session('success') }}</div></div>

@endif

@if(session('error'))
    <div class="container mt-3"><div class="alert alert-danger">{{ session('error') }}</div></div>
@endif

@if(isset($errors) && $errors->any())

    <div class="container mt-3">

        <div class="alert alert-danger">

            <ul class="mb-0">

                @foreach($errors->all() as $error)

                    <li>{{ $error }}</li>

                @endforeach

            </ul>

        </div>

    </div>

@endif



<main>

    @yield('content')

</main>



<footer>

    <div class="container">

        <div class="row">

            <div class="col-md-4">

                <img src="{{ media_thumb(setting('footer_logo') ?: setting('logo'), 300) }}" alt="{{ setting('site_name') }}" loading="lazy" style="max-width: 180px; height: auto; margin-bottom: 16px;">

                <p class="footer-about">{{ setting('footer_about', 'Welcome to our online store! We are a team of passionate individuals who are dedicated to providing our customers with high-quality products and excellent service.') }}</p>
                <div class="footer-contact">
                    @if(setting('phone'))<div>📞 <a href="tel:{{ preg_replace('/[^0-9+]/', '', setting('phone')) }}">{{ setting('phone') }}</a></div>@endif
                    @if(whatsapp_link())<div>💬 <a href="{{ whatsapp_link() }}" target="_blank" rel="noopener">WhatsApp-এ মেসেজ করুন</a></div>@endif
                </div>

                <div class="footer-social">

                    @if(setting('facebook_url'))

                        <a href="{{ setting('facebook_url') }}" target="_blank" rel="noopener" aria-label="Facebook">

                            <svg viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>

                        </a>

                    @endif

                    @if(setting('instagram_url'))

                        <a href="{{ setting('instagram_url') }}" target="_blank" rel="noopener" aria-label="Instagram">

                            <svg viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>

                        </a>

                    @endif

                    @if(setting('youtube_url'))

                        <a href="{{ setting('youtube_url') }}" target="_blank" rel="noopener" aria-label="YouTube">

                            <svg viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>

                        </a>

                    @endif

                </div>

            </div>

            <div class="col-md-4 footer-accordion-col">

                <h4>{{ __('messages.information') }}</h4>

                <button type="button" class="footer-accordion-toggle" aria-expanded="false">
                    <h4>{{ __('messages.information') }}</h4>
                    <span class="footer-accordion-icon" aria-hidden="true">+</span>
                </button>

                <div class="footer-accordion-panel">

                <ul class="list-unstyled">

                    @foreach($footerPages ?? [] as $page)

                        <li><a href="{{ route('page.show', $page->slug) }}">{{ $page->title }}</a></li>

                    @endforeach

                </ul>

                </div>

            </div>

            <div class="col-md-4 footer-accordion-col">

                <h4>Customer Service</h4>

                <button type="button" class="footer-accordion-toggle" aria-expanded="false">
                    <h4>Customer Service</h4>
                    <span class="footer-accordion-icon" aria-hidden="true">+</span>
                </button>

                <div class="footer-accordion-panel">

                <ul class="list-unstyled">

                    <li><a href="{{ route('wishlist') }}">Wish List</a></li>

                    <li><a href="{{ route('account.guest-tracking') }}">Order Tracking</a></li>

                    <li><a href="{{ route('account.contact') }}">Contact Us</a></li>

                    <li><a href="{{ route('account.orders') }}">Order History</a></li>

                </ul>

                </div>

            </div>

        </div>

        <hr style="border-color: #e2e2e2; margin-top: 30px;">

        <p class="footer-copyright mb-0">{{ setting('copyright_text', setting('site_name', 'Eternity Gadget') . ' © ' . date('Y')) }}</p>

    </div>

</footer>



@php
    $contactButtons = collect($layoutContactButtons ?? []);
    $chatPhoneBtn = $contactButtons->first(fn ($b) => strtolower($b->type ?? '') === 'telephone');
    $chatMessengerBtn = $contactButtons->first(fn ($b) => in_array(strtolower($b->type ?? ''), ['messenger', 'facebook'], true));
    $chatWhatsappBtn = $contactButtons->first(fn ($b) => strtolower($b->type ?? '') === 'whatsapp');

    $chatPhoneRaw = $chatPhoneBtn?->url ?: setting('phone', '01867683114');
    $chatPhoneDigits = preg_replace('/\D+/', '', (string) $chatPhoneRaw);
    if (str_starts_with((string) $chatPhoneRaw, 'tel:')) {
        $chatPhoneUrl = $chatPhoneRaw;
    } else {
        $chatPhoneUrl = 'tel:' . ($chatPhoneDigits ?: preg_replace('/\s+/', '', (string) setting('phone', '01867683114')));
    }

    $chatMessenger = $chatMessengerBtn?->url ?: setting('facebook_url');

    // The number saved in Admin → Shop Settings wins; older contact-button / phone settings are the fallback.
    $chatWhatsapp = setting('whatsapp_number') ? whatsapp_link() : $chatWhatsappBtn?->url;
    if (! $chatWhatsapp) {
        $waDigits = preg_replace('/\D+/', '', (string) setting('phone', '8801867683114'));
        if ($waDigits && ! str_starts_with($waDigits, '880') && strlen($waDigits) === 11 && str_starts_with($waDigits, '0')) {
            $waDigits = '88' . $waDigits;
        }
        $chatWhatsapp = setting('whatsapp_url', $waDigits ? 'https://wa.me/' . $waDigits : null);
    }
    if ($chatWhatsapp && ! str_starts_with($chatWhatsapp, 'http') && ! str_starts_with($chatWhatsapp, 'whatsapp:')) {
        $waDigits = preg_replace('/\D+/', '', $chatWhatsapp);
        $chatWhatsapp = $waDigits ? 'https://wa.me/' . $waDigits : null;
    }
@endphp

<div class="chat-widget" id="chat-widget">

    <div class="chat-popup" id="chat-popup">

        @if($chatPhoneUrl)
            <a href="{{ $chatPhoneUrl }}" class="chat-popup-link phone" aria-label="Call us">
                <svg viewBox="0 0 24 24"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
            </a>
        @endif

        @if($chatMessenger)
            <a href="{{ $chatMessenger }}" class="chat-popup-link facebook" target="_blank" rel="noopener" aria-label="Messenger">
                <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.03 2 10.72c0 2.65 1.31 5.01 3.36 6.56-.09.79-.32 2.88-.37 3.35-.05.42.15.58.35.63.2.05.44-.09.69-.34.24-.24 2.82-2.47 3.96-3.47.64.1 1.3.15 2.01.15 5.52 0 10-4.03 10-8.72S17.52 2 12 2z"/></svg>
            </a>
        @endif

        @if($chatWhatsapp)
            <a href="{{ $chatWhatsapp }}" class="chat-popup-link whatsapp" target="_blank" rel="noopener" aria-label="WhatsApp">
                <svg viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            </a>
        @endif

    </div>

    <button type="button" class="chat-trigger-btn" data-chat-trigger aria-label="Chat">

        <svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H5.17L4 17.17V4h16v12z"/><path d="M7 9h10v2H7zm0-3h10v2H7z" opacity=".6" transform="translate(3 3)"/></svg>

    </button>

</div>



<button type="button" class="floating-cart-widget" id="floating-cart-widget" data-open-cart aria-label="Open shopping cart">

    <div class="floating-cart-top">

        <span class="fc-icon"><svg viewBox="0 0 24 24"><path d="M18 6h-2c0-2.21-1.79-4-4-4S8 3.79 8 6H6c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm-8 4c0 .55-.45 1-1 1s-1-.45-1-1V8h2v2zm2-4c1.1 0 2 .9 2 2h-4c0-1.1.9-2 2-2zm4 4c0 .55-.45 1-1 1s-1-.45-1-1V8h2v2z"/></svg></span>

        <span class="fc-items"><span id="floating-cart-count">0</span> টি</span>

    </div>

    <div class="floating-cart-bottom" id="floating-cart-total">৳0</div>

</button>



@php
    $mobileShopSlug = optional($layoutCategories->first() ?? null)->slug ?? 'iphone-case';
    $mobileShopActive = request()->routeIs('category.show');
    $mobileAccountUrl = auth()->check() ? route('account') : route('account.login');
    $mobileAccountActive = request()->routeIs('account*');
@endphp

<nav class="mobile-bottom-nav" aria-label="Mobile navigation">
    <div class="mobile-bottom-nav-inner">
        <a href="{{ route('home') }}" class="mobile-nav-item @if(request()->routeIs('home')) active @endif">
            <svg viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
            <span>Home</span>
        </a>
        <a href="{{ route('category.show', $mobileShopSlug) }}" class="mobile-nav-item @if($mobileShopActive) active @endif">
            <svg viewBox="0 0 24 24"><path d="M20 4H4v2h16V4zm1 10v-2l-1-5H4l-1 5v2h1v6h10v-6h4v6h2v-6h1zm-9 4H6v-4h6v4z"/></svg>
            <span>Shop</span>
        </a>
        <button type="button" class="mobile-nav-item" id="mobile-nav-categories" aria-label="Categories">
            <svg viewBox="0 0 24 24"><path d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm4 4h14v-2H7v2zm0 4h14v-2H7v2zM7 7v2h14V7H7z"/></svg>
            <span>Categories</span>
        </button>
        <button type="button" class="mobile-nav-item" id="mobile-nav-search" aria-label="Search">
            <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0016 9.5 6.5 6.5 0 109.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C8.01 14 6 11.99 6 9.5S8.01 5 10.5 5 15 7.01 15 9.5 12.99 14 10.5 14z"/></svg>
            <span>Search</span>
        </button>
        <a href="{{ $mobileAccountUrl }}" class="mobile-nav-item @if($mobileAccountActive) active @endif">
            <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
            <span>Account</span>
        </a>
    </div>
</nav>



@include('storefront.partials.cart-sidebar')

@include('storefront.partials.cart-toast')



<script>

(function () {

    // Remove the old service worker and its cache (it served outdated CSS/JS to returning visitors)
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.getRegistrations().then((regs) => regs.forEach((r) => r.unregister())).catch(() => {});
    }
    if (window.caches && caches.keys) {
        caches.keys().then((keys) => keys.filter((k) => k.indexOf('ig-store') === 0).forEach((k) => caches.delete(k))).catch(() => {});
    }



    const miniCartCountBadge = document.getElementById('mini-cart-count-badge');

    const wishlistCountBadge = document.getElementById('wishlist-count-badge');

    const floatingCartCount = document.getElementById('floating-cart-count');

    const floatingCartTotal = document.getElementById('floating-cart-total');



    function updateCountBadge(el, count) {

        if (!el) return;

        el.textContent = count;

    }



    function syncCartBadges(data) {
        const count = data?.count ?? data?.cart_count ?? 0;
        const subtotal = data?.subtotal ?? data?.cart_subtotal ?? '৳0';
        updateCountBadge(miniCartCountBadge, count);
        updateCountBadge(wishlistCountBadge, data?.wishlist_count ?? 0);
        if (floatingCartCount) floatingCartCount.textContent = count;
        if (floatingCartTotal) floatingCartTotal.textContent = subtotal;
    }



    const searchToggle = document.getElementById('search-toggle');

    const searchPanel = document.getElementById('search-panel');

    const mobileNavSearch = document.getElementById('mobile-nav-search');

    const searchInput = document.getElementById('search-input');

    searchToggle?.addEventListener('click', () => {

        searchPanel?.classList.toggle('open');

        if (searchPanel?.classList.contains('open')) {

            searchInput?.focus();

        }

    });

    document.addEventListener('click', (e) => {

        if (searchPanel?.classList.contains('open')
            && !searchPanel.contains(e.target)
            && !searchToggle?.contains(e.target)
            && !mobileNavSearch?.contains(e.target)) {

            searchPanel.classList.remove('open');

        }

    });



    const chatPopup = document.getElementById('chat-popup');

    function toggleChat() { chatPopup?.classList.toggle('open'); }

    document.querySelectorAll('[data-chat-trigger]').forEach((btn) => {

        btn.addEventListener('click', (e) => { e.stopPropagation(); toggleChat(); });

    });

    document.addEventListener('click', (e) => {

        if (!document.getElementById('chat-widget')?.contains(e.target)) {

            chatPopup?.classList.remove('open');

        }

    });



    async function refreshHeaderCounts() {

        try {

            const res = await fetch('/api/header-counts');

            const data = await res.json();

            syncCartBadges(data);

        } catch (e) {}

    }

    document.addEventListener('cart:updated', (e) => syncCartBadges(e.detail));

    refreshHeaderCounts();

    setInterval(refreshHeaderCounts, 30000);



    const mobileNavOverlay = document.getElementById('mobile-nav-overlay');

    const mobileNavDrawer = document.getElementById('mobile-nav-drawer');

    function openMobileNav() {
        mobileNavOverlay?.classList.add('open');
        mobileNavDrawer?.classList.add('open');
        document.body.classList.add('mobile-nav-open');
        mobileNavOverlay?.setAttribute('aria-hidden', 'false');
        mobileNavDrawer?.setAttribute('aria-hidden', 'false');
    }

    function closeMobileNav() {
        mobileNavOverlay?.classList.remove('open');
        mobileNavDrawer?.classList.remove('open');
        document.body.classList.remove('mobile-nav-open');
        mobileNavOverlay?.setAttribute('aria-hidden', 'true');
        mobileNavDrawer?.setAttribute('aria-hidden', 'true');
    }

    document.getElementById('mobile-menu-toggle')?.addEventListener('click', openMobileNav);
    document.getElementById('mobile-nav-close')?.addEventListener('click', closeMobileNav);
    mobileNavOverlay?.addEventListener('click', closeMobileNav);

    document.querySelectorAll('.mobile-sub-toggle').forEach((btn) => {
        btn.addEventListener('click', () => {
            const sub = btn.nextElementSibling;
            const open = sub?.classList.toggle('open');
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    });

    document.querySelectorAll('.mobile-nav-list a[data-open-cart]').forEach((link) => {
        link.addEventListener('click', () => closeMobileNav());
    });

    document.getElementById('mobile-nav-categories')?.addEventListener('click', openMobileNav);

    document.getElementById('mobile-nav-search')?.addEventListener('click', (e) => {
        e.stopPropagation();
        searchPanel?.classList.add('open');
        searchInput?.focus();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    document.querySelectorAll('.footer-accordion-toggle').forEach((btn) => {
        btn.addEventListener('click', () => {
            const col = btn.closest('.footer-accordion-col');
            const open = col?.classList.toggle('open');
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    });



    const notificationBell = document.getElementById('notification-bell');

    const searchAutocomplete = document.getElementById('search-autocomplete');

    let searchTimer;



    searchInput?.addEventListener('input', () => {

        clearTimeout(searchTimer);

        const q = searchInput.value.trim();

        if (q.length < 2) { searchAutocomplete.style.display = 'none'; return; }

        searchTimer = setTimeout(async () => {

            try {

                const res = await fetch('/api/search/autocomplete?q=' + encodeURIComponent(q));

                const data = await res.json();

                if (!data.results?.length) { searchAutocomplete.style.display = 'none'; return; }

                searchAutocomplete.innerHTML = data.results.map(r => `

                    <a href="${r.url}"><img src="${r.image}" alt=""><span>${r.name}<br><small>${r.price}</small></span></a>

                `).join('');

                searchAutocomplete.style.display = 'block';

            } catch (e) { searchAutocomplete.style.display = 'none'; }

        }, 250);

    });



    document.addEventListener('click', (e) => {

        if (!document.getElementById('search-form')?.contains(e.target)) {

            searchAutocomplete.style.display = 'none';

        }

    });



    notificationBell?.addEventListener('click', (e) => {

        e.stopPropagation();

        notificationBell.classList.toggle('open');

    });

    document.addEventListener('click', () => notificationBell?.classList.remove('open'));



    const siteHeader = document.querySelector('.ig-header');

    if (siteHeader) {

        const setHeaderHeight = () => {

            document.documentElement.style.setProperty('--header-height', siteHeader.offsetHeight + 'px');

            document.body.classList.add('has-fixed-header');

        };

        setHeaderHeight();

        window.addEventListener('resize', setHeaderHeight, { passive: true });



        let navbarIdleTimer = null;



        function showNavbar() {

            siteHeader.classList.remove('is-hidden');

        }



        function hideNavbar() {

            if (window.scrollY > 24) {

                siteHeader.classList.add('is-hidden');

            } else {

                showNavbar();

            }

        }



        function onScrollStop() {

            clearTimeout(navbarIdleTimer);

            showNavbar();

        }



        function onScrollActive() {

            hideNavbar();

            clearTimeout(navbarIdleTimer);

            navbarIdleTimer = setTimeout(onScrollStop, 350);

        }



        window.addEventListener('scroll', onScrollActive, { passive: true });



        if ('onscrollend' in window) {

            window.addEventListener('scrollend', onScrollStop, { passive: true });

        }

    }



    document.querySelectorAll('.ig-phone-wrap[data-phone-wrap]').forEach((wrap) => {
        const localInput = wrap.querySelector('.ig-phone-local');
        const hiddenInput = wrap.querySelector('.ig-phone-hidden');
        const feedbackId = wrap.dataset.phoneFeedback;
        const feedbackEl = feedbackId ? document.getElementById(feedbackId) : null;
        let phoneTimer;

        if (!localInput || !hiddenInput) {
            return;
        }

        const syncPhone = () => {
            localInput.value = localInput.value.replace(/\D/g, '').slice(0, 11);
            hiddenInput.value = localInput.value.length === 11 ? '+88' + localInput.value : '';
        };

        const validateLocal = () => {
            if (!localInput.required) {
                localInput.setCustomValidity('');
                return true;
            }

            if (localInput.value.length !== 11) {
                localInput.setCustomValidity('সঠিক ১১ সংখ্যার মোবাইল নম্বর লিখুন (যেমন 01XXXXXXXXX)।');
                return false;
            }

            localInput.setCustomValidity('');
            return true;
        };

        localInput.addEventListener('input', () => {
            syncPhone();
            validateLocal();

            clearTimeout(phoneTimer);
            phoneTimer = setTimeout(async () => {
                const phone = hiddenInput.value.trim();
                if (phone.length < 14) {
                    if (feedbackEl) {
                        feedbackEl.textContent = '';
                    }
                    return;
                }

                try {
                    const res = await fetch('/api/check-phone?phone=' + encodeURIComponent(phone));
                    const data = await res.json();

                    if (feedbackEl) {
                        if (!data.valid) {
                            feedbackEl.textContent = data.message || 'মোবাইল নম্বরটি সঠিক নয়';
                            feedbackEl.style.color = '#c0392b';
                        } else if (data.sharing_warning) {
                            feedbackEl.textContent = 'এই নম্বর দিয়ে আগে অর্ডার করা হয়েছে।';
                            feedbackEl.style.color = '#f5924c';
                        } else if (!data.available) {
                            feedbackEl.textContent = 'এই নম্বর দিয়ে আগেই অ্যাকাউন্ট খোলা হয়েছে।';
                            feedbackEl.style.color = '#3498db';
                        } else {
                            feedbackEl.textContent = '';
                        }
                    } else if (!data.valid) {
                        localInput.setCustomValidity(data.message || 'মোবাইল নম্বরটি সঠিক নয়');
                    } else {
                        localInput.setCustomValidity('');
                    }
                } catch (e) {
                    if (feedbackEl) {
                        feedbackEl.textContent = '';
                    }
                }
            }, 400);
        });

        wrap.closest('form')?.addEventListener('submit', (event) => {
            syncPhone();
            if (!validateLocal()) {
                event.preventDefault();
                localInput.reportValidity();
            }
        });

        syncPhone();
    });

})();

</script>

@stack('scripts')

<script>
// Browser form warnings in Bangla (fields that already set their own message are left alone)
(function () {
    function bnMessage(el) {
        const v = el.validity;
        if (v.valueMissing) {
            if (el.type === 'radio') return 'অনুগ্রহ করে একটি অপশন বেছে নিন।';
            if (el.type === 'checkbox') return 'চালিয়ে যেতে এই বক্সে টিক দিন।';
            if (el.tagName === 'SELECT') return 'অনুগ্রহ করে একটি অপশন বেছে নিন।';
            return 'এই ঘরটি পূরণ করুন।';
        }
        if (v.typeMismatch && el.type === 'email') return 'সঠিক ইমেইল ঠিকানা লিখুন।';
        if (v.tooShort) return 'কমপক্ষে ' + el.minLength + ' অক্ষর লিখুন।';
        if (v.rangeUnderflow) return 'সর্বনিম্ন ' + el.min + ' দিতে হবে।';
        if (v.rangeOverflow) return 'সর্বোচ্চ ' + el.max + ' দেওয়া যাবে।';
        if (v.patternMismatch || v.badInput) return 'সঠিক তথ্য লিখুন।';
        return '';
    }
    document.addEventListener('invalid', function (e) {
        const el = e.target;
        if (el.dataset.bnMsg === undefined && el.validationMessage && !el.validity.customError) {
            el.setCustomValidity(bnMessage(el));
            el.dataset.bnMsg = '1';
        }
    }, true);
    function clear(e) {
        const el = e.target;
        if (el && el.dataset && el.dataset.bnMsg === '1') {
            el.setCustomValidity('');
            delete el.dataset.bnMsg;
            if (el.type === 'radio' && el.name) {
                document.querySelectorAll('input[type=radio][name="' + el.name + '"]').forEach(function (r) {
                    if (r.dataset.bnMsg === '1') { r.setCustomValidity(''); delete r.dataset.bnMsg; }
                });
            }
        }
    }
    document.addEventListener('input', clear, true);
    document.addEventListener('change', clear, true);
})();
</script>
</body>

</html>

