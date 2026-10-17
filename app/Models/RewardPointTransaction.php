<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RewardPointTransaction extends Model
{
    public const TYPE_EARN = 'earn';       // order delivered
    public const TYPE_REVERSE = 'reverse'; // delivered order later cancelled/returned
    public const TYPE_REDEEM = 'redeem';   // used on an order
    public const TYPE_REFUND = 'refund';   // order with used points was cancelled
    public const TYPE_ADJUST = 'adjust';   // changed by admin

    protected $fillable = ['user_id', 'order_id', 'points', 'type', 'note'];

    protected function casts(): array
    {
        return ['points' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return array<string, string> */
    public static function typeLabelsBn(): array
    {
        return [
            self::TYPE_EARN => 'অর্জিত',
            self::TYPE_REVERSE => 'বাতিল (অর্ডার ফেরত)',
            self::TYPE_REDEEM => 'ব্যবহৃত',
            self::TYPE_REFUND => 'ফেরত (অর্ডার বাতিল)',
            self::TYPE_ADJUST => 'অ্যাডমিন পরিবর্তন',
        ];
    }

    /** @return array<string, string> */
    public static function typeLabels(): array
    {
        return [
            self::TYPE_EARN => 'Earned (delivered)',
            self::TYPE_REVERSE => 'Taken back (order undone)',
            self::TYPE_REDEEM => 'Used on order',
            self::TYPE_REFUND => 'Returned (order cancelled)',
            self::TYPE_ADJUST => 'Admin adjustment',
        ];
    }
}
