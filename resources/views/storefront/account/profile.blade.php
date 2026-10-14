@extends('storefront.account.layout', ['activeNav' => 'profile'])

@section('title', 'Edit Account')
@section('account_breadcrumb', 'Edit Account')

@section('account_content')
@php
    $nameParts = explode(' ', $user->name, 2);
    $firstName = old('first_name', $nameParts[0] ?? '');
    $lastName = old('last_name', $nameParts[1] ?? '');
@endphp

<div class="account-box">
    <h1>Your Personal Details</h1>

    <form method="post" action="{{ route('account.profile.update') }}" enctype="multipart/form-data">
        @csrf

        <div class="account-detail-row">
            <label for="first_name"><span class="req">*</span> First Name</label>
            <input type="text" id="first_name" name="first_name" value="{{ $firstName }}" required>
        </div>

        <div class="account-detail-row">
            <label for="last_name"><span class="req">*</span> Last Name</label>
            <input type="text" id="last_name" name="last_name" value="{{ $lastName }}" required>
        </div>

        <div class="account-detail-row">
            <label for="email"><span class="req">*</span> E-Mail</label>
            <input type="email" id="email" value="{{ $user->email }}" disabled readonly>
        </div>

        @include('storefront.partials.phone-input', [
            'name' => 'phone',
            'value' => old('phone', $user->phone),
            'label' => 'Mobile',
            'variant' => 'account-row',
        ])

        <div class="account-actions">
            <a href="{{ route('account') }}" class="account-btn">Back</a>
            <button class="account-btn" type="submit">Continue</button>
        </div>
    </form>
</div>
@endsection
