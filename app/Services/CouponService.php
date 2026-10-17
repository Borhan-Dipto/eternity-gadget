<?php

namespace App\Services;

use App\Models\Coupon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CouponService
{
    /**
     * @return array{coupon: Coupon, discount: float}
     */
    public function validate(string $code, float $subtotal, ?Collection $existingCoupons = null): array
    {
        $coupon = Coupon::query()
            ->whereRaw('LOWER(code) = ?', [strtolower(trim($code))])
            ->first();

        if (! $coupon || ! $coupon->isValid()) {
            throw ValidationException::withMessages(['coupon' => 'কুপন কোডটি সঠিক নয় বা মেয়াদ শেষ।']);
        }

        if ($coupon->min_order !== null && $subtotal < (float) $coupon->min_order) {
            throw ValidationException::withMessages([
                'coupon' => 'এই কুপন ব্যবহার করতে কমপক্ষে ' . money_bd($coupon->min_order) . ' এর প্রোডাক্ট নিতে হবে।',
            ]);
        }

        if ($existingCoupons !== null && $existingCoupons->isNotEmpty()) {
            $this->assertStackable($existingCoupons->push($coupon));
        }

        return [
            'coupon' => $coupon,
            'discount' => $this->apply($coupon, $subtotal),
        ];
    }

    public function apply(Coupon $coupon, float $subtotal): float
    {
        if ($coupon->type === Coupon::TYPE_PERCENT) {
            return round($subtotal * ((float) $coupon->value / 100));
        }

        return min((float) $coupon->value, $subtotal);
    }

    /**
     * @param  Collection<int, Coupon>  $coupons
     */
    public function applyMany(Collection $coupons, float $subtotal): float
    {
        $this->assertStackable($coupons);

        $remaining = $subtotal;
        $totalDiscount = 0.0;

        foreach ($coupons as $coupon) {
            $discount = $this->apply($coupon, $remaining);
            $totalDiscount += $discount;
            $remaining = max(0, $remaining - $discount);
        }

        return round(min($totalDiscount, $subtotal), 2);
    }

    public function incrementUsage(Coupon $coupon): void
    {
        $coupon->increment('used_count');
    }

    /**
     * @param  Collection<int, Coupon>  $coupons
     */
    private function assertStackable(Collection $coupons): void
    {
        if ($coupons->count() <= 1) {
            return;
        }

        if ($coupons->contains(fn (Coupon $coupon) => ! $coupon->is_stackable)) {
            throw ValidationException::withMessages([
                'coupon' => 'এই কুপন অন্য অফারের সাথে একসাথে ব্যবহার করা যাবে না।',
            ]);
        }
    }
}
