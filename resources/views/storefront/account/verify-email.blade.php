@extends('layouts.storefront')

@section('title', 'Verify Email')

@section('content')
<div class="container mt-4 mb-5" style="max-width:520px;">
    <h1>Verify Your Email</h1>
    <p>Thanks for signing up. Please check your email for a verification link.</p>
    <form method="post" action="{{ route('account.verification.send') }}">
        @csrf
        <button class="ig-btn">Resend Verification Email</button>
    </form>
    <p class="mt-3"><a href="{{ route('account') }}">Back to account</a></p>
</div>
@endsection
