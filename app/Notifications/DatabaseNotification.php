<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\UserNotification;

class DatabaseNotification
{
    public static function notify(
        User $user,
        string $type,
        string $title,
        ?string $body = null,
        ?array $data = null,
    ): UserNotification {
        return UserNotification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);
    }
}
