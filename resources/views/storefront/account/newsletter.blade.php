@extends('storefront.account.layout', ['activeNav' => 'newsletter'])

@section('title', 'Newsletter')
@section('account_breadcrumb', 'Newsletter')

@section('account_content')
<div class="account-box">
    <h1>Newsletter</h1>
    <p class="lead">Subscribe / unsubscribe to newsletter</p>

    <form method="post" action="{{ route('account.newsletter.update') }}">
        @csrf
        <div class="account-field">
            <label style="font-weight:400;text-transform:none;text-align:left;">
                <input type="checkbox" name="marketing_opt_in" value="1" {{ $user->marketing_opt_in ? 'checked' : '' }}>
                Subscribe to newsletter and promotional emails
            </label>
        </div>
        <div class="account-actions" style="justify-content:flex-end;">
            <button class="account-btn" type="submit">Continue</button>
        </div>
    </form>
</div>
@endsection
