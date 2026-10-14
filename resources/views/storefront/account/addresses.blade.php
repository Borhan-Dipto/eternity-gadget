@extends('storefront.account.layout', ['activeNav' => 'addresses'])

@section('title', 'Address Book')
@section('account_breadcrumb', 'Address Book')

@section('account_content')
<div class="account-box">
    <h1>Address Book Entries</h1>

    @if($addresses->isEmpty())
        <p class="account-empty-msg">You have no addresses in your account.</p>
    @else
        @foreach($addresses as $address)
            <div style="padding:14px 0;border-bottom:1px solid #eee;">
                @if($address->is_default)<strong style="font-size:12px;">Default</strong><br>@endif
                <strong>{{ $address->label ?: 'Address' }}</strong><br>
                {{ $address->name }} — {{ $address->phone }}<br>
                {{ $address->line1 }}@if($address->line2), {{ $address->line2 }}@endif<br>
                {{ $address->city }}@if($address->state), {{ $address->state }}@endif {{ $address->postal_code }}
                <div style="margin-top:8px;">
                    @if(! $address->is_default)
                        <form method="post" action="{{ route('account.addresses.default', $address) }}" class="d-inline">@csrf<button class="account-btn" style="padding:6px 14px;font-size:12px;">Set Default</button></form>
                    @endif
                    <form method="post" action="{{ route('account.addresses.destroy', $address) }}" class="d-inline">@csrf @method('DELETE')<button class="account-btn" style="padding:6px 14px;font-size:12px;background:#c0392b;">Delete</button></form>
                </div>
            </div>
        @endforeach
    @endif

    <div class="account-actions">
        <a href="{{ route('account') }}" class="account-btn">Back</a>
        <button type="button" class="account-btn" onclick="document.getElementById('add-address-form').style.display='block'">New Address</button>
    </div>
</div>

<div class="account-box" id="add-address-form" style="margin-top:20px;{{ $addresses->isEmpty() ? '' : 'display:none;' }}">
    <h2>Add New Address</h2>
    <form method="post" action="{{ route('account.addresses.store') }}">
        @csrf
        <div class="account-field-row">
            <div class="account-field"><label>Label</label><input name="label" placeholder="Home, Office"></div>
            <div class="account-field"><label><span class="req">*</span> Full Name</label><input name="name" required></div>
        </div>
        @include('storefront.partials.phone-input', [
            'name' => 'phone',
            'label' => 'Mobile',
            'variant' => 'account-field',
        ])
        <div class="account-field"><label><span class="req">*</span> Address Line 1</label><input name="line1" required></div>
        <div class="account-field"><label>Address Line 2</label><input name="line2"></div>
        <div class="account-field-row">
            <div class="account-field"><label><span class="req">*</span> City</label><input name="city" required></div>
            <div class="account-field"><label>State</label><input name="state"></div>
        </div>
        <div class="account-field"><label>Postal Code</label><input name="postal_code"></div>
        <div class="account-field"><label style="text-align:left;font-weight:400;text-transform:none;"><input type="checkbox" name="is_default" value="1"> Set as default address</label></div>
        <button class="account-btn" type="submit">Save Address</button>
    </form>
</div>
@endsection
