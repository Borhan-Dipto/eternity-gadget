<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

/**
 * Shows a warning in the admin panel while the logged-in admin uses an easy-to-guess password.
 * The check is cached per password hash, so it costs nothing after the first page.
 */
class WeakPasswordWarning
{
    private const WEAK = [
        'admin000', 'admin123', 'admin1234', 'admin', 'password', 'password123', '12345678', '123456789',
        '123456', '00000000', 'eternity', 'eternity123', 'eternitygadget', 'imagine123', 'qwerty123',
    ];

    public static function isWeak(?string $hash): bool
    {
        if (! $hash) {
            return false;
        }

        return Cache::rememberForever('weak_pw_' . sha1($hash), function () use ($hash) {
            foreach (self::WEAK as $candidate) {
                if (Hash::check($candidate, $hash)) {
                    return true;
                }
            }

            return false;
        });
    }

    public static function render(): string
    {
        $user = Auth::user();
        if (! $user || ! self::isWeak($user->getAuthPassword())) {
            return '';
        }

        $url = e(filament()->getProfileUrl() ?? '#');

        return '<div style="margin:0 0 16px;padding:14px 16px;border-radius:10px;background:#fef2f2;border:1px solid #fca5a5;color:#991b1b;font-size:14px;line-height:1.5;">'
            . '<strong>Security warning:</strong> this admin account uses an easy-to-guess password. '
            . 'Anyone who guesses it can see customers and change orders. '
            . '<a href="' . $url . '" style="color:#991b1b;font-weight:700;text-decoration:underline;">Change it now</a> '
            . '(at least 12 characters, letters + numbers).</div>';
    }
}
