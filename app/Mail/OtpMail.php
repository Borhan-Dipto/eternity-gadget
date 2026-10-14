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
            subject: 'Your verification code',
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '<p>Your ' . e($this->purpose) . ' code is:</p>'
                . '<p><strong style="font-size:24px;letter-spacing:4px;">' . e($this->code) . '</strong></p>'
                . '<p>This code expires shortly. Do not share it with anyone.</p>',
        );
    }
}
