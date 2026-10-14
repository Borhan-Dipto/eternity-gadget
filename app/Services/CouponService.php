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
            throw ValidationException::withMessages(['coupon' => 'This coupon code is invalid or expired.']);
        }

        if ($coupon->min_order !== null && $subtotal < (float) $coupon->min_order) {
            throw ValidationException::withMessages([
                'coupon' => 'Minimum order amount for this coupon is ' . money_bd($coupon->min_order) . '.',
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
            return round($subtotal * ((float) $coupon->value / 100), 2);
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
                'coupon' => 'One or more coupons cannot be combined with other promotions.',
            ]);
        }
    }
}
