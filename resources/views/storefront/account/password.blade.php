@extends('storefront.account.layout', ['activeNav' => 'password'])

@section('title', 'Change Password')
@section('account_breadcrumb', 'Password')

@section('account_content')
<div class="account-box">
    <h1>পাসওয়ার্ড পরিবর্তন</h1>

    <form method="post" action="{{ route('account.password.change') }}">
        @csrf

        <div class="account-detail-row">
            <label for="password"><span class="req">*</span> নতুন পাসওয়ার্ড</label>
            <input type="password" id="password" name="password" placeholder="নতুন পাসওয়ার্ড" required>
        </div>

        <div class="account-detail-row">
            <label for="password_confirmation"><span class="req">*</span> পাসওয়ার্ড আবার লিখুন</label>
            <input type="password" id="password_confirmation" name="password_confirmation" placeholder="পাসওয়ার্ড আবার লিখুন" required>
        </div>

        <div class="account-actions">
            <a href="{{ route('account') }}" class="account-btn">ফিরে যান</a>
            <button class="account-btn" type="submit">সেভ করুন</button>
        </div>
    </form>
</div>
@endsection
