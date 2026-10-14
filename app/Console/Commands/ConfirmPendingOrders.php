<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Console\Command;

class ConfirmPendingOrders extends Command
{
    protected $signature = 'orders:confirm-pending';

    protected $description = 'Move pending orders past the cancel window into processing';

    public function handle(OrderService $orderService): int
    {
        $count = 0;

        Order::query()
            ->where('status', Order::STATUS_PENDING)
            ->where('created_at', '<=', now()->subMinutes(Order::CANCEL_WINDOW_MINUTES))
            ->orderBy('id')
            ->each(function (Order $order) use ($orderService, &$count) {
                if ($orderService->confirmPendingOrder($order)) {
                    $count++;
                }
            });

        $this->info("Confirmed {$count} pending order(s).");

        return self::SUCCESS;
    }
}
