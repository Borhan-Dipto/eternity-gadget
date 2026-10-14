@extends('storefront.account.layout', ['activeNav' => 'forgot-password'])

@section('title', 'Forgot Password')
@section('account_breadcrumb', 'Forgotten Password')

@section('account_content')
<div class="account-box">
    <h1>Forgot Your Password?</h1>
    <p class="lead">Enter the e-mail address associated with your account. Click submit to have a password reset link e-mailed to you.</p>

    <form method="post" action="{{ route('account.password.email') }}">
        @csrf

        <div class="account-field">
            <label for="email"><span class="req">*</span> Your E-Mail Address</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="E-Mail Address" required>
        </div>

        <div class="account-actions">
            <a href="{{ route('account.login') }}" class="account-btn">Back</a>
            <button class="account-btn" type="submit">Continue</button>
        </div>
    </form>
</div>
@endsection
