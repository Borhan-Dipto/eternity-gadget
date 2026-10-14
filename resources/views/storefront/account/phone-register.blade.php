@extends('layouts.storefront')

@section('title', 'Phone Register')

@section('content')
<div class="container mt-4 mb-5" style="max-width:480px;">
    <h1>Register with Phone</h1>

    @if(session('phone_register'))
        <form method="post" action="{{ route('account.phone.verify') }}">
            @csrf
            <p>Enter the code sent to your phone.</p>
            <div class="mb-2"><input class="form-control" name="code" placeholder="Verification code" required autofocus></div>
            <button class="ig-btn w-100">Verify & Create Account</button>
        </form>
    @else
        <form method="post" action="{{ route('account.phone.register.submit') }}">
            @csrf
            <div class="mb-2"><input class="form-control" name="name" value="{{ old('name') }}" placeholder="Name" required></div>
            @include('storefront.partials.phone-input', [
                'name' => 'phone',
                'value' => old('phone'),
                'label' => 'Mobile',
            ])
            <button class="ig-btn w-100">Send Code</button>
        </form>
    @endif

    <p class="mt-3"><a href="{{ route('account.register') }}">Email register</a> | <a href="{{ route('account.login') }}">Login</a></p>
</div>
@endsection
