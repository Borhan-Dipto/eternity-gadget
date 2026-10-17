@php($active = $activeNav ?? '')

<aside class="account-sidebar">
    @guest
        <a href="{{ route('account.login') }}" class="account-sidebar-item{{ $active === 'login' ? ' active' : '' }}">
            <svg viewBox="0 0 24 24"><path d="M11 7L9.6 8.4l2.6 2.6H2v2h10.2l-2.6 2.6L11 17l6-6-6-6zm9 12h-8v2h8c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-8v2h8v14z"/></svg>
            Login
        </a>
        <a href="{{ route('account.register') }}" class="account-sidebar-item{{ $active === 'register' ? ' active' : '' }}">
            <svg viewBox="0 0 24 24"><path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
            Register
        </a>
        <a href="{{ route('account.password.request') }}" class="account-sidebar-item{{ $active === 'forgot-password' ? ' active' : '' }}">
            <svg viewBox="0 0 24 24"><path d="M12.65 10A5.99 5.99 0 007 6c-3.31 0-6 2.69-6 6s2.69 6 6 6h5.65c.76 0 1.35-.54 1.35-1.3 0-.79-.59-1.3-1.35-1.3H7c-2.21 0-4-1.79-4-4s1.79-4 4-4c1.66 0 3.14.84 4 2.13V9.27c-.6-.34-1.28-.52-2-.52-2.21 0-4 1.79-4 4s1.79 4 4 4z"/></svg>
            Forgotten Password
        </a>
    @endguest

    @auth
        <a href="{{ route('account') }}" class="account-sidebar-item{{ $active === 'account' ? ' active' : '' }}">
            <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
            My Account
        </a>
        <a href="{{ route('account.profile') }}" class="account-sidebar-item{{ $active === 'profile' ? ' active' : '' }}">
            <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1.003 1.003 0 000-1.42l-2.34-2.34a1.003 1.003 0 00-1.42 0l-1.83 1.83 3.75 3.75 1.84-1.82z"/></svg>
            Edit Account
        </a>
        @if(\Illuminate\Support\Facades\Route::has('account.password'))
            <a href="{{ route('account.password') }}" class="account-sidebar-item{{ $active === 'password' ? ' active' : '' }}">
                <svg viewBox="0 0 24 24"><path d="M12.65 10A5.99 5.99 0 007 6c-3.31 0-6 2.69-6 6s2.69 6 6 6h5.65c.76 0 1.35-.54 1.35-1.3 0-.79-.59-1.3-1.35-1.3H7c-2.21 0-4-1.79-4-4s1.79-4 4-4c1.66 0 3.14.84 4 2.13V9.27c-.6-.34-1.28-.52-2-.52-2.21 0-4 1.79-4 4s1.79 4 4 4z"/></svg>
                Password
            </a>
        @else
            <a href="{{ route('account.profile') }}" class="account-sidebar-item{{ $active === 'password' ? ' active' : '' }}">
                <svg viewBox="0 0 24 24"><path d="M12.65 10A5.99 5.99 0 007 6c-3.31 0-6 2.69-6 6s2.69 6 6 6h5.65c.76 0 1.35-.54 1.35-1.3 0-.79-.59-1.3-1.35-1.3H7c-2.21 0-4-1.79-4-4s1.79-4 4-4c1.66 0 3.14.84 4 2.13V9.27c-.6-.34-1.28-.52-2-.52-2.21 0-4 1.79-4 4s1.79 4 4 4z"/></svg>
                Password
            </a>
        @endif
    @endauth

    <a href="{{ route('account.addresses') }}" class="account-sidebar-item{{ $active === 'addresses' ? ' active' : '' }}">
        <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
        Address Book
    </a>
    <a href="{{ route('wishlist') }}" class="account-sidebar-item{{ $active === 'wishlist' ? ' active' : '' }}">
        <svg viewBox="0 0 24 24"><path d="M16.5 3c-1.74 0-3.41.81-4.5 2.09C10.91 3.81 9.24 3 7.5 3 4.42 3 2 5.42 2 8.5c0 3.78 3.4 6.86 8.55 11.54L12 21.35l1.45-1.32C18.6 15.36 22 12.28 22 8.5 22 5.42 19.58 3 16.5 3z"/></svg>
        Wish List
    </a>
    <a href="{{ route('account.orders') }}" class="account-sidebar-item{{ $active === 'orders' ? ' active' : '' }}">
        <svg viewBox="0 0 24 24"><path d="M13 3a9 9 0 00-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42A8.954 8.954 0 0013 21a9 9 0 000-18zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/></svg>
        Order History
    </a>
@guest
    <a href="{{ route('account.guest-tracking') }}" class="account-sidebar-item{{ $active === 'guest-tracking' ? ' active' : '' }}">
        <svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 010-5 2.5 2.5 0 000 5z"/></svg>
        Track Order
    </a>
@endguest
@if(setting('rewards_enabled', '1') === '1')
    <a href="{{ route('account.rewards') }}" class="account-sidebar-item{{ $active === 'rewards' ? ' active' : '' }}">
        <svg viewBox="0 0 24 24"><path d="M19 5h-2V3H7v2H5c-1.1 0-2 .9-2 2v1c0 2.55 1.92 4.63 4.39 4.94.63 1.5 1.98 2.63 3.61 2.96V19H7v2h10v-2h-4v-3.1c1.63-.33 2.98-1.46 3.61-2.96C19.08 12.63 21 10.55 21 8V7c0-1.1-.9-2-2-2zM5 8V7h2v3.82C5.84 10.4 5 9.3 5 8zm14 0c0 1.3-.84 2.4-2 2.82V7h2v1z"/></svg>
        Reward Points
    </a>
@endif
    <a href="{{ route('account.newsletter') }}" class="account-sidebar-item{{ $active === 'newsletter' ? ' active' : '' }}">
        <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
        Newsletter
    </a>

    @auth
        <form method="post" action="{{ route('account.logout') }}" class="mb-0">
            @csrf
            <button type="submit" class="account-sidebar-item" style="width:100%;border:0;background:none;cursor:pointer;text-align:left;">
                <svg viewBox="0 0 24 24"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5-5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg>
                Logout
            </button>
        </form>
    @endauth
</aside>
