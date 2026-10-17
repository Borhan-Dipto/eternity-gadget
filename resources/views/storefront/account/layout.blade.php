@extends('layouts.storefront')

@section('content')
<style>
    .account-page { padding: 28px 0 50px; }

    @media (max-width: 767px) {
        .account-page {
            padding-top: 20px;
            padding-bottom: 44px;
        }

        .account-breadcrumb {
            font-size: 15px;
            margin-bottom: 22px;
        }

        .account-sidebar-item {
            font-size: 16px;
            padding: 14px 18px;
        }
    }

    .account-breadcrumb {
        text-align: center;
        margin-bottom: 28px;
        font-size: 14px;
        color: #111;
    }

    .account-breadcrumb a { color: #111; text-decoration: none; }

    .account-breadcrumb a:hover { color: var(--link-hover-color, #83B735); }

    .account-breadcrumb .current { color: #c0392b; }

    .account-breadcrumb svg { width: 14px; height: 14px; vertical-align: -2px; fill: currentColor; }

    .account-page-row {
        display: flex;
        gap: 28px;
        align-items: flex-start;
    }

    .account-main { flex: 1 1 auto; min-width: 0; }

    .account-sidebar {
        flex: 0 0 260px;
        border: 1px solid #ddd;
        background: #fff;
    }

    .account-sidebar-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 13px 16px;
        border-bottom: 1px solid #ddd;
        color: #111;
        text-decoration: none;
        font-size: 15px;
        transition: background .15s, color .15s;
    }

    .account-sidebar-item:last-child { border-bottom: 0; }

    .account-sidebar-item svg { width: 18px; height: 18px; fill: currentColor; flex-shrink: 0; }

    .account-sidebar-item:hover { background: #f8f8f8; color: #111; }

    .account-sidebar-item.active { background: #111; color: #fff; }

    .account-sidebar-item.active:hover { background: #111; color: #fff; }

    .account-box {
        border: 1px solid #ddd;
        background: #fff;
        padding: 28px 32px;
    }

    .account-box h1, .account-box h2 {
        font-size: 22px;
        font-weight: 400;
        margin: 0 0 20px;
    }

    .account-box p.lead { font-size: 14px; color: #444; margin-bottom: 24px; }

    .account-form-title { text-align: center; font-size: 22px; margin-bottom: 24px; }

    .account-field { margin-bottom: 18px; }

    .account-field label {
        display: block;
        text-align: center;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .5px;
        margin-bottom: 8px;
        text-transform: uppercase;
    }

    .account-field label .req { color: #c0392b; }

    .account-field input[type="text"],
    .account-field input[type="email"],
    .account-field input[type="password"],
    .account-field input[type="tel"],
    .account-field select,
    .account-field textarea {
        width: 100%;
        border: 1px solid #ccc;
        padding: 10px 14px;
        font-size: 14px;
        color: #111;
        background: #fff;
    }

    .account-field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

    .account-form-links {
        display: flex;
        justify-content: space-between;
        margin: 16px 0 24px;
        font-size: 14px;
    }

    .account-form-links a { color: #111; }

    .account-form-links a:hover { color: var(--link-hover-color); }

    .account-btn {
        display: inline-block;
        background: #111;
        color: #fff;
        border: 0;
        padding: 12px 28px;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: .5px;
        text-transform: uppercase;
        cursor: pointer;
        text-decoration: none;
        text-align: center;
    }

    .account-btn:hover { background: #333; color: #fff; }

    .account-btn-block { display: block; width: 100%; }

    .account-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 28px;
        gap: 12px;
        flex-wrap: wrap;
    }

    .account-section { margin-bottom: 28px; }

    .account-section-header {
        background: #f0f0f0;
        border: 1px solid #ddd;
        border-bottom: 0;
        padding: 10px 16px;
        font-size: 15px;
        font-weight: 400;
    }

    .account-section-body {
        border: 1px solid #ddd;
        display: grid;
        grid-template-columns: repeat(4, 1fr);
    }

    .account-tile {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 28px 16px;
        text-align: center;
        color: #111;
        text-decoration: none;
        border-right: 1px solid #ddd;
        border-bottom: 1px solid #ddd;
        min-height: 130px;
        transition: background .15s;
    }

    .account-tile:nth-child(4n) { border-right: 0; }

    .account-tile svg { width: 36px; height: 36px; fill: #111; margin-bottom: 12px; }

    .account-tile span { font-size: 13px; line-height: 1.35; }

    .account-tile:hover { background: #fafafa; color: #c0392b; }

    .account-tile:hover svg { fill: #c0392b; }

    .account-detail-row {
        display: grid;
        grid-template-columns: 180px 1fr;
        gap: 16px;
        padding: 14px 0;
        border-bottom: 1px solid #eee;
        align-items: center;
    }

    .account-detail-row label {
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .account-detail-row label .req { color: #c0392b; }

    .account-empty-msg { font-size: 14px; margin-bottom: 20px; }

    .account-section-title { font-size: 16px; font-weight: 700; margin: 24px 0 12px; text-transform: uppercase; letter-spacing: .3px; }

    @media (max-width: 991px) {
        .account-page-row { flex-direction: column; } /* page content first, menu below it */
        .account-main { width: 100%; }
        .account-page-row--dashboard .account-sidebar { display: none; } /* dashboard tiles already are the menu */
        .account-page-row--login,
        .account-page-row--register {
            flex-direction: column;
        }

        .account-sidebar { flex: 1 1 auto; width: 100%; }
        .account-section-body { grid-template-columns: repeat(2, 1fr); }
        .account-tile:nth-child(4n) { border-right: 1px solid #ddd; }
        .account-tile:nth-child(2n) { border-right: 0; }
    }

    @media (max-width: 575px) {
        .account-field-row { grid-template-columns: 1fr; }
        .account-section-body { grid-template-columns: repeat(2, 1fr); }
        .account-tile { min-height: 110px; padding: 20px 10px; }
        .account-tile svg { width: 30px; height: 30px; margin-bottom: 8px; }
        .account-detail-row { grid-template-columns: 1fr; gap: 6px; }
        .account-box { padding: 20px 16px; }
    }

    .account-page-row--register .account-main .account-box:not(.reg-page) {
        border: 0;
        padding: 0;
        background: transparent;
    }

    .account-page-row--login .account-main {
        display: flex;
        justify-content: center;
        width: 100%;
    }

    @media (max-width: 991px) {
        .account-page-row--register .account-sidebar {
            display: none;
        }
    }
</style>

<div class="container account-page">
    <nav class="account-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('home') }}" aria-label="Home">
            <svg viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
        </a>
        &rsaquo;
        <a href="{{ auth()->check() ? route('account') : route('account.login') }}">Account</a>
        @hasSection('account_breadcrumb')
            &rsaquo; <span class="current">@yield('account_breadcrumb')</span>
        @endif
    </nav>

    <div class="account-page-row @yield('account_page_row_class') @if(($activeNav ?? '') === 'account') account-page-row--dashboard @endif">
        <div class="account-main">
            @yield('account_content')
        </div>
        @include('storefront.account.sidebar', ['activeNav' => $activeNav ?? ''])
    </div>
</div>
@endsection
