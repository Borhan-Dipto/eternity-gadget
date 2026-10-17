@extends('layouts.storefront')

@section('title', 'Verify Email')

@section('content')
<div class="container mt-4 mb-5" style="max-width:520px;">
    <h1>ইমেইল যাচাই করুন</h1>
    <p>অ্যাকাউন্ট খোলার জন্য ধন্যবাদ। আপনার ইমেইলে একটি যাচাইয়ের লিংক পাঠানো হয়েছে।</p>
    <form method="post" action="{{ route('account.verification.send') }}">
        @csrf
        <button class="ig-btn">আবার লিংক পাঠান</button>
    </form>
    <p class="mt-3"><a href="{{ route('account') }}">অ্যাকাউন্টে ফিরে যান</a></p>
</div>
@endsection
