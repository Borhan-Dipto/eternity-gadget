{{-- Shared error page inside the normal shop layout (header, menu, search still work). --}}
@extends('layouts.storefront')

@section('title', $title)

@section('content')
<div style="max-width:560px;margin:56px auto 40px;padding:0 16px;text-align:center;font-family:var(--font-sans);">
    <div style="font-size:64px;font-weight:800;line-height:1;color:var(--brand);letter-spacing:-2px;">{{ $code }}</div>
    <h1 style="font-size:22px;margin:16px 0 10px;color:#111;">{{ $title }}</h1>
    <p style="color:#555;line-height:1.7;margin:0 0 22px;">{{ $message }}</p>

    @if($showSearch ?? false)
        <form action="{{ route('search') }}" method="get" style="display:flex;gap:8px;max-width:420px;margin:0 auto 18px;">
            <input name="q" placeholder="প্রোডাক্টের নাম বা মডেল লিখুন..." style="flex:1;min-width:0;padding:11px 14px;border:1px solid #ddd;border-radius:10px;font-size:14px;">
            <button type="submit" style="padding:0 18px;border:0;border-radius:10px;background:#111;color:#fff;font-size:14px;">খুঁজুন</button>
        </form>
    @endif

    <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
        <a href="{{ url('/') }}" style="padding:11px 20px;border-radius:10px;background:var(--brand);color:#fff;text-decoration:none;font-weight:600;font-size:14px;">হোম পেজে যান</a>
        @if(whatsapp_link())
            <a href="{{ whatsapp_link() }}" target="_blank" rel="noopener" style="padding:11px 20px;border-radius:10px;border:1px solid #ddd;color:#111;text-decoration:none;font-weight:600;font-size:14px;">WhatsApp-এ জানান</a>
        @endif
    </div>
</div>
@endsection
