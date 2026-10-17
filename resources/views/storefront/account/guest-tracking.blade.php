@extends('storefront.account.layout', ['activeNav' => 'guest-tracking'])

@section('title', 'Track Order')
@section('account_breadcrumb', 'Track Order')

@section('account_content')
<div class="account-box">
    <h1>অর্ডার ট্র্যাক করুন</h1>
    <p style="color:#666;font-size:14px;margin-bottom:18px;">অর্ডার নম্বর (যেমন IG-261017-ABC123) আর যে মোবাইল নম্বর দিয়ে অর্ডার করেছেন সেটি লিখুন।</p>

    <form method="post" action="{{ route('account.guest-tracking.lookup') }}">
        @csrf
        <div class="account-field">
            <label for="order_number"><span class="req">*</span> অর্ডার নম্বর</label>
            <input type="text" id="order_number" name="order_number" value="{{ old('order_number') }}" placeholder="IG-XXXXXX-XXXXXX" required autocapitalize="characters">
        </div>
        <div class="account-field">
            <label for="contact"><span class="req">*</span> মোবাইল নম্বর</label>
            <input type="tel" id="contact" name="contact" value="{{ old('contact') }}" placeholder="01XXXXXXXXX" required inputmode="tel">
        </div>
        <button class="account-btn" type="submit">অর্ডার দেখুন</button>
    </form>
</div>
@endsection
