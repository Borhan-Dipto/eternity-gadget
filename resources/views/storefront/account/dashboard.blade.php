@extends('storefront.account.layout', ['activeNav' => 'account'])

@section('title', 'My Account')
@section('account_breadcrumb', 'Account')

@section('account_content')
@auth
    @if(auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
        <div class="account-box" style="margin-bottom:20px;border-color:#f0c040;">
            <p class="mb-0">আপনার ইমেইল যাচাই করা হয়নি। <a href="{{ route('verification.notice') }}">আবার লিংক পাঠান</a></p>
        </div>
    @endif

    @php $rewardsOn = setting('rewards_enabled', '1') === '1'; @endphp
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:20px;">
        <h1 style="font-size:20px;margin:0;">স্বাগতম, {{ auth()->user()->name }}</h1>
        <form method="post" action="{{ route('account.logout') }}" style="margin:0;">
            @csrf
            <button type="submit" style="border:1px solid #ddd;background:#fff;border-radius:8px;padding:7px 14px;font-size:13px;cursor:pointer;">লগআউট</button>
        </form>
    </div>
    <div class="account-section">
        <div class="account-section-header">আমার অর্ডার</div>
        <div class="account-section-body">
            <a href="{{ route('account.orders') }}" class="account-tile">
                <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
                <span>অর্ডারের তালিকা ও অবস্থা দেখুন</span>
            </a>
            @if($rewardsOn)
            <a href="{{ route('account.rewards') }}" class="account-tile">
                <svg viewBox="0 0 24 24"><path d="M20 6h-2.18c.11-.31.18-.65.18-1 0-1.66-1.34-3-3-3-1.05 0-1.96.54-2.5 1.35l-.5.67-.5-.68C10.96 2.54 10.05 2 9 2 7.34 2 6 3.34 6 5c0 .35.07.69.18 1H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-5-2c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zM9 4c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm11 15H4v-2h16v2zm0-5H4V8h5.08L7 10.83 8.62 12 11 8.76l1-1.36 1 1.36L15.38 12 17 10.83 14.92 8H20v6z"/></svg>
                <span>রিওয়ার্ড পয়েন্ট: <strong>{{ number_format(app(\App\Services\RewardService::class)->balance(auth()->user())) }}</strong></span>
            </a>
            @endif
            <a href="{{ route('wishlist') }}" class="account-tile">
                <svg viewBox="0 0 24 24"><path d="M16.5 3c-1.74 0-3.41.81-4.5 2.09C10.91 3.81 9.24 3 7.5 3 4.42 3 2 5.42 2 8.5c0 3.78 3.4 6.86 8.55 11.54L12 21.35l1.45-1.32C18.6 15.36 22 12.28 22 8.5 22 5.42 19.58 3 16.5 3z"/></svg>
                <span>উইশলিস্ট</span>
            </a>
        </div>
    </div>

    <div class="account-section">
        <div class="account-section-header">আমার তথ্য</div>
        <div class="account-section-body">
            <a href="{{ route('account.profile') }}" class="account-tile">
                <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                <span>নাম, ইমেইল ও মোবাইল নম্বর</span>
            </a>
            <a href="{{ route('account.addresses') }}" class="account-tile">
                <svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 010-5 2.5 2.5 0 000 5z"/></svg>
                <span>ডেলিভারির ঠিকানা</span>
            </a>
            <a href="{{ route('account.password') }}" class="account-tile">
                <svg viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1s3.1 1.39 3.1 3.1v2z"/></svg>
                <span>পাসওয়ার্ড পরিবর্তন</span>
            </a>
            <a href="{{ route('account.notifications') }}" class="account-tile">
                <svg viewBox="0 0 24 24"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>
                <span>নোটিফিকেশন</span>
            </a>
            <a href="{{ route('account.newsletter') }}" class="account-tile">
                <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                <span>ইমেইলে অফার পাওয়া চালু/বন্ধ</span>
            </a>
        </div>
    </div>
@endauth
@endsection
