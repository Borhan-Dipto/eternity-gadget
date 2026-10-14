@extends('storefront.account.layout', ['activeNav' => 'register'])

@section('title', 'Register')
@section('account_breadcrumb', 'Register')
@section('account_page_row_class', 'account-page-row--register')

@section('account_content')
<style>
    .reg-page {
        font-family: "Times New Roman", Times, serif;
        color: #111;
    }

    .reg-page h1 {
        font-size: 26px;
        font-weight: 400;
        margin: 0 0 10px;
        line-height: 1.25;
    }

    .reg-lead {
        font-size: 15px;
        line-height: 1.5;
        color: #222;
        margin: 0 0 28px;
    }

    .reg-lead a {
        color: #111;
        text-decoration: underline;
    }

    .reg-section {
        margin-bottom: 28px;
    }

    .reg-section-title {
        font-size: 18px;
        font-weight: 400;
        margin: 0 0 14px;
        padding-bottom: 10px;
        border-bottom: 1px solid #ddd;
    }

    .reg-grid {
        display: grid;
        gap: 14px 24px;
    }

    .reg-grid--2 {
        grid-template-columns: 1fr 1fr;
    }

    .reg-field {
        display: grid;
        grid-template-columns: minmax(110px, 130px) 1fr;
        align-items: center;
        gap: 12px 16px;
        min-width: 0;
    }

    .reg-field > label {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .4px;
        text-transform: uppercase;
        text-align: right;
        margin: 0;
        line-height: 1.3;
    }

    .reg-field > label .req {
        color: #c0392b;
    }

    .reg-input {
        width: 100%;
        border: 1px solid #ccc;
        background: #fff;
        padding: 10px 12px;
        font-size: 14px;
        font-family: "Times New Roman", Times, serif;
        color: #111;
        min-width: 0;
    }

    .reg-input:focus {
        outline: none;
        border-color: #888;
    }

    .reg-input::placeholder {
        color: #999;
        font-style: italic;
    }

    .reg-phone-input {
        display: flex;
        align-items: stretch;
        border: 1px solid #ccc;
        background: #fff;
        min-width: 0;
    }

    .reg-phone-prefix {
        display: flex;
        align-items: center;
        padding: 0 10px;
        background: #ebebeb;
        color: #111;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
        font-size: 13px;
        font-weight: 700;
        border-right: 1px solid #ccc;
        flex-shrink: 0;
        user-select: none;
    }

    .reg-phone-input .reg-input {
        border: 0;
        flex: 1;
        min-width: 0;
    }

    .reg-field--radio > label {
        align-self: start;
        padding-top: 2px;
        text-transform: uppercase;
    }

    .reg-radio-group {
        display: flex;
        align-items: center;
        gap: 18px;
        font-family: "Times New Roman", Times, serif;
        font-size: 15px;
    }

    .reg-radio-group label {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-weight: 400;
        text-transform: none;
        text-align: left;
        cursor: pointer;
    }

    .reg-captcha-wrap {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 12px;
    }

    .reg-captcha-wrap .reg-input {
        width: 120px;
        flex: 0 0 120px;
    }

    .reg-captcha-img {
        display: block;
        border: 1px solid #111;
        cursor: pointer;
        background: #fff;
    }

    .reg-field-error {
        grid-column: 2;
        font-size: 13px;
        color: #c0392b;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
    }

    .reg-footer {
        margin-top: 24px;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 18px;
    }

    .reg-agree {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        font-family: "Times New Roman", Times, serif;
        font-size: 15px;
        line-height: 1.45;
        max-width: 100%;
        text-align: left;
        align-self: stretch;
    }

    .reg-agree input {
        margin-top: 4px;
        flex-shrink: 0;
    }

    .reg-agree a {
        color: #111;
        font-weight: 700;
    }

    .reg-submit {
        display: inline-block;
        background: #111;
        color: #fff;
        border: 0;
        padding: 14px 28px;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: .6px;
        text-transform: uppercase;
        cursor: pointer;
        min-width: 220px;
        text-align: center;
    }

    .reg-submit:hover {
        background: #333;
    }

    @media (max-width: 991px) {
        .reg-page h1 {
            font-size: 24px;
        }

        .reg-grid--2 {
            grid-template-columns: 1fr;
            gap: 22px;
        }

        .reg-field,
        .reg-field.ig-phone-wrap {
            grid-template-columns: 1fr;
            gap: 8px;
        }

        .reg-field > label {
            text-align: left;
        }

        .reg-input,
        .reg-phone-input {
            border: 0;
            border-bottom: 1px solid #ccc;
            border-radius: 0;
            padding: 8px 0;
            background: transparent;
        }

        .reg-phone-input {
            display: flex;
            align-items: stretch;
            border: 0;
            border-bottom: 1px solid #ccc;
            background: transparent;
        }

        .reg-phone-prefix {
            background: transparent;
            border: 0;
            padding: 8px 10px 8px 0;
            font-size: 14px;
            align-self: stretch;
            display: flex;
            align-items: center;
        }

        .reg-phone-input .reg-input {
            border: 0;
            padding: 8px 0;
            background: transparent;
        }

        .reg-field--radio > label {
            padding-top: 0;
        }

        .reg-captcha-wrap .reg-input {
            width: 100%;
            flex: 1 1 100%;
        }

        .reg-field-error {
            grid-column: 1;
        }

        .reg-footer {
            align-items: stretch;
        }

        .reg-submit {
            width: 100%;
            min-width: 0;
        }
    }

    @media (min-width: 992px) {
        .reg-page {
            padding-right: 8px;
        }

        .reg-footer {
            padding-top: 8px;
        }

        .reg-agree {
            justify-content: flex-end;
            align-self: auto;
            max-width: 420px;
        }
    }
