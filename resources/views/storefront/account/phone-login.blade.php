@extends('layouts.storefront')

@section('title', 'Phone Login')

@section('content')
<div class="container mt-4 mb-5" style="max-width:480px;">
    <h1>Login with Phone</h1>

    @if(session('phone_login'))
        <form method="post" action="{{ route('account.phone.login.submit') }}">
            @csrf
            <input type="hidden" name="phone" value="{{ session('phone_login') }}">
            <p>Enter the code sent to {{ session('phone_login') }}.</p>
            <div class="mb-2"><input class="form-control" name="code" placeholder="Verification code" required autofocus></div>
            <button class="ig-btn w-100">Verify & Login</button>
        </form>
    @else
        <form method="post" action="{{ route('account.phone.login.submit') }}">
            @csrf
            @include('storefront.partials.phone-input', [
                'name' => 'phone',
                'value' => old('phone'),
                'label' => 'Mobile',
            ])
            <button class="ig-btn w-100">Send Code</button>
        </form>
    @endif

    <p class="mt-3"><a href="{{ route('account.login') }}">Email login</a> | <a href="{{ route('account.phone.register') }}">Register</a></p>
</div>
@endsection
