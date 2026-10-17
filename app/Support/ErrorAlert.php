<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/** Sends a short Telegram message when the site has a real error, so problems are seen before customers complain. */
class ErrorAlert
{
    public static function send(\Throwable $e): void
    {
        try {
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
                || $e instanceof \Illuminate\Validation\ValidationException
                || $e instanceof \Illuminate\Auth\AuthenticationException
                || $e instanceof \Illuminate\Session\TokenMismatchException
                || $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return;
            }

            $token = trim((string) setting('telegram_bot_token', ''));
            $chat = trim((string) setting('telegram_chat_id', ''));
            if ($token === '' || $chat === '') {
                return;
            }

            $key = 'error_alert_' . md5(get_class($e) . $e->getFile() . $e->getLine());
            if (! Cache::add($key, 1, now()->addMinutes(30))) {
                return;
            }

            $where = str_replace(base_path() . '/', '', $e->getFile()) . ':' . $e->getLine();
            $url = app()->runningInConsole() ? 'console' : request()->fullUrl();
            Http::timeout(5)->asForm()->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chat,
                'text' => "⚠️ সাইটে একটি এরর হয়েছে\n" . mb_substr($e->getMessage(), 0, 300) . "\n📍 {$where}\n🔗 {$url}",
                'disable_web_page_preview' => 'true',
            ]);
        } catch (\Throwable) {
            // never let the alert itself break the site
        }
    }
}
