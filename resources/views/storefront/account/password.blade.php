@extends('storefront.account.layout', ['activeNav' => 'password'])

@section('title', 'Change Password')
@section('account_breadcrumb', 'Password')

@section('account_content')
<div class="account-box">
    <h1>Change Password</h1>

    <form method="post" action="{{ route('account.password.change') }}">
        @csrf

        <div class="account-detail-row">
            <label for="password"><span class="req">*</span> Password</label>
            <input type="password" id="password" name="password" placeholder="Password" required>
        </div>

        <div class="account-detail-row">
            <label for="password_confirmation"><span class="req">*</span> Password Confirm</label>
            <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Password Confirm" required>
        </div>

        <div class="account-actions">
            <a href="{{ route('account') }}" class="account-btn">Back</a>
            <button class="account-btn" type="submit">Continue</button>
        </div>
    </form>
</div>
@endsection
