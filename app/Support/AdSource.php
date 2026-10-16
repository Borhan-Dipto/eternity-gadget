<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

class AdSource
{
    public const COOKIE = 'eg_src';

    private const DAYS = 30;

    private const PARAMS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'fbclid', 'gclid'];

    public static function captureFrom(Request $request, Response $response): void
    {
        $tagged = collect(self::PARAMS)
            ->mapWithKeys(fn (string $key) => [$key => self::clean($request->query($key))])
            ->filter()
            ->all();

        $existing = self::current($request);
        $referrer = self::externalReferrer($request);

        // A new ad click / tagged link always wins (last-click attribution).
        // An untagged visit from another website only fills in when nothing is stored yet.
        if ($tagged === [] && ($referrer === null || $existing !== [])) {
            return;
        }

        $data = $tagged + [
            'landing_page' => Str::limit($request->fullUrl(), 500, ''),
            'referrer' => $referrer,
            'captured_at' => now()->toIso8601String(),
        ];

        if (isset($tagged['fbclid'])) {
            // Same format the Meta Pixel uses for its _fbc cookie; needed later for Conversions API matching.
            $data['fbc'] = 'fb.1.' . (int) (microtime(true) * 1000) . '.' . $tagged['fbclid'];
        }

        $response->headers->setCookie(new Cookie(
            self::COOKIE,
            json_encode(array_filter($data), JSON_UNESCAPED_SLASHES),
            now()->addDays(self::DAYS),
            '/',
            null,
            $request->isSecure(),
            true,
            false,
            'lax'
        ));
    }

    /** @return array<string, string> */
    public static function current(Request $request): array
    {
        $raw = $request->cookie(self::COOKIE);
        $data = is_string($raw) ? json_decode($raw, true) : null;

        return is_array($data) ? array_map(fn ($v) => (string) $v, $data) : [];
    }

    /**
     * Columns saved on the order. Pixel cookies (_fbp/_fbc) are read too, because the
     * pixel sets them itself and they let Meta match the order to the ad click.
     *
     * @return array<string, string|null>
     */
    public static function forOrder(Request $request): array
    {
        $src = self::current($request);

        return [
            'utm_source' => $src['utm_source'] ?? null,
            'utm_medium' => $src['utm_medium'] ?? null,
            'utm_campaign' => $src['utm_campaign'] ?? null,
            'utm_content' => $src['utm_content'] ?? null,
            'utm_term' => $src['utm_term'] ?? null,
            'fbclid' => $src['fbclid'] ?? null,
            'gclid' => $src['gclid'] ?? null,
            'landing_page' => $src['landing_page'] ?? null,
            'referrer' => $src['referrer'] ?? null,
            'fbp' => self::clean($request->cookie('_fbp'), 120),
            'fbc' => self::clean($request->cookie('_fbc'), 500) ?? ($src['fbc'] ?? null),
            'client_ip' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
        ];
    }

    /** Short, human-readable source label for the admin panel. */
    public static function label(?string $source, ?string $medium, ?string $fbclid, ?string $gclid, ?string $referrer): string
    {
        if ($source) {
            return trim($source . ($medium ? ' / ' . $medium : ''));
        }
        if ($fbclid) {
            return 'facebook / click';
        }
        if ($gclid) {
            return 'google / ads';
        }
        if ($referrer) {
            return (parse_url($referrer, PHP_URL_HOST) ?: $referrer) . ' / referral';
        }

        return 'direct';
    }

    private static function externalReferrer(Request $request): ?string
    {
        $ref = (string) $request->headers->get('referer', '');
        $host = $ref !== '' ? parse_url($ref, PHP_URL_HOST) : null;

        if (! $host || strcasecmp($host, $request->getHost()) === 0) {
            return null;
        }

        return Str::limit($ref, 500, '');
    }

    private static function clean(mixed $value, int $max = 200): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        $value = trim(strip_tags($value));

        return $value === '' ? null : Str::limit($value, $max, '');
    }
}
