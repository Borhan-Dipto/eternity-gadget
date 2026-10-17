@extends('storefront.account.layout', ['activeNav' => 'login'])

@section('title', 'Login')
@section('account_breadcrumb', 'Login')
@section('account_page_row_class', 'account-page-row--login')

@section('account_content')
<style>
    .account-page-row--login .login-page {
        box-sizing: border-box;
        width: 100%;
        max-width: 520px;
        margin: 0 auto;
        border: 1px solid #ddd;
        background: #fff;
        padding: 32px 36px 36px;
        font-family: var(--font-sans);
        color: #111;
        text-align: center;
    }

    .account-page-row--login .login-page h1 {
        font-size: 26px;
        font-weight: 400;
        text-align: center;
        margin: 0 0 28px;
        line-height: 1.25;
    }

    .account-page-row--login .login-field {
        margin-bottom: 22px;
        text-align: center;
    }

    .account-page-row--login .login-field label {
        display: block;
        text-align: center;
        font-family: var(--font-sans);
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .5px;
        text-transform: uppercase;
        margin-bottom: 10px;
    }

    .account-page-row--login .login-input {
        display: block;
        width: 100%;
        max-width: 100%;
        border: 0;
        border-bottom: 1px solid #ccc;
        background: transparent;
        padding: 10px 4px;
        font-size: 15px;
        font-family: var(--font-sans);
        color: #111;
        text-align: center;
        margin: 0 auto;
        box-sizing: border-box;
    }

    .account-page-row--login .login-input:focus {
        outline: none;
        border-bottom-color: #111;
    }

    .account-page-row--login .login-input::placeholder {
        color: #999;
        font-style: italic;
        text-align: center;
    }

    .account-page-row--login .login-links {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin: 8px 0 28px;
        font-size: 14px;
        flex-wrap: wrap;
        text-align: center;
    }

    .account-page-row--login .login-links a {
        color: #111;
        text-decoration: none;
    }

    .account-page-row--login .login-links a:hover {
        color: var(--link-hover-color, #83B735);
        text-decoration: underline;
    }

    .account-page-row--login .login-submit {
        display: block;
        width: 100%;
        max-width: 100%;
        background: #111;
        color: #fff;
        border: 0;
        padding: 14px 20px;
        font-family: var(--font-sans);
        font-size: 15px;
        font-weight: 700;
        letter-spacing: .5px;
        text-transform: uppercase;
        cursor: pointer;
        text-align: center;
        margin: 0 auto;
        box-sizing: border-box;
    }

    .account-page-row--login .login-submit:hover {
        background: #333;
    }

    .account-page-row--login .login-error {
        margin-bottom: 18px;
        padding: 10px 12px;
        background: #fdf0f0;
        border: 1px solid #f0c0c0;
        color: #c0392b;
        font-size: 14px;
        text-align: center;
        font-family: var(--font-sans);
    }

    @media (max-width: 991px) {
        .account-page-row--login .login-page {
            max-width: 480px;
            padding: 28px 24px 32px;
        }
    }

    @media (max-width: 575px) {
        .account-page-row--login .login-page {
            max-width: 100%;
            padding: 28px 16px 32px;
        }

        .account-page-row--register .reg-page {
            padding-left: 0;
            padding-right: 0;
        }
    }
</style>

<div class="account-box login-page">
    <h1>লগইন করুন</h1>

    @if($errors->any())
        <div class="login-error">
            {{ $errors->first('email') ?: 'ইমেইল বা পাসওয়ার্ড ভুল হয়েছে, আবার চেষ্টা করুন।' }}
        </div>
    @endif

    <form method="post" action="{{ route('account.login.submit') }}">
        @csrf

        <div class="login-field">
            <label for="email">ইমেইল</label>
            <input
                type="email"
                id="email"
                name="email"
                class="login-input"
                value="{{ old('email') }}"
                placeholder="আপনার ইমেইল"
                required
                autofocus
                autocomplete="email"
            >
        </div>

        <div class="login-field">
            <label for="password">পাসওয়ার্ড</label>
            <input
                type="password"
                id="password"
                name="password"
                class="login-input"
                placeholder="পাসওয়ার্ড"
                required
                autocomplete="current-password"
            >
        </div>

        <div class="login-links">
            <a href="{{ route('account.password.request') }}">পাসওয়ার্ড ভুলে গেছেন?</a>
            <a href="{{ route('account.register') }}">নতুন অ্যাকাউন্ট খুলুন</a>
        </div>

        <button class="login-submit" type="submit">লগইন</button>
    </form>
</div>
@endsection
