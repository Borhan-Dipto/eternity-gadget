@extends('storefront.account.layout', ['activeNav' => 'rewards'])

@section('title', 'Reward Points')
@section('account_breadcrumb', 'Reward Points')

@section('account_content')
<div class="account-box">
    <h1>Reward Points</h1>
    <div style="text-align:center;padding:30px 0;">
        <p style="margin-bottom:8px;">Your balance</p>
        <p style="font-size:42px;margin:0 0 12px;">{{ number_format($points) }}</p>
        <p style="font-size:14px;color:#666;">Earn 1 point for every ৳100 spent on delivered orders.</p>
    </div>
</div>
@endsection
