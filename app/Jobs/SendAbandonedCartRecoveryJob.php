<?php

namespace App\Jobs;

use App\Mail\AbandonedCartMail;
use App\Models\AbandonedCart;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendAbandonedCartRecoveryJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public AbandonedCart $abandonedCart) {}

    public function handle(): void
    {
        if ($this->abandonedCart->emailed_at !== null) {
            return;
        }

        $email = $this->abandonedCart->user?->email;

        if (! $email) {
            return;
        }

        Mail::to($email)->send(new AbandonedCartMail($this->abandonedCart));

        $this->abandonedCart->update(['emailed_at' => now()]);
    }
}
