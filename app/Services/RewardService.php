<?php

namespace App\Services;

use App\Models\Order;
use App\Models\RewardPointTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Reward points, fully controlled from Admin → Shop Settings.
 *
 * Earn: when an order is delivered, the customer gets points for what they paid for products
 *       (not delivery/tax). Taken back if the order is later moved away from "delivered".
 * Use:  at checkout a logged-in customer can pay part of an order with points
 *       (limited by a minimum balance and a maximum % of the order). Returned if the order is cancelled.
 */
class RewardService
{
    public function enabled(): bool
    {
        return setting('rewards_enabled', '1') === '1';
    }

    public function earnPer100(): float
    {
        return max(0, (float) setting('rewards_earn_per_100', '1'));
    }

    public function pointValue(): float
    {
        return max(0, (float) setting('rewards_point_value', '1'));
    }

    public function minRedeem(): int
    {
        return max(0, (int) setting('rewards_min_redeem', '50'));
    }

    public function maxRedeemPercent(): float
    {
        return min(100, max(0, (float) setting('rewards_max_redeem_percent', '10')));
    }

    public function balance(?User $user): int
    {
        if (! $user) {
            return 0;
        }

        return (int) RewardPointTransaction::query()->where('user_id', $user->id)->sum('points');
    }

    /** Points a customer would earn for paying this much for products. */
    public function pointsForAmount(float $amount): int
    {
        if (! $this->enabled() || $amount <= 0) {
            return 0;
        }

        return (int) floor($amount / 100 * $this->earnPer100());
    }

    /** How many points this customer may use on an order with this product subtotal. */
    public function redeemablePoints(?User $user, float $subtotal): int
    {
        if (! $user || ! $this->enabled() || $this->pointValue() <= 0) {
            return 0;
        }

        $balance = $this->balance($user);
        if ($balance <= 0 || $balance < $this->minRedeem()) {
            return 0;
        }

        $maxByPercent = (int) floor(($subtotal * $this->maxRedeemPercent() / 100) / $this->pointValue());

        return max(0, min($balance, $maxByPercent));
    }

    public function discountFor(int $points): float
    {
        return round($points * $this->pointValue(), 2);
    }

    /** Take points from the customer for a new order. Call inside the order DB transaction. */
    public function redeemForOrder(Order $order, User $user, int $points): void
    {
        if ($points <= 0) {
            return;
        }

        // Lock the customer's row so two orders at the same moment cannot spend the same points.
        User::query()->whereKey($user->id)->lockForUpdate()->first();

        if ($points > $this->balance($user)) {
            throw ValidationException::withMessages(['use_points' => 'আপনার পর্যাপ্ত পয়েন্ট নেই। পেজটি রিফ্রেশ করে আবার চেষ্টা করুন।']);
        }

        RewardPointTransaction::create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'points' => -$points,
            'type' => RewardPointTransaction::TYPE_REDEEM,
            'note' => 'অর্ডার ' . $order->order_number . ' এ ব্যবহার',
        ]);
    }

    /** Keep earned / used points correct whenever an order's status changes. Safe to call many times. */
    public function syncOrder(Order $order): void
    {
        if (! $order->user_id) {
            return;
        }

        DB::transaction(function () use ($order) {
            $rows = RewardPointTransaction::query()->where('order_id', $order->id)->lockForUpdate()->get();

            // 1) Points earned on delivery
            $earnedNet = (int) $rows->whereIn('type', [RewardPointTransaction::TYPE_EARN, RewardPointTransaction::TYPE_REVERSE])->sum('points');
            if ($order->status === Order::STATUS_DELIVERED && $earnedNet === 0) {
                $paidForProducts = (float) $order->subtotal - (float) $order->discount - (float) $order->reward_discount;
                $points = $this->pointsForAmount($paidForProducts);
                if ($points > 0) {
                    RewardPointTransaction::create([
                        'user_id' => $order->user_id, 'order_id' => $order->id, 'points' => $points,
                        'type' => RewardPointTransaction::TYPE_EARN, 'note' => 'অর্ডার ' . $order->order_number . ' ডেলিভারি',
                    ]);
                }
            } elseif ($order->status !== Order::STATUS_DELIVERED && $earnedNet > 0) {
                RewardPointTransaction::create([
                    'user_id' => $order->user_id, 'order_id' => $order->id, 'points' => -$earnedNet,
                    'type' => RewardPointTransaction::TYPE_REVERSE, 'note' => 'অর্ডার ' . $order->order_number . ' এর অবস্থা পরিবর্তন',
                ]);
            }

            // 2) Points used on the order come back if it is cancelled
            $usedNet = (int) $rows->whereIn('type', [RewardPointTransaction::TYPE_REDEEM, RewardPointTransaction::TYPE_REFUND])->sum('points');
            if ($order->status === Order::STATUS_CANCELLED && $usedNet < 0) {
                RewardPointTransaction::create([
                    'user_id' => $order->user_id, 'order_id' => $order->id, 'points' => -$usedNet,
                    'type' => RewardPointTransaction::TYPE_REFUND, 'note' => 'অর্ডার ' . $order->order_number . ' বাতিল — পয়েন্ট ফেরত',
                ]);
            }
        });
    }
}
