@extends('layouts.storefront')

@section('title', 'Notifications')

@section('content')
<div class="container mt-4 mb-5">
    <h1>Notifications</h1>
    @include('storefront.account._nav')

    <form method="post" action="{{ route('account.notifications.read-all') }}" class="mb-3">@csrf<button class="ig-btn btn-sm">সব পড়া হয়েছে</button></form>

    @forelse($notifications as $notification)
        <div class="border-bottom py-3 {{ $notification->read_at ? 'text-muted' : '' }}">
            <strong>{{ $notification->title }}</strong>
            <small class="float-end">{{ $notification->created_at->diffForHumans() }}</small>
            @if($notification->body)<p class="mb-1">{{ $notification->body }}</p>@endif
            @if(! $notification->read_at)
                <form method="post" action="{{ route('account.notifications.read', $notification) }}">@csrf<button class="ig-btn btn-sm">পড়া হয়েছে</button></form>
            @endif
        </div>
    @empty
        <p>এখনো কোনো নোটিফিকেশন নেই।</p>
    @endforelse

    {{ $notifications->links() }}
</div>
@endsection
