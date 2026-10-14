@extends('storefront.account.layout', ['activeNav' => 'account'])

@section('title', 'My Account')
@section('account_breadcrumb', 'Account')

@section('account_content')
@auth
    @if(auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
        <div class="account-box" style="margin-bottom:20px;border-color:#f0c040;">
            <p class="mb-0">Please verify your email address. <a href="{{ route('verification.notice') }}">Resend verification email</a></p>
        </div>
    @endif

    <div class="account-section">
        <div class="account-section-header">My Account</div>
        <div class="account-section-body">
            <a href="{{ route('account.profile') }}" class="account-tile">
                <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                <span>Edit your account information</span>
            </a>
            <a href="{{ \Illuminate\Support\Facades\Route::has('account.password') ? route('account.password') : route('account.profile') }}" class="account-tile">
                <svg viewBox="0 0 24 24"><path d="M12.65 10A5.99 5.99 0 007 6c-3.31 0-6 2.69-6 6s2.69 6 6 6h5.65c.76 0 1.35-.54 1.35-1.3 0-.79-.59-1.3-1.35-1.3H7c-2.21 0-4-1.79-4-4s1.79-4 4-4c1.66 0 3.14.84 4 2.13V9.27c-.6-.34-1.28-.52-2-.52-2.21 0-4 1.79-4 4s1.79 4 4 4z"/></svg>
                <span>Change your password</span>
            </a>
            <a href="{{ route('account.addresses') }}" class="account-tile">
                <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                <span>Modify your address book entries</span>
            </a>
            <a href="{{ route('wishlist') }}" class="account-tile">
                <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                <span>Modify your wish list</span>
            </a>
        </div>
    </div>

    <div class="account-section">
        <div class="account-section-header">My Orders</div>
        <div class="account-section-body">
            <a href="{{ route('account.orders') }}" class="account-tile">
                <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
                <span>View your order history</span>
            </a>
            <a href="{{ route('account.downloads') }}" class="account-tile">
                <svg viewBox="0 0 24 24"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
                <span>Downloads</span>
            </a>
            <a href="{{ route('account.rewards') }}" class="account-tile">
                <svg viewBox="0 0 24 24"><path d="M19 5h-2V3H7v2H5c-1.1 0-2 .9-2 2v1c0 2.55 1.92 4.63 4.39 4.94.63 1.5 1.98 2.63 3.61 2.96V19H7v2h10v-2h-4v-3.1c1.63-.33 2.98-1.46 3.61-2.96C19.08 12.63 21 10.55 21 8V7c0-1.1-.9-2-2-2z"/></svg>
                <span>Your Reward Points</span>
            </a>
            <a href="{{ route('account.transactions') }}" class="account-tile">
                <svg viewBox="0 0 24 24"><path d="M6.99 11L3 15l3.99 4v-3H14v-2H6.99v-3zM21 9l-3.99-4v3H10v2h7.01v3L21 9z"/></svg>
                <span>Your Transactions</span>
            </a>
        </div>
    </div>

    <div class="account-section">
        <div class="account-section-header">Newsletter</div>
        <div class="account-section-body" style="grid-template-columns:1fr;">
            <a href="{{ route('account.newsletter') }}" class="account-tile" style="border-right:0;">
                <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                <span>Subscribe / unsubscribe to newsletter</span>
            </a>
        </div>
    </div>
@endauth
@endsection
