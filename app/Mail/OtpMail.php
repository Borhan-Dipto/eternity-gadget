<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $phone,
        public string $code,
        public string $purpose = 'verification',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'আপনার যাচাইকরণ কোড',
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '<p>আপনার কোড:</p>'
                . '<p><strong style="font-size:24px;letter-spacing:4px;">' . e($this->code) . '</strong></p>'
                . '<p>কোডটি কিছুক্ষণের মধ্যে মেয়াদোত্তীর্ণ হবে। কাউকে এই কোড দেবেন না।</p>',
        );
    }
}
