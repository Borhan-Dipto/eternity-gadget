@extends('storefront.account.layout', ['activeNav' => 'addresses'])

@section('title', 'Address Book')
@section('account_breadcrumb', 'Address Book')

@section('account_content')
<div class="account-box">
    <h1>আমার ঠিকানা</h1>

    @if($addresses->isEmpty())
        <p class="account-empty-msg">এখনো কোনো ঠিকানা সেভ করা হয়নি।</p>
    @else
        @foreach($addresses as $address)
            <div style="padding:14px 0;border-bottom:1px solid #eee;">
                @if($address->is_default)<strong style="font-size:12px;">মূল ঠিকানা</strong><br>@endif
                <strong>{{ $address->label ?: 'ঠিকানা' }}</strong><br>
                {{ $address->name }} — {{ $address->phone }}<br>
                {{ $address->line1 }}@if($address->line2), {{ $address->line2 }}@endif<br>
                {{ $address->city }}@if($address->state), {{ $address->state }}@endif {{ $address->postal_code }}
                <div style="margin-top:8px;">
                    @if(! $address->is_default)
                        <form method="post" action="{{ route('account.addresses.default', $address) }}" class="d-inline">@csrf<button class="account-btn" style="padding:6px 14px;font-size:12px;">মূল ঠিকানা করুন</button></form>
                    @endif
                    <form method="post" action="{{ route('account.addresses.destroy', $address) }}" class="d-inline">@csrf @method('DELETE')<button class="account-btn" style="padding:6px 14px;font-size:12px;background:#c0392b;">মুছুন</button></form>
                </div>
            </div>
        @endforeach
    @endif

    <div class="account-actions">
        <a href="{{ route('account') }}" class="account-btn">ফিরে যান</a>
        <button type="button" class="account-btn" onclick="document.getElementById('add-address-form').style.display='block'">নতুন ঠিকানা যোগ করুন</button>
    </div>
</div>

<div class="account-box" id="add-address-form" style="margin-top:20px;{{ $addresses->isEmpty() ? '' : 'display:none;' }}">
    <h2>নতুন ঠিকানা</h2>
    <form method="post" action="{{ route('account.addresses.store') }}">
        @csrf
        <div class="account-field-row">
            <div class="account-field"><label>ঠিকানার নাম</label><input name="label" placeholder="যেমন: বাসা, অফিস"></div>
            <div class="account-field"><label><span class="req">*</span> পূর্ণ নাম</label><input name="name" required></div>
        </div>
        @include('storefront.partials.phone-input', [
            'name' => 'phone',
            'label' => 'মোবাইল নম্বর',
            'variant' => 'account-field',
        ])
        <div class="account-field"><label><span class="req">*</span> ঠিকানা (বাসা, রোড, এলাকা)</label><input name="line1" required></div>
        <div class="account-field"><label>ঠিকানা (২য় লাইন)</label><input name="line2"></div>
        <div class="account-field-row">
            <div class="account-field"><label><span class="req">*</span> থানা / শহর</label><input name="city" required></div>
            <div class="account-field"><label>জেলা</label><input name="state"></div>
        </div>
        <div class="account-field"><label>পোস্ট কোড</label><input name="postal_code"></div>
        <div class="account-field"><label style="text-align:left;font-weight:400;text-transform:none;"><input type="checkbox" name="is_default" value="1"> এটাকে মূল ঠিকানা হিসেবে রাখুন</label></div>
        <button class="account-btn" type="submit">ঠিকানা সেভ করুন</button>
    </form>
</div>
@endsection
