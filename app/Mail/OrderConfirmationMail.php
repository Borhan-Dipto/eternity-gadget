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
            subject: 'অর্ডার কনফার্মেশন — ' . $this->order->order_number,
        );
    }

    public function content(): Content
    {
        $this->order->loadMissing('items');

        return new Content(view: 'emails.order-confirmation', with: ['order' => $this->order]);
    }
}