</style>

<div class="account-box reg-page">
    <h1>Register Account</h1>
    <p class="reg-lead">If you already have an account with us, please login at the <a href="{{ route('account.login') }}">login page</a>.</p>

    @if($errors->any())
        <div class="reg-field-error" style="margin-bottom:20px;grid-column:auto;">
            Please fix the highlighted fields below.
        </div>
    @endif

    <form method="post" action="{{ route('account.register.submit') }}" class="reg-form" novalidate>
        @csrf

        <section class="reg-section">
            <h2 class="reg-section-title">Your Personal Details</h2>
            <div class="reg-grid reg-grid--2">
                <div class="reg-field">
                    <label for="first_name"><span class="req">*</span> First Name</label>
                    <input type="text" id="first_name" name="first_name" class="reg-input" value="{{ old('first_name') }}" placeholder="First Name" required autocomplete="given-name">
                </div>
                <div class="reg-field">
                    <label for="last_name"><span class="req">*</span> Last Name</label>
                    <input type="text" id="last_name" name="last_name" class="reg-input" value="{{ old('last_name') }}" placeholder="Last Name" required autocomplete="family-name">
                </div>
                <div class="reg-field">
                    <label for="email"><span class="req">*</span> E-Mail</label>
                    <input type="email" id="email" name="email" class="reg-input" value="{{ old('email') }}" placeholder="E-Mail" required autocomplete="email">
                </div>
                @include('storefront.partials.phone-input', [
                    'name' => 'phone',
                    'value' => old('phone'),
                    'label' => 'Mobile',
                    'variant' => 'register',
                    'placeholder' => '1XXXXXXXXX',
                ])
            </div>
        </section>

        <section class="reg-section">
            <h2 class="reg-section-title">Your Password</h2>
            <div class="reg-grid reg-grid--2">
                <div class="reg-field">
                    <label for="password"><span class="req">*</span> Password</label>
                    <input type="password" id="password" name="password" class="reg-input" placeholder="Password" required autocomplete="new-password">
                </div>
                <div class="reg-field">
                    <label for="password_confirmation"><span class="req">*</span> Password Confirm</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="reg-input" placeholder="Password Confirm" required autocomplete="new-password">
                </div>
            </div>
        </section>

        <section class="reg-section">
            <h2 class="reg-section-title">Newsletter</h2>
            <div class="reg-field reg-field--radio">
                <label>Subscribe</label>
                <div class="reg-radio-group">
                    <label>
                        <input type="radio" name="marketing_opt_in" value="1" {{ old('marketing_opt_in') == '1' ? 'checked' : '' }}>
                        Yes
                    </label>
                    <label>
                        <input type="radio" name="marketing_opt_in" value="0" {{ old('marketing_opt_in', '0') == '0' ? 'checked' : '' }}>
                        No
                    </label>
                </div>
            </div>
        </section>

        <section class="reg-section">
            <h2 class="reg-section-title">Captcha</h2>
            <div class="reg-field">
                <label for="reg-captcha-input"><span class="req">*</span> Enter the code in the box below</label>
                <div class="reg-captcha-wrap">
                    <input type="text" name="captcha" id="reg-captcha-input" class="reg-input" value="{{ old('captcha') }}" required autocomplete="off">
                    <img src="{{ route('captcha.register') }}?t={{ time() }}" alt="Captcha code" class="reg-captcha-img" id="reg-captcha-img" width="130" height="44" title="Click to refresh">
                </div>
            </div>
            @error('captcha')
                <div class="reg-field-error">{{ $message }}</div>
            @enderror
        </section>

        <div class="reg-footer">
            <label class="reg-agree">
                <input type="checkbox" name="agree" value="1" required {{ old('agree') ? 'checked' : '' }}>
                <span>I have read and agree to the <a href="{{ url('/page/privacy-policy') }}">Privacy Policy</a></span>
            </label>
            <button class="reg-submit" type="submit">Continue</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
(function () {
    const captchaImg = document.getElementById('reg-captcha-img');
    captchaImg?.addEventListener('click', () => {
        captchaImg.src = '{{ route('captcha.register') }}?t=' + Date.now();
    });
})();
</script>
@endpush
@endsection
