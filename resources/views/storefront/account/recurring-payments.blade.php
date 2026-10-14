@extends('storefront.account.layout', ['activeNav' => 'recurring'])

@section('title', 'Recurring Payments')
@section('account_breadcrumb', 'Recurring Payments')

@section('account_content')
<div class="account-box">
    <h1>Recurring Payments</h1>
    <p class="account-empty-msg">No recurring payments found!</p>
    <div class="account-actions" style="justify-content:flex-end;">
        <a href="{{ route('home') }}" class="account-btn">View More Products</a>
    </div>
</div>
@endsection
