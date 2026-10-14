@extends('layouts.storefront')

@section('title', 'Payment Methods')

@section('content')
<div class="container mt-4 mb-5">
    <h1>Payment Methods</h1>
    @include('storefront.account._nav')

    @foreach($paymentMethods as $method)
        <div class="border p-3 mb-3">
            @if($method->is_default)<span class="badge bg-success">Default</span>@endif
            <strong>{{ strtoupper($method->type) }}</strong> — {{ $method->label }}
            @if($method->last_four)<span>(**** {{ $method->last_four }})</span>@endif
            <div class="mt-2">
                @if(! $method->is_default)
                    <form method="post" action="{{ route('account.payment-methods.default', $method) }}" class="d-inline">@csrf<button class="ig-btn btn-sm">Set Default</button></form>
                @endif
                <form method="post" action="{{ route('account.payment-methods.destroy', $method) }}" class="d-inline">@csrf @method('DELETE')<button class="ig-btn btn-sm" style="background:#c0392b;">Remove</button></form>
            </div>
        </div>
    @endforeach

    <h3 class="mt-4">Add Payment Method</h3>
    <form method="post" action="{{ route('account.payment-methods.store') }}">
        @csrf
        <div class="mb-2">
            <select class="form-control" name="type" required>
                <option value="cod">Cash on Delivery</option>
                <option value="card">Card Label</option>
            </select>
        </div>
        <div class="mb-2"><input class="form-control" name="label" placeholder="Label (e.g. My Visa, COD Home)" required></div>
        <div class="mb-2"><input class="form-control" name="last_four" placeholder="Last 4 digits (cards only)" maxlength="4"></div>
        <div class="mb-2"><label><input type="checkbox" name="is_default" value="1"> Set as default</label></div>
        <button class="ig-btn">Save</button>
    </form>
</div>
@endsection
