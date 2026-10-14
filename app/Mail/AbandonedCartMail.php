<?php

namespace App\Mail;

use App\Models\AbandonedCart;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AbandonedCartMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public AbandonedCart $abandonedCart)
    {
        $this->abandonedCart->loadMissing('user');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You left items in your cart',
        );
    }

    public function content(): Content
    {
        $itemCount = count($this->abandonedCart->cart_snapshot ?? []);

        return new Content(
            htmlString: '<p>You still have ' . $itemCount . ' item(s) waiting in your cart.</p>'
                . '<p><a href="' . url('/cart') . '">Return to your cart</a> to complete checkout.</p>',
        );
    }
}
