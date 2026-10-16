<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public string $previousStatus,
        public string $newStatus,
    ) {
        $this->order->loadMissing('items');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Order Update — ' . $this->order->order_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '<p>Hi ' . e($this->order->customer_name) . ',</p>'
                . '<p>Your order <strong>' . e($this->order->order_number) . '</strong> '
                . ($this->previousStatus !== ''
                    ? 'changed from <em>' . e($this->previousStatus) . '</em> to <strong>' . e($this->newStatus) . '</strong>.</p>'
                    : 'is now <strong>' . e(ucfirst(str_replace('_', ' ', $this->newStatus))) . '</strong>.</p>'),
        );
    }
}
