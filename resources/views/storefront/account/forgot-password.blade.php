@extends('storefront.account.layout', ['activeNav' => 'forgot-password'])

@section('title', 'Forgot Password')
@section('account_breadcrumb', 'Forgotten Password')

@section('account_content')
<div class="account-box">
    <h1>পাসওয়ার্ড ভুলে গেছেন?</h1>
    <p class="lead">আপনার অ্যাকাউন্টের ইমেইলটি লিখুন। নতুন পাসওয়ার্ড সেট করার লিংক ইমেইলে পাঠানো হবে।</p>

    <form method="post" action="{{ route('account.password.email') }}">
        @csrf

        <div class="account-field">
            <label for="email"><span class="req">*</span> আপনার ইমেইল</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="আপনার ইমেইল" required>
        </div>

        <div class="account-actions">
            <a href="{{ route('account.login') }}" class="account-btn">ফিরে যান</a>
            <button class="account-btn" type="submit">লিংক পাঠান</button>
        </div>
    </form>
</div>
@endsection
