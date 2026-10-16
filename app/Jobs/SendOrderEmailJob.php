<?php

namespace App\Jobs;

use App\Mail\OrderConfirmationMail;
use App\Mail\OrderStatusMail;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Emails the customer about their order. Never throws: a mail problem must not break checkout.
 * Nothing is sent while MAIL_MAILER=log (set real SMTP details in .env to turn email on).
 */
class SendOrderEmailJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $orderId,
        public string $template = 'confirmation',
    ) {}

    public function handle(): void
    {
        $order = Order::query()->with('items')->find($this->orderId);

        if (! $order || ! $order->customer_email) {
            return;
        }

        try {
            $mail = $this->template === 'status_update'
                ? new OrderStatusMail($order, '', (string) $order->status)
                : new OrderConfirmationMail($order);

            Mail::to($order->customer_email)->send($mail);
        } catch (Throwable $e) {
            Log::warning('Order email failed', [
                'order' => $order->order_number,
                'template' => $this->template,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
