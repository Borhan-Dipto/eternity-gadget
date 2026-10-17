@extends('layouts.storefront')

@section('title', 'Contact Us')

@section('content')
<div class="container mt-4 mb-5" style="max-width:640px;">
    <h1>Contact Us</h1>
    @auth @include('storefront.account._nav') @endauth

    <form method="post" action="{{ route('account.contact.store') }}">
        @csrf
        <div class="mb-2"><input class="form-control" name="name" value="{{ old('name', auth()->user()?->name) }}" placeholder="আপনার নাম" required></div>
        <div class="mb-2"><input class="form-control" name="email" type="email" value="{{ old('email', auth()->user()?->email) }}" placeholder="ইমেইল" required></div>
        @include('storefront.partials.phone-input', [
            'name' => 'phone',
            'value' => old('phone', auth()->user()?->phone),
            'label' => 'মোবাইল নম্বর',
        ])
        <div class="mb-2"><input class="form-control" name="subject" value="{{ old('subject') }}" placeholder="বিষয়" required></div>
        <div class="mb-2"><textarea class="form-control" name="message" rows="6" placeholder="আপনার মেসেজ" required>{{ old('message') }}</textarea></div>
        <button class="ig-btn">মেসেজ পাঠান</button>
    </form>
</div>
@endsection
