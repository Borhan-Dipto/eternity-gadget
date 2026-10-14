<?php

namespace App\Jobs;

use App\Models\Order;
use App\Notifications\DatabaseNotification;
use App\Notifications\OrderStatusNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

class SendOrderStatusJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
        public string $previousStatus,
        public string $newStatus,
    ) {}

    public function handle(): void
    {
        $email = $this->order->customer_email ?: $this->order->user?->email;

        if ($email) {
            Notification::route('mail', $email)
                ->notify(new OrderStatusNotification($this->order, $this->previousStatus, $this->newStatus));
        }

        if ($this->order->user) {
            DatabaseNotification::notify(
                $this->order->user,
                'order_status',
                'Order ' . $this->order->order_number . ' updated',
                'Status changed from ' . $this->previousStatus . ' to ' . $this->newStatus . '.',
                [
                    'order_id' => $this->order->id,
                    'order_number' => $this->order->order_number,
                    'previous_status' => $this->previousStatus,
                    'status' => $this->newStatus,
                ],
            );
        }
    }
}
