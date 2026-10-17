<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;

class PhoneService
{
    public function normalizePhone(?string $phone): ?string
    {
        return User::normalizePhone($phone);
    }

    public function isUniqueForRegistration(?string $phone, ?int $ignoreUserId = null): bool
    {
        $normalized = $this->normalizePhone($phone);

        if (! $normalized) {
            return false;
        }

        $query = User::query()->where('phone', $normalized);

        if ($ignoreUserId) {
            $query->whereKeyNot($ignoreUserId);
        }

        return ! $query->exists();
    }

    /**
     * @return array{warning: bool, message: ?string, match_count: int}
     */
    public function checkSharingWarning(?string $phone, ?int $ignoreUserId = null): array
    {
        $normalized = $this->normalizePhone($phone);

        if (! $normalized) {
            return ['warning' => false, 'message' => null, 'match_count' => 0];
        }

        $userQuery = User::query()->where('phone', $normalized);
        if ($ignoreUserId) {
            $userQuery->whereKeyNot($ignoreUserId);
        }
        $userMatches = $userQuery->count();

        $orderMatches = Order::query()
            ->where(function ($query) use ($normalized) {
                $query->where('customer_phone', $normalized)
                    ->orWhere('customer_phone', 'like', '%' . substr($normalized, -11));
            })
            ->when($ignoreUserId, fn ($query) => $query->where('user_id', '!=', $ignoreUserId))
            ->count();

        $matchCount = $userMatches + $orderMatches;

        if ($matchCount === 0) {
            return ['warning' => false, 'message' => null, 'match_count' => 0];
        }

        return [
            'warning' => true,
            'message' => 'এই মোবাইল নম্বরটি অন্য একটি অ্যাকাউন্টে ব্যবহার হয়েছে।',
            'match_count' => $matchCount,
        ];
    }
}
