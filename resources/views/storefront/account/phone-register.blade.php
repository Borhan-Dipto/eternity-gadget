@extends('layouts.storefront')

@section('title', 'Phone Register')

@section('content')
<div class="container mt-4 mb-5" style="max-width:480px;">
    <h1>মোবাইল দিয়ে অ্যাকাউন্ট খুলুন</h1>

    @if(session('phone_register'))
        <form method="post" action="{{ route('account.phone.verify') }}">
            @csrf
            <p>মোবাইলে আসা কোডটি লিখুন।</p>
            <div class="mb-2"><input class="form-control" name="code" placeholder="মোবাইলে আসা কোড" required autofocus></div>
            <button class="ig-btn w-100">অ্যাকাউন্ট খুলুন</button>
        </form>
    @else
        <form method="post" action="{{ route('account.phone.register.submit') }}">
            @csrf
            <div class="mb-2"><input class="form-control" name="name" value="{{ old('name') }}" placeholder="আপনার নাম" required></div>
            @include('storefront.partials.phone-input', [
                'name' => 'phone',
                'value' => old('phone'),
                'label' => 'মোবাইল নম্বর',
            ])
            <button class="ig-btn w-100">কোড পাঠান</button>
        </form>
    @endif

    <p class="mt-3"><a href="{{ route('account.register') }}">ইমেইল দিয়ে অ্যাকাউন্ট</a> | <a href="{{ route('account.login') }}">Login</a></p>
</div>
@endsection
