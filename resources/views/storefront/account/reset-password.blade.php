@extends('storefront.account.layout', ['activeNav' => 'forgot-password'])

@section('title', 'Reset Password')
@section('account_breadcrumb', 'Reset Password')

@section('account_content')
<div class="account-box">
    <h1>Reset Password</h1>

    <form method="post" action="{{ route('account.password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="account-field">
            <label for="email"><span class="req">*</span> E-Mail Address</label>
            <input type="email" id="email" name="email" value="{{ old('email', $email) }}" required>
        </div>

        <div class="account-field-row">
            <div class="account-field">
                <label for="password"><span class="req">*</span> Password</label>
                <input type="password" id="password" name="password" placeholder="Password" required>
            </div>
            <div class="account-field">
                <label for="password_confirmation"><span class="req">*</span> Password Confirm</label>
                <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Password Confirm" required>
            </div>
        </div>

        <div class="account-actions" style="justify-content:flex-end;">
            <button class="account-btn" type="submit">Continue</button>
        </div>
    </form>
</div>
@endsection
