@extends('layouts.storefront')

@section('title', 'একটু অপেক্ষা করুন')

@section('content')
<div style="max-width:560px;margin:48px auto;padding:0 16px;text-align:center;">
    <h1 style="font-size:22px;margin-bottom:12px;">অনেকবার চেষ্টা করা হয়েছে</h1>
    <p style="color:#555;line-height:1.6;">
        অল্প সময়ে অনেকগুলো অর্ডার বা অনুরোধ এসেছে। কয়েক মিনিট পরে আবার চেষ্টা করুন।
        জরুরি হলে আমাদের সাথে সরাসরি যোগাযোগ করুন{{ setting('phone') ? ': ' . setting('phone') : '' }}।
    </p>
    <p style="margin-top:20px;"><a href="{{ url('/') }}">হোমপেজে ফিরে যান</a></p>
</div>
@endsection
