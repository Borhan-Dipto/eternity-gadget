<?php

namespace App\Services;

use App\Jobs\SendOrderEmailJob;
use App\Models\Order;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function notify(
        ?User $user,
        string $type,
        string $title,
        ?string $body = null,
        ?array $data = null,
    ): ?UserNotification {
        if (! $user) {
            return null;
        }

        return UserNotification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);
    }

    public function sendOrderEmail(Order $order, string $template = 'confirmation'): void
    {
        SendOrderEmailJob::dispatch($order->id, $template);
    }

    public function sendSms(?string $phone, string $message): void
    {
        if (! $phone) {
            return;
        }

        Log::channel('sms')->info($message, [
            'phone' => app(PhoneService::class)->normalizePhone($phone) ?? $phone,
        ]);
    }
}
