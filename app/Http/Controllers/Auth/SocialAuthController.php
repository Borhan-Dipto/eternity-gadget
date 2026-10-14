<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SocialAuthController extends Controller
{
    public function __construct(
        private CartService $cartService,
    ) {}

    public function redirect(string $provider): RedirectResponse
    {
        $this->validateProvider($provider);

        if (class_exists(\Laravel\Socialite\Facades\Socialite::class) && $this->socialiteConfigured($provider)) {
            return \Laravel\Socialite\Facades\Socialite::driver($provider)->redirect();
        }

        return $this->manualOAuthRedirect($provider);
    }

    public function callback(string $provider): RedirectResponse
    {
        $this->validateProvider($provider);

        if (class_exists(\Laravel\Socialite\Facades\Socialite::class) && $this->socialiteConfigured($provider)) {
            $socialUser = \Laravel\Socialite\Facades\Socialite::driver($provider)->user();
            $providerId = (string) $socialUser->getId();
            $email = $socialUser->getEmail();
            $name = $socialUser->getName() ?? $socialUser->getNickname() ?? 'Customer';
        } else {
            [$providerId, $email, $name] = $this->manualOAuthCallback($provider, request());
        }

        $account = SocialAccount::query()
            ->where('provider', $provider)
            ->where('provider_id', $providerId)
            ->first();

        if ($account) {
            Auth::login($account->user);
            $this->afterLogin($account->user);

            return redirect()->intended(route('account'));
        }

        $user = null;
        if ($email) {
            $user = User::query()->where('email', $email)->first();
        }

        if (! $user) {
            $user = User::create([
                'name' => $name,
                'email' => $email ?? "{$providerId}@{$provider}.social.local",
                'password' => Str::password(32),
                'email_verified_at' => $email ? now() : null,
                'role' => User::ROLE_CUSTOMER,
                'is_admin' => false,
            ]);
        }

        SocialAccount::create([
            'user_id' => $user->id,
            'provider' => $provider,
            'provider_id' => $providerId,
        ]);

        Auth::login($user);
        $this->afterLogin($user);

        return redirect()->intended(route('account.dashboard'));
    }

    private function afterLogin(User $user): void
    {
        session()->regenerate();
        $this->cartService->mergeGuestToUser($user->id);
    }

    private function validateProvider(string $provider): void
    {
        abort_unless(in_array($provider, ['google', 'facebook'], true), 404);
    }

    private function socialiteConfigured(string $provider): bool
    {
        $config = config("services.{$provider}");

        return ! empty($config['client_id']) && ! empty($config['client_secret']);
    }

    private function manualOAuthRedirect(string $provider): RedirectResponse
    {
        $config = config("services.{$provider}");

        if (empty($config['client_id']) || empty($config['redirect'])) {
            abort(503, ucfirst($provider) . ' login is not configured.');
        }

        $state = Str::random(40);
        session(['oauth_state' => $state, 'oauth_provider' => $provider]);

        $params = [
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect'],
            'response_type' => 'code',
            'scope' => $provider === 'google' ? 'openid email profile' : 'email,public_profile',
            'state' => $state,
        ];

        $authUrl = $provider === 'google'
            ? 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params)
            : 'https://www.facebook.com/v18.0/dialog/oauth?' . http_build_query($params);

        return redirect()->away($authUrl);
    }

    private function manualOAuthCallback(string $provider, $request): array
    {
        $config = config("services.{$provider}");

        if ($request->input('state') !== session('oauth_state') || session('oauth_provider') !== $provider) {
            abort(403, 'Invalid OAuth state.');
        }

        session()->forget(['oauth_state', 'oauth_provider']);

        if (! $request->filled('code')) {
            abort(400, 'Authorization denied.');
        }

        if ($provider === 'google') {
            $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'code' => $request->input('code'),
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'redirect_uri' => $config['redirect'],
                'grant_type' => 'authorization_code',
            ])->throw()->json();

            $profile = Http::withToken($tokenResponse['access_token'])
                ->get('https://www.googleapis.com/oauth2/v3/userinfo')
                ->throw()
                ->json();

            return [
                (string) ($profile['sub'] ?? ''),
                $profile['email'] ?? null,
                $profile['name'] ?? 'Google User',
            ];
        }

        $tokenResponse = Http::get('https://graph.facebook.com/v18.0/oauth/access_token', [
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'redirect_uri' => $config['redirect'],
            'code' => $request->input('code'),
        ])->throw()->json();

        $profile = Http::get('https://graph.facebook.com/me', [
            'fields' => 'id,name,email',
            'access_token' => $tokenResponse['access_token'],
        ])->throw()->json();

        return [
            (string) ($profile['id'] ?? ''),
            $profile['email'] ?? null,
            $profile['name'] ?? 'Facebook User',
        ];
    }
}
