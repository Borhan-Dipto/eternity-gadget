<nav class="account-sidebar mb-4">
    <ul class="list-unstyled account-nav-list">
        <li><a href="{{ route('account') }}">My Account</a></li>
        @guest
            <li><a href="{{ route('account.login') }}">Login</a></li>
            <li><a href="{{ route('account.register') }}">Register</a></li>
            <li><a href="{{ route('account.password.request') }}">Forgot Password</a></li>
        @endguest
        <li><a href="{{ route('account.addresses') }}">Address Book</a></li>
        <li><a href="{{ route('wishlist') }}">Wish List</a></li>
        <li><a href="{{ route('account.orders') }}">Order History</a></li>
        <li><a href="{{ route('account.guest-tracking') }}">Track Order</a></li>
        <li><a href="{{ route('account.rewards') }}">Reward Points</a></li>
        <li><a href="{{ route('account.newsletter') }}">Newsletter</a></li>
        @auth
            <li><a href="{{ route('account.profile') }}">Edit Profile</a></li>
            <li><a href="{{ route('account.notifications') }}">Notifications</a></li>
            <li><a href="{{ route('compare') }}">Compare Products</a></li>
        @endauth
    </ul>
</nav>

<style>
.account-nav-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 8px 16px; }
.account-nav-list a { display: block; padding: 8px 12px; border: 1px solid #eee; border-radius: 6px; color: #111; text-decoration: none; }
.account-nav-list a:hover { border-color: var(--link-hover-color); color: var(--link-hover-color); }
</style>
