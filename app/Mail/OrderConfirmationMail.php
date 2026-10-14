<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
        $this->order->loadMissing('items');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Order Confirmation — ' . $this->order->order_number,
        );
    }

    public function content(): Content
    {
        $items = $this->order->items
            ->map(fn ($item) => e($item->product_name) . ' × ' . $item->quantity . ' — ' . money_bd($item->total))
            ->implode('<br>');

        return new Content(
            htmlString: '<p>Thank you for your order, ' . e($this->order->customer_name) . '.</p>'
                . '<p><strong>Order:</strong> ' . e($this->order->order_number) . '</p>'
                . '<p><strong>Total:</strong> ' . money_bd($this->order->total) . '</p>'
                . '<p>' . $items . '</p>',
        );
    }
}
