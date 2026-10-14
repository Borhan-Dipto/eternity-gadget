<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

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

        Log::info('Queued order email', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'template' => $this->template,
            'email' => $order->customer_email,
        ]);
    }
}
