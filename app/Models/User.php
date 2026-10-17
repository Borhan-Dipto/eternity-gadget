<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail, \Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication, \Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'role',
        'is_admin',
        'marketing_opt_in',
        'phone_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'app_authentication_secret',
        'app_authentication_recovery_codes',
    ];

    public const ROLE_CUSTOMER = 'customer';

    public const ROLE_STAFF = 'staff';

    public const ROLE_ADMIN = 'admin';

    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_STAFF], true)
            || (bool) $this->is_admin;
    }

    public static function normalizePhone(?string $phone): ?string
    {
        if ($phone === null || $phone === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '880' . substr($digits, 1);
        } elseif (strlen($digits) === 10) {
            $digits = '880' . $digits;
        } elseif (! str_starts_with($digits, '880')) {
            $digits = '880' . $digits;
        }

        return $digits;
    }

    public static function localPhoneDigits(?string $phone): string
    {
        $normalized = self::normalizePhone($phone);

        if (! $normalized) {
            return '';
        }

        return str_starts_with($normalized, '880')
            ? substr($normalized, 2)
            : $normalized;
    }

    public static function isValidBangladeshMobile(?string $phone): bool
    {
        $normalized = self::normalizePhone($phone);

        return $normalized !== null && (bool) preg_match('/^8801\d{9}$/', $normalized);
    }

    public static function requireValidBangladeshMobile(?string $phone, string $attribute = 'phone'): string
    {
        if (! self::isValidBangladeshMobile($phone)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                $attribute => 'Enter a valid Bangladesh mobile number (11 digits after +88).',
            ]);
        }

        return self::normalizePhone($phone);
    }

    public static function mergeNormalizedPhoneInput(\Illuminate\Http\Request $request, string $key = 'phone'): void
    {
        if (! $request->has($key)) {
            return;
        }

        $request->merge([
            $key => self::normalizePhone($request->input($key)) ?? '',
        ]);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(UserNotification::class);
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function giftCards(): HasMany
    {
        return $this->hasMany(GiftCard::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    protected function casts(): array
    {
        return [
            'app_authentication_secret' => 'encrypted',
            'app_authentication_recovery_codes' => 'encrypted:array',
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'marketing_opt_in' => 'boolean',
        ];
    }

    // Admin 2-step login (Filament app authentication)
    public function getAppAuthenticationSecret(): ?string
    {
        return $this->app_authentication_secret;
    }

    public function saveAppAuthenticationSecret(#[\SensitiveParameter] ?string $secret): void
    {
        $this->app_authentication_secret = $secret;
        $this->save();
    }

    public function getAppAuthenticationHolderName(): string
    {
        return $this->email;
    }

    public function getAppAuthenticationRecoveryCodes(): ?array
    {
        return $this->app_authentication_recovery_codes;
    }

    public function saveAppAuthenticationRecoveryCodes(#[\SensitiveParameter] ?array $codes): void
    {
        $this->app_authentication_recovery_codes = $codes;
        $this->save();
    }
}
