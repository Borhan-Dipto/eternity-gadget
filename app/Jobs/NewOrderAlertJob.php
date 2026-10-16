<?php

namespace App\Jobs;

use App\Models\Order;
use App\Support\AdSource;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Tells the shop owner about every new order, instantly.
 * Telegram: set "telegram_bot_token" + "telegram_chat_id" in Admin → Website Settings.
 * Email: set "order_alert_email" (needs working SMTP in .env).
 * Each channel is optional and failures are only logged, never shown to the customer.
 */
class NewOrderAlertJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $orderId) {}

    public function handle(): void
    {
        $order = Order::query()->with('items')->find($this->orderId);
        if (! $order) {
            return;
        }

        $text = $this->message($order);

        $token = trim((string) setting('telegram_bot_token', ''));
        $chatId = trim((string) setting('telegram_chat_id', ''));
        if ($token !== '' && $chatId !== '') {
            try {
                Http::timeout(8)->asForm()->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $text,
                    'disable_web_page_preview' => 'true',
                ])->throw();
            } catch (Throwable $e) {
                Log::warning('Telegram order alert failed', ['order' => $order->order_number, 'error' => $e->getMessage()]);
            }
        }

        $email = trim((string) setting('order_alert_email', ''));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            try {
                Mail::raw($text, function (Message $m) use ($email, $order) {
                    $m->to($email)->subject('New order ' . $order->order_number . ' — ' . money_bd($order->total));
                });
            } catch (Throwable $e) {
                Log::warning('Email order alert failed', ['order' => $order->order_number, 'error' => $e->getMessage()]);
            }
        }
    }

    private function message(Order $order): string
    {
        $lines = [
            '🛒 নতুন অর্ডার ' . $order->order_number,
            'মোট: ' . money_bd($order->total) . ' (' . ($order->payment_method === 'cod' ? 'COD' : $order->payment_method) . ')',
            'নাম: ' . $order->customer_name,
            'ফোন: ' . $order->customer_phone,
            'ঠিকানা: ' . $order->shipping_address,
            '',
        ];

        foreach ($order->items as $item) {
            $lines[] = '• ' . $item->product_name . ($item->option_summary ? ' (' . $item->option_summary . ')' : '') . ' × ' . $item->quantity . ' = ' . money_bd($item->total);
        }

        $lines[] = '';
        $lines[] = 'উৎস: ' . AdSource::label($order->utm_source, $order->utm_medium, $order->fbclid, $order->gclid, $order->referrer)
            . ($order->utm_campaign ? ' | ' . $order->utm_campaign : '');
        if ($order->notes) {
            $lines[] = 'নোট: ' . $order->notes;
        }
        $lines[] = url('/admin/orders/' . $order->id);

        return implode("\n", $lines);
    }
}
