<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ProductOption;
use App\Models\Refund;
use App\Models\ReturnRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private CartService $cartService,
        private NotificationService $notificationService,
    ) {}

    public function cancel(Order $order, ?string $reason = null): Order
    {
        $order->confirmIfExpired();

        if (! $order->canCancel()) {
            throw ValidationException::withMessages([
                'order' => 'অর্ডার করার ১০ মিনিট পার হয়ে গেছে, তাই এখন আর বাতিল করা যাবে না। প্রয়োজনে আমাদের কল/WhatsApp করুন।',
            ]);
        }

        return DB::transaction(function () use ($order, $reason) {
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! $order->canCancel()) {
                throw ValidationException::withMessages([
                    'order' => 'এই অর্ডারটি এখন আর বাতিল করা যাবে না।',
                ]);
            }

            $this->releaseStockOnCancel($order);

            $order->update([
                'status' => Order::STATUS_CANCELLED,
                'notes' => trim(($order->notes ?? '') . ($reason ? "\nCancelled: {$reason}" : '')),
            ]);

            $this->sendStatusNotification($order->fresh(['items', 'user']));

            return $order->fresh(['items', 'user']);
        });
    }

    public function confirmPendingOrder(Order $order): bool
    {
        if (! $order->shouldAutoConfirm()) {
            return false;
        }

        $order->update(['status' => Order::STATUS_PROCESSING]);
        $this->sendStatusNotification($order->fresh(['items', 'user']));

        return true;
    }

    public function reorder(Order $order): void
    {
        $order->loadMissing('items.product');

        foreach ($order->items as $item) {
            if (! $item->product || ! $item->product->is_active) {
                continue;
            }

            $optionId = $this->resolveOptionIdFromSummary($item->product_id, $item->option_summary);

            try {
                $this->cartService->add($item->product, $item->quantity, $optionId);
            } catch (ValidationException) {
                continue;
            }
        }
    }

    public function processRefund(Order $order, float $amount, string $method = Refund::METHOD_ORIGINAL, ?ReturnRequest $returnRequest = null): Refund
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Refund amount must be greater than zero.']);
        }

        return DB::transaction(function () use ($order, $amount, $method, $returnRequest) {
            $refund = Refund::create([
                'order_id' => $order->id,
                'return_request_id' => $returnRequest?->id,
                'amount' => $amount,
                'method' => $method,
                'status' => Refund::STATUS_PROCESSED,
            ]);

            $order->update([
                'payment_status' => Order::PAYMENT_STATUS_REFUNDED,
            ]);

            if ($returnRequest) {
                $returnRequest->update([
                    'status' => ReturnRequest::STATUS_COMPLETED,
                    'refund_amount' => $amount,
                ]);
            }

            $this->sendStatusNotification($order->fresh(['items', 'user']));

            return $refund;
        });
    }

    public function releaseStockOnCancel(Order $order): void
    {
        $order->loadMissing('items.product');

        foreach ($order->items as $item) {
            if ($item->option_summary) {
                $option = $this->findOptionFromSummary($item->product_id, $item->option_summary);
                if ($option) {
                    $option->increment('stock', $item->quantity);
                    continue;
                }
            }

            if ($item->product) {
                $item->product->increment('stock', $item->quantity);
            }
        }
    }

    public function sendStatusNotification(Order $order): void
    {
        $title = match ($order->status) {
            Order::STATUS_CANCELLED => 'অর্ডার বাতিল হয়েছে',
            Order::STATUS_PROCESSING => 'অর্ডার প্রসেস করা হচ্ছে',
            Order::STATUS_SHIPPED => 'অর্ডার কুরিয়ারে পাঠানো হয়েছে',
            Order::STATUS_DELIVERED => 'অর্ডার ডেলিভারি হয়েছে',
            default => 'অর্ডার আপডেট',
        };

        $body = sprintf(
            'অর্ডার %s — এখন: %s।',
            $order->order_number,
            $order->statusLabelBn()
        );

        $this->notificationService->notify(
            $order->user,
            'order_status_' . $order->status,
            $title,
            $body,
            ['order_id' => $order->id, 'status' => $order->status]
        );

        if ($order->customer_email) {
            $this->notificationService->sendOrderEmail($order, 'status_update');
        }

        if ($order->customer_phone) {
            $this->notificationService->sendSms($order->customer_phone, $body);
        }
    }

    private function resolveOptionIdFromSummary(int $productId, ?string $optionSummary): ?int
    {
        if (! $optionSummary) {
            return null;
        }

        return $this->findOptionFromSummary($productId, $optionSummary)?->id;
    }

    private function findOptionFromSummary(int $productId, string $optionSummary): ?ProductOption
    {
        if (! str_contains($optionSummary, ': ')) {
            return null;
        }

        [$name, $value] = explode(': ', $optionSummary, 2);

        return ProductOption::query()
            ->where('product_id', $productId)
            ->where('name', $name)
            ->where('value', $value)
            ->first();
    }
}
