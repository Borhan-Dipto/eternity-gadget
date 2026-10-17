<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\WishlistItem;
use App\Services\CartService;
use App\Services\OtpService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AccountController extends Controller
{
    private const COMPARE_LIMIT = 4;

    private const OTP_PURPOSE_REGISTER = 'phone_register';

    private const OTP_PURPOSE_LOGIN = 'phone_login';

    public function __construct(
        private CartService $cartService,
        private OtpService $otpService,
    ) {}

    public function account(): View|RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('account.login');
        }

        $user = Auth::user();

        return view('storefront.account.dashboard', [
            'orders' => $user->orders()->with('items')->latest()->limit(10)->get(),
            'notifications' => $user->notifications()->latest()->limit(5)->get(),
            'unreadCount' => $user->notifications()->whereNull('read_at')->count(),
        ]);
    }

    public function showPassword(): View
    {
        return view('storefront.account.password');
    }

    public function profile(): View
    {
        return view('storefront.account.profile', [
            'user' => Auth::user(),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = Auth::user();
        User::mergeNormalizedPhoneInput($request);

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => [
                'required',
                'string',
                'max:50',
                Rule::unique('users', 'phone')->ignore($user->id),
            ],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        $data['name'] = trim($data['first_name'] . ' ' . $data['last_name']);
        unset($data['first_name'], $data['last_name']);

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        } else {
            unset($data['avatar']);
        }

        $normalizedPhone = User::requireValidBangladeshMobile($data['phone'], 'phone');
        if ($normalizedPhone !== $user->phone) {
            $data['phone_verified_at'] = null;
        }
        $data['phone'] = $normalizedPhone;

        $user->update($data);

        return back()->with('success', 'প্রোফাইল আপডেট হয়েছে।');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        Auth::user()->update(['password' => $data['password']]);

        return back()->with('success', 'পাসওয়ার্ড পরিবর্তন হয়েছে।');
    }

    public function deleteAccount(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']]);

        $user = Auth::user();
        $userId = $user->id;

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        DB::table('sessions')->where('user_id', $userId)->delete();
        User::query()->whereKey($userId)->delete();

        return redirect()->route('home')->with('success', 'আপনার অ্যাকাউন্ট মুছে ফেলা হয়েছে।');
    }

    public function addresses(): View
    {
        return view('storefront.account.addresses', [
            'addresses' => Auth::user()->addresses()->orderByDesc('is_default')->get(),
        ]);
    }

    public function storeAddress(Request $request): RedirectResponse
    {
        $data = $this->validateAddress($request);
        $data['user_id'] = Auth::id();

        if ($data['is_default'] ?? false) {
            Auth::user()->addresses()->update(['is_default' => false]);
        } elseif (! Auth::user()->addresses()->exists()) {
            $data['is_default'] = true;
        }

        Address::create($data);

        return back()->with('success', 'ঠিকানা যোগ হয়েছে।');
    }

    public function updateAddress(Request $request, Address $address): RedirectResponse
    {
        $this->authorizeAddress($address);
        $data = $this->validateAddress($request, $address);

        if ($data['is_default'] ?? false) {
            Auth::user()->addresses()->whereKeyNot($address->id)->update(['is_default' => false]);
        }

        $address->update($data);

        return back()->with('success', 'ঠিকানা আপডেট হয়েছে।');
    }

    public function destroyAddress(Address $address): RedirectResponse
    {
        $this->authorizeAddress($address);
        $wasDefault = $address->is_default;
        $address->delete();

        if ($wasDefault) {
            Auth::user()->addresses()->first()?->update(['is_default' => true]);
        }

        return back()->with('success', 'ঠিকানা মুছে ফেলা হয়েছে।');
    }

    public function setDefaultAddress(Address $address): RedirectResponse
    {
        $this->authorizeAddress($address);
        Auth::user()->addresses()->update(['is_default' => false]);
        $address->update(['is_default' => true]);

        return back()->with('success', 'মূল ঠিকানা পরিবর্তন হয়েছে।');
    }

    public function showPhoneRegister(): View|RedirectResponse
    {
        if (! $this->phoneOtpAvailable()) {
            return redirect()->route('account.login')->with('error', 'ফোন দিয়ে লগইন এখন বন্ধ আছে। ইমেইল দিয়ে লগইন করুন।');
        }

        return view('storefront.account.phone-register');
    }

    public function registerPhone(Request $request): RedirectResponse
    {
        if (! $this->phoneOtpAvailable()) {
            return redirect()->route('account.login')->with('error', 'ফোন দিয়ে লগইন এখন বন্ধ আছে। ইমেইল দিয়ে লগইন করুন।');
        }

        User::mergeNormalizedPhoneInput($request);

        $data = $request->validate([
            'phone' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $normalized = User::requireValidBangladeshMobile($data['phone'], 'phone');

        if (User::query()->where('phone', $normalized)->exists()) {
            throw ValidationException::withMessages(['phone' => 'এই মোবাইল নম্বর দিয়ে আগেই অ্যাকাউন্ট খোলা হয়েছে।']);
        }

        $otp = $this->otpService->generate($normalized, self::OTP_PURPOSE_REGISTER);
        $this->otpService->send($otp);

        session([
            'phone_register' => [
                'phone' => $normalized,
                'name' => $data['name'],
            ],
        ]);

        return back()->with('success', 'আপনার মোবাইলে একটি কোড পাঠানো হয়েছে।');
    }

    public function verifyPhoneOtp(Request $request): RedirectResponse
    {
        if (! $this->phoneOtpAvailable()) {
            return redirect()->route('account.login')->with('error', 'ফোন দিয়ে লগইন এখন বন্ধ আছে। ইমেইল দিয়ে লগইন করুন।');
        }

        $data = $request->validate([
            'code' => ['required', 'string', 'max:10'],
        ]);

        $pending = session('phone_register');
        if (! $pending) {
            return redirect()->route('account.phone.register')->withErrors(['phone' => 'অনুগ্রহ করে আবার শুরু করুন।']);
        }

        $this->otpService->verify($pending['phone'], $data['code'], self::OTP_PURPOSE_REGISTER);

        $user = User::create([
            'name' => $pending['name'],
            'phone' => $pending['phone'],
            'phone_verified_at' => now(),
            'email' => $pending['phone'] . '@phone.eternitygadget.local',
            'password' => Str::password(32),
            'role' => User::ROLE_CUSTOMER,
            'is_admin' => false,
        ]);

        session()->forget('phone_register');
        Auth::login($user);
        $request->session()->regenerate();
        $this->afterLogin($user);

        return redirect()->route('account')->with('success', 'রেজিস্ট্রেশন সম্পন্ন হয়েছে।');
    }

    public function showPhoneLogin(): View|RedirectResponse
    {
        if (! $this->phoneOtpAvailable()) {
            return redirect()->route('account.login')->with('error', 'ফোন দিয়ে লগইন এখন বন্ধ আছে। ইমেইল দিয়ে লগইন করুন।');
        }

        return view('storefront.account.phone-login');
    }

    public function loginPhone(Request $request): RedirectResponse
    {
        if (! $this->phoneOtpAvailable()) {
            return redirect()->route('account.login')->with('error', 'ফোন দিয়ে লগইন এখন বন্ধ আছে। ইমেইল দিয়ে লগইন করুন।');
        }

        if ($request->filled('code')) {
            return $this->verifyPhoneLogin($request);
        }

        $this->throttleAuth('phone-login:' . $request->ip(), 5, 60);

        User::mergeNormalizedPhoneInput($request);

        $data = $request->validate(['phone' => ['required', 'string', 'max:50']]);
        $normalized = User::requireValidBangladeshMobile($data['phone'], 'phone');

        if (! User::query()->where('phone', $normalized)->exists()) {
            RateLimiter::hit('phone-login:' . $request->ip(), 60);
            throw ValidationException::withMessages(['phone' => 'এই মোবাইল নম্বরে কোনো অ্যাকাউন্ট নেই।']);
        }

        $otp = $this->otpService->generate($normalized, self::OTP_PURPOSE_LOGIN);
        $this->otpService->send($otp);
        session(['phone_login' => $normalized]);

        return back()->with('success', 'আপনার মোবাইলে একটি কোড পাঠানো হয়েছে।');
    }

    public function showRegister(): View
    {
        return view('storefront.account.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $this->throttleAuth('register:' . $request->ip(), 5, 60);

        User::mergeNormalizedPhoneInput($request);

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
            'phone' => ['required', 'string', 'max:50', 'unique:users,phone'],
            'marketing_opt_in' => ['sometimes', 'boolean'],
            'captcha' => ['required', 'string', 'max:10'],
            'agree' => ['accepted'],
        ]);

        $expectedCaptcha = strtolower((string) session('register_captcha', ''));
        if ($expectedCaptcha === '' || strtolower($data['captcha']) !== $expectedCaptcha) {
            throw ValidationException::withMessages([
                'captcha' => 'ক্যাপচা কোডটি ভুল হয়েছে, আবার চেষ্টা করুন।',
            ]);
        }

        session()->forget('register_captcha');

        $data['name'] = trim($data['first_name'] . ' ' . $data['last_name']);
        unset($data['first_name'], $data['last_name'], $data['agree'], $data['captcha']);

        $data['phone'] = User::requireValidBangladeshMobile($data['phone'], 'phone');

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'phone' => $data['phone'],
            'marketing_opt_in' => $request->boolean('marketing_opt_in'),
            'role' => User::ROLE_CUSTOMER,
            'is_admin' => false,
        ]);

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();
        $this->afterLogin($user);
        RateLimiter::clear('register:' . $request->ip());

        return redirect()->route('account')->with('success', 'স্বাগতম! আপনার অ্যাকাউন্ট খোলা হয়েছে।');
    }

    public function showLogin(): View
    {
        return view('storefront.account.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $this->throttleAuth('login:' . $request->ip(), 5, 60);

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            $this->afterLogin(Auth::user());
            RateLimiter::clear('login:' . $request->ip());

            return redirect()->intended(route('account'));
        }

        RateLimiter::hit('login:' . $request->ip(), 60);

        throw ValidationException::withMessages([
            'email' => 'ইমেইল বা পাসওয়ার্ড ভুল হয়েছে।',
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    public function logoutAllDevices(Request $request): RedirectResponse
    {
        $userId = Auth::id();
        $currentSessionId = $request->session()->getId();

        DB::table('sessions')
            ->where('user_id', $userId)
            ->where('id', '!=', $currentSessionId)
            ->delete();

        return back()->with('success', 'অন্য সব ডিভাইস থেকে লগআউট করা হয়েছে।');
    }

    public function showForgotPassword(): View
    {
        return view('storefront.account.forgot-password');
    }

    public function forgotPassword(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? back()->with('success', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    public function showResetPassword(Request $request, string $token): View
    {
        return view('storefront.account.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => $password])->save();
                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('account.login')->with('success', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    public function verifyEmailNotice(): View
    {
        return view('storefront.account.verify-email');
    }

    public function resendVerificationEmail(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('account');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', 'যাচাইয়ের লিংক পাঠানো হয়েছে।');
    }

    public function convertGuest(Request $request, Order $order): RedirectResponse
    {
        if ($order->user_id) {
            return redirect()->route('order.confirmed', $order);
        }

        abort_unless(
            session('last_order_id') === $order->id
            || in_array($order->id, array_values(session('checkout_idempotency_orders', [])), true),
            403
        );

        User::mergeNormalizedPhoneInput($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
            'phone' => ['required', 'string', 'max:50', 'unique:users,phone'],
            'marketing_opt_in' => ['sometimes', 'boolean'],
        ]);

        $data['phone'] = User::requireValidBangladeshMobile($data['phone'], 'phone');

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'phone' => $data['phone'],
            'marketing_opt_in' => $request->boolean('marketing_opt_in'),
            'role' => User::ROLE_CUSTOMER,
            'is_admin' => false,
        ]);

        $order->update(['user_id' => $user->id]);
        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();
        $this->afterLogin($user);

        return redirect()->signedRoute('order.confirmed', ['order' => $order])->with('success', 'অ্যাকাউন্ট খোলা হয়েছে এবং অর্ডারটি যুক্ত হয়েছে।');
    }

    public function notifications(): View
    {
        return view('storefront.account.notifications', [
            'notifications' => Auth::user()->notifications()->latest()->paginate(20),
        ]);
    }

    public function markNotificationRead(UserNotification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === Auth::id(), 403);
        $notification->markAsRead();

        return back()->with('success', 'নোটিফিকেশন পড়া হয়েছে।');
    }

    public function markAllNotificationsRead(): RedirectResponse
    {
        Auth::user()->notifications()->whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('success', 'সব নোটিফিকেশন পড়া হয়েছে।');
    }

    public function compare(): View
    {
        $ids = array_slice(array_values(session('compare', [])), 0, self::COMPARE_LIMIT);
        $products = Product::query()
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->with(['images', 'options'])
            ->get()
            ->sortBy(fn (Product $p) => array_search($p->id, $ids, true))
            ->values();

        return view('storefront.compare', compact('products'));
    }

    public function contact(): View
    {
        return view('storefront.account.contact');
    }

    public function orderHistory(): View
    {
        return view('storefront.account.order-history', [
            'orders' => Auth::user()->orders()->with('items')->latest()->paginate(15),
        ]);
    }

    public function guestOrderTracking(): View
    {
        $order = null;

        if ($orderId = session('tracked_order')) {
            $order = Order::query()->with('items')->find($orderId);
        }

        return view('storefront.account.guest-tracking', compact('order'));
    }

    public function lookupGuestOrder(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order_number' => ['required', 'string', 'max:50'],
            'contact' => ['required', 'string', 'max:255'],
        ], [], ['order_number' => 'অর্ডার নম্বর', 'contact' => 'মোবাইল নম্বর']);

        $number = strtoupper(trim(ltrim(trim($data['order_number']), '#')));
        $contact = trim($data['contact']);

        $query = Order::query()->where('order_number', $number);
        if (str_contains($contact, '@')) {
            $query->where('customer_email', $contact);
        } else {
            $phone = \App\Models\User::normalizePhone($contact);
            $query->where('customer_phone', $phone ?: '__none__');
        }
        $order = $query->first();

        if (! $order) {
            return back()->withErrors(['order_number' => 'এই অর্ডার নম্বর ও মোবাইল নম্বর দিয়ে কোনো অর্ডার পাওয়া যায়নি। অর্ডার কনফার্মেশনে দেওয়া নম্বরটি দেখে আবার লিখুন।'])->withInput();
        }

        if ($order->user_id === null) {
            session(['tracked_order' => $order->id]);
        }

        return redirect()->to(\Illuminate\Support\Facades\URL::temporarySignedRoute('orders.show', now()->addHours(2), ['order' => $order]));
    }

    public function rewardPoints(\App\Services\RewardService $rewards): View|RedirectResponse
    {
        if (! $rewards->enabled()) {
            return redirect()->route('account');
        }

        $user = Auth::user();

        return view('storefront.account.reward-points', [
            'points' => $rewards->balance($user),
            'pointValue' => $rewards->pointValue(),
            'earnPer100' => $rewards->earnPer100(),
            'minRedeem' => $rewards->minRedeem(),
            'maxPercent' => $rewards->maxRedeemPercent(),
            'history' => \App\Models\RewardPointTransaction::query()->where('user_id', $user->id)->with('order:id,order_number')->latest()->limit(50)->get(),
        ]);
    }

    public function newsletterSettings(): View
    {
        return view('storefront.account.newsletter', [
            'user' => Auth::user(),
        ]);
    }

    public function updateNewsletterSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'marketing_opt_in' => ['sometimes', 'boolean'],
        ]);

        Auth::user()->update([
            'marketing_opt_in' => $request->boolean('marketing_opt_in'),
        ]);

        return back()->with('success', 'নিউজলেটার সেটিং আপডেট হয়েছে।');
    }

    public function storeContact(Request $request): RedirectResponse
    {
        User::mergeNormalizedPhoneInput($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $data['phone'] = User::requireValidBangladeshMobile($data['phone'], 'phone');

        ContactMessage::create($data);

        return back()->with('success', 'ধন্যবাদ! আমরা শীঘ্রই আপনার সাথে যোগাযোগ করব।');
    }

    private function verifyPhoneLogin(Request $request): RedirectResponse
    {
        User::mergeNormalizedPhoneInput($request);

        $data = $request->validate([
            'phone' => ['required', 'string', 'max:50'],
            'code' => ['required', 'string', 'max:10'],
        ]);

        $normalized = User::requireValidBangladeshMobile($data['phone'], 'phone');

        $this->otpService->verify($normalized, $data['code'], self::OTP_PURPOSE_LOGIN);

        $user = User::query()->where('phone', $normalized)->firstOrFail();
        session()->forget('phone_login');

        Auth::login($user);
        $request->session()->regenerate();
        $this->afterLogin($user);
        RateLimiter::clear('phone-login:' . $request->ip());

        return redirect()->intended(route('account'));
    }

    private function afterLogin(User $user): void
    {
        $this->cartService->mergeGuestToUser($user->id);
        $this->mergeGuestWishlist($user->id);
    }

    private function mergeGuestWishlist(int $userId): void
    {
        $sessionId = session()->getId();

        WishlistItem::query()
            ->where('session_id', $sessionId)
            ->each(function (WishlistItem $item) use ($userId) {
                WishlistItem::updateOrCreate(
                    ['user_id' => $userId, 'product_id' => $item->product_id],
                    ['session_id' => null]
                );
                $item->delete();
            });
    }

    private function validateAddress(Request $request, ?Address $address = null): array
    {
        User::mergeNormalizedPhoneInput($request);

        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'line1' => ['required', 'string', 'max:255'],
            'line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:2'],
            'is_default' => ['sometimes', 'boolean'],
        ]);

        $data['phone'] = User::requireValidBangladeshMobile($data['phone'], 'phone');

        return $data;
    }

    private function authorizeAddress(Address $address): void
    {
        abort_unless($address->user_id === Auth::id(), 403);
    }

    private function throttleAuth(string $key, int $maxAttempts, int $decaySeconds): void
    {
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'email' => "Too many attempts. Please try again in {$seconds} seconds.",
            ]);
        }
    }

    /** Phone OTP needs a real SMS gateway. Until one is connected (Website Settings → sms_enabled = 1) the phone pages stay closed. */
    private function phoneOtpAvailable(): bool
    {
        return setting('sms_enabled', '0') === '1';
    }
}
