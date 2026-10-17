<?php

namespace App\Support;

use App\Jobs\SendMetaConversionJob;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Meta Conversions API: the same events as the browser Pixel, sent from the server.
 * Ad blockers, iPhone privacy settings and closed tabs cannot stop these, so Meta sees
 * (almost) every order. Browser and server send the SAME event_id, so Meta counts each event once.
 *
 * Turned on by pasting an access token in Admin → Settings → Shop Settings → Tracking.
 */
class MetaConversions
{
    public static function enabled(): bool
    {
        return MetaPixel::enabled() && trim((string) setting('meta_capi_token', '')) !== '';
    }

    /** Browser-side facts about the visitor, captured now (the event is sent after the page is delivered). */
    public static function context(?Request $request = null): array
    {
        $request ??= request();
        $src = AdSource::current($request);

        return array_filter([
            'client_ip_address' => $request->ip(),
            'client_user_agent' => (string) $request->userAgent(),
            'fbp' => $request->cookie('_fbp'),
            'fbc' => $request->cookie('_fbc') ?: ($src['fbc'] ?? null),
            'event_source_url' => $request->fullUrl(),
        ]);
    }

    /** ViewContent / AddToCart / InitiateCheckout for the visitor making the current request. */
    public static function track(string $event, string $eventId, array $customData): void
    {
        if (! static::enabled()) {
            return;
        }

        $user = auth()->user();
        $context = static::context();

        SendMetaConversionJob::dispatch([
            'event_name' => $event,
            'event_id' => $eventId,
            'event_time' => time(),
            'action_source' => 'website',
            'event_source_url' => $context['event_source_url'] ?? url('/'),
            'user_data' => static::userData(
                phone: $user?->phone,
                email: $user?->email,
                name: $user?->name,
                externalId: $user ? 'user_' . $user->id : null,
                context: $context,
            ),
            'custom_data' => static::customData($customData),
        ])->afterResponse();
    }

    /** Purchase: sent as soon as the order is saved, whether or not the thank-you page ever loads. */
    public static function purchase(Order $order): void
    {
        if (! static::enabled()) {
            return;
        }

        $order->loadMissing('items');

        SendMetaConversionJob::dispatch([
            'event_name' => 'Purchase',
            'event_id' => MetaPixel::purchaseEventId($order),
            'event_time' => $order->created_at?->timestamp ?? time(),
            'action_source' => 'website',
            'event_source_url' => $order->landing_page ?: url('/checkout'),
            'user_data' => static::userData(
                phone: $order->customer_phone,
                email: $order->customer_email,
                name: $order->customer_name,
                externalId: $order->user_id ? 'user_' . $order->user_id : null,
                context: array_filter([
                    'client_ip_address' => $order->client_ip,
                    'client_user_agent' => $order->user_agent,
                    'fbp' => $order->fbp,
                    'fbc' => $order->fbc,
                ]),
            ),
            'custom_data' => static::customData(MetaPixel::orderParams($order)),
        ])->afterResponse();
    }

    /**
     * Identifiers are normalised and SHA-256 hashed as Meta requires;
     * IP, browser and the _fbp/_fbc cookies are sent as they are.
     */
    public static function userData(?string $phone, ?string $email, ?string $name, ?string $externalId, array $context): array
    {
        $digits = preg_replace('/\D/', '', (string) $phone);
        if (str_starts_with($digits, '01') && strlen($digits) === 11) {
            $digits = '88' . $digits;
        }
        $parts = trim((string) $name) !== '' ? preg_split('/\s+/u', mb_strtolower(trim((string) $name))) : [];
        $hash = fn (?string $v) => $v !== null && $v !== '' ? hash('sha256', $v) : null;

        return array_filter([
            'ph' => strlen($digits) >= 11 ? [$hash($digits)] : null,
            'em' => $email && filter_var($email, FILTER_VALIDATE_EMAIL) ? [$hash(mb_strtolower(trim($email)))] : null,
            'fn' => isset($parts[0]) ? [$hash($parts[0])] : null,
            'ln' => count($parts) > 1 ? [$hash(end($parts))] : null,
            'country' => [$hash('bd')],
            'external_id' => $externalId ? [$hash($externalId)] : null,
            'client_ip_address' => $context['client_ip_address'] ?? null,
            'client_user_agent' => $context['client_user_agent'] ?? null,
            'fbp' => $context['fbp'] ?? null,
            'fbc' => $context['fbc'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');
    }

    private static function customData(array $params): array
    {
        unset($params['content_category']);

        return array_filter($params, fn ($v) => $v !== null && $v !== '' && $v !== []);
    }

    /** Last result, shown on the Shop Settings page. */
    public static function lastStatus(): ?array
    {
        return Cache::get('meta_capi_last');
    }
}
