<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\URL;

class Order extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_SHIPPED = 'shipped';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_CANCELLED = 'cancelled';

    public const PAYMENT_STATUS_PENDING = 'pending';

    public const PAYMENT_STATUS_PAID = 'paid';

    public const PAYMENT_STATUS_FAILED = 'failed';

    public const PAYMENT_STATUS_REFUNDED = 'refunded';

    public const CANCEL_WINDOW_MINUTES = 10;

    protected $fillable = [
        'order_number',
        'user_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'shipping_address',
        'payment_method',
        'payment_status',
        'shipping_method',
        'tracking_number',
        'carrier',
        'estimated_delivery',
        'status',
        'subtotal',
        'discount',
        'reward_points_used',
        'reward_discount',
        'coupon_code',
        'shipping_cost',
        'tax_amount',
        'total',
        'notes',
        'delivered_at',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
        'fbclid',
        'gclid',
        'landing_page',
        'referrer',
        'fbp',
        'fbc',
        'client_ip',
        'user_agent',
    ];

    protected static function booted(): void
    {
        // Reward points follow the order status (earn on delivery, give back on cancel).
        static::updated(function (self $order) {
            if ($order->wasChanged('status')) {
                app(\App\Services\RewardService::class)->syncOrder($order);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'reward_discount' => 'decimal:2',
            'reward_points_used' => 'integer',
            'total' => 'decimal:2',
            'estimated_delivery' => 'date',
            'delivered_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function returnRequests(): HasMany
    {
        return $this->hasMany(ReturnRequest::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function adminViews(): HasMany
    {
        return $this->hasMany(OrderAdminView::class);
    }

    public function viewedByAdmin(?int $userId = null): bool
    {
        $userId ??= auth()->id();

        if (! $userId) {
            return false;
        }

        if ($this->relationLoaded('adminViews')) {
            return $this->adminViews->contains('user_id', $userId);
        }

        return $this->adminViews()->where('user_id', $userId)->exists();
    }

    public function canCancel(): bool
    {
        return $this->status === self::STATUS_PENDING && $this->isWithinCancelWindow();
    }

    public function cancelWindowEndsAt(): \Illuminate\Support\Carbon
    {
        return $this->created_at->copy()->addMinutes(self::CANCEL_WINDOW_MINUTES);
    }

    public function isWithinCancelWindow(): bool
    {
        return now()->lt($this->cancelWindowEndsAt());
    }

    public function shouldAutoConfirm(): bool
    {
        return $this->status === self::STATUS_PENDING && ! $this->isWithinCancelWindow();
    }

    public function confirmIfExpired(): self
    {
        if ($this->shouldAutoConfirm()) {
            app(\App\Services\OrderService::class)->confirmPendingOrder($this);
            $this->refresh();
        }

        return $this;
    }

    public function releaseStock(): void
    {
        $this->loadMissing('items.product');

        foreach ($this->items as $item) {
            if ($item->product) {
                $item->product->increment('stock', $item->quantity);
            }
        }
    }

    /**
     * @param  list<int>  $guestSessionOrderIds
     */
    public function isAccessibleTo(?int $userId, array $guestSessionOrderIds = []): bool
    {
        if ($userId && $this->user_id && (int) $this->user_id === (int) $userId) {
            return true;
        }

        if ($this->user_id === null && in_array($this->id, $guestSessionOrderIds, true)) {
            return true;
        }

        return false;
    }

    public function invoiceUrl(): string
    {
        return URL::signedRoute('orders.invoice', ['order' => $this], now()->addDays(30));
    }

    /** Order status in Bangla, for customer pages and emails. */
    public function statusLabelBn(): string
    {
        return self::statusLabelsBn()[$this->status] ?? ucfirst((string) $this->status);
    }

    /** @return array<string, string> */
    public static function statusLabelsBn(): array
    {
        return [
            self::STATUS_PENDING => 'অর্ডার গ্রহণ করা হয়েছে',
            self::STATUS_PROCESSING => 'প্রসেস করা হচ্ছে',
            self::STATUS_SHIPPED => 'কুরিয়ারে পাঠানো হয়েছে',
            self::STATUS_DELIVERED => 'ডেলিভারি সম্পন্ন',
            self::STATUS_CANCELLED => 'বাতিল',
            // older statuses that the previous admin form allowed
            'confirmed' => 'প্রসেস করা হচ্ছে',
            'completed' => 'ডেলিভারি সম্পন্ন',
        ];
    }

    /** Payment status in Bangla. Cash on delivery that is not paid yet reads as "pay on delivery". */
    public function paymentLabelBn(): string
    {
        return match ($this->payment_status) {
            self::PAYMENT_STATUS_PAID => 'পরিশোধিত',
            self::PAYMENT_STATUS_FAILED => 'পেমেন্ট হয়নি',
            self::PAYMENT_STATUS_REFUNDED => 'টাকা ফেরত দেওয়া হয়েছে',
            default => $this->payment_method === 'cod' ? 'ক্যাশ অন ডেলিভারি (পণ্য হাতে পেয়ে টাকা দিন)' : 'বাকি',
        };
    }
}
