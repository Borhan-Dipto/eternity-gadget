<?php

namespace App\Jobs;

use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendOrderConfirmationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order) {}

    public function handle(): void
    {
        $email = $this->order->customer_email ?: $this->order->user?->email;

        if (! $email) {
            return;
        }

        Mail::to($email)->send(new OrderConfirmationMail($this->order));
    }
}
