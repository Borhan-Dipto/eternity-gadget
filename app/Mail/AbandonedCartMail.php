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
            subject: 'আপনার কার্টে প্রোডাক্ট রয়ে গেছে',
        );
    }

    public function content(): Content
    {
        $itemCount = count($this->abandonedCart->cart_snapshot ?? []);

        return new Content(
            htmlString: '<p>আপনার কার্টে এখনো ' . $itemCount . 'টি প্রোডাক্ট অপেক্ষা করছে।</p>'
                . '<p><a href="' . url('/checkout') . '">এখানে ক্লিক করে</a> অর্ডারটি সম্পন্ন করুন।</p>',
        );
    }
}
