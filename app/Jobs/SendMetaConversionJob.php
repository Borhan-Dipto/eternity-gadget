<?php

namespace App\Jobs;

use App\Support\MetaPixel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/** Sends one event to Meta's Conversions API. A failure is logged, never shown to the customer. */
class SendMetaConversionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    public function __construct(public array $event) {}

    public function handle(): void
    {
        $pixelId = MetaPixel::id();
        $token = trim((string) setting('meta_capi_token', ''));
        if (! $pixelId || $token === '') {
            return;
        }

        $version = trim((string) setting('meta_graph_version', '')) ?: 'v26.0';
        $payload = ['data' => [$this->event], 'access_token' => $token];
        if ($test = trim((string) setting('meta_test_event_code', ''))) {
            $payload['test_event_code'] = $test;
        }

        try {
            $response = Http::timeout(8)->asJson()->post("https://graph.facebook.com/{$version}/{$pixelId}/events", $payload);
            $ok = $response->successful();
            Cache::put('meta_capi_last', [
                'ok' => $ok,
                'event' => $this->event['event_name'] ?? '?',
                'at' => now()->toDateTimeString(),
                'message' => $ok ? 'received: ' . ($response->json('events_received') ?? '?') : (string) ($response->json('error.message') ?? $response->status()),
            ], now()->addDays(30));
            if (! $ok) {
                Log::warning('Meta CAPI rejected event', ['event' => $this->event['event_name'] ?? null, 'status' => $response->status(), 'error' => $response->json('error')]);
            }
        } catch (\Throwable $e) {
            Cache::put('meta_capi_last', ['ok' => false, 'event' => $this->event['event_name'] ?? '?', 'at' => now()->toDateTimeString(), 'message' => $e->getMessage()], now()->addDays(30));
            Log::warning('Meta CAPI send failed', ['message' => $e->getMessage()]);
        }
    }
}
