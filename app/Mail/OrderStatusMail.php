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
            subject: 'অর্ডার আপডেট — ' . $this->order->order_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '<div style="font-family:\'Hind Siliguri\',Arial,sans-serif;font-size:15px;color:#222;line-height:1.6;">'
                . '<p>প্রিয় ' . e($this->order->customer_name) . ',</p>'
                . '<p>আপনার অর্ডার <strong>' . e($this->order->order_number) . '</strong> এর অবস্থা এখন: <strong>'
                . e(\App\Models\Order::statusLabelsBn()[$this->newStatus] ?? $this->newStatus) . '</strong>।</p>'
                . '<p><a href="' . e(route('orders.show', $this->order)) . '">অর্ডারটি দেখুন</a></p>'
                . '<p>যেকোনো প্রয়োজনে কল বা WhatsApp করুন: ' . e(setting('phone', '01867-683114')) . '</p>'
                . '<p>ধন্যবাদ,<br>' . e(setting('site_name', 'Eternity Gadget')) . '</p></div>',
        );
    }
}
