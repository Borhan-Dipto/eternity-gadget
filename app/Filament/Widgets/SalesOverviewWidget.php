<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SalesOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $orderCount = Order::query()->count();
        $revenue = (float) Order::query()
            ->whereNotIn('status', [Order::STATUS_CANCELLED])
            ->sum('total');
        $pendingOrders = Order::query()
            ->where('status', Order::STATUS_PENDING)
            ->count();

        return [
            Stat::make('Total Orders', number_format($orderCount))
                ->description('All time')
                ->descriptionIcon(Heroicon::OutlinedShoppingCart)
                ->color('primary'),
            Stat::make('Revenue', '৳' . number_format($revenue, 2))
                ->description('Excluding cancelled')
                ->descriptionIcon(Heroicon::OutlinedBanknotes)
                ->color('success'),
            Stat::make('Pending Orders', number_format($pendingOrders))
                ->description('Awaiting processing')
                ->descriptionIcon(Heroicon::OutlinedClock)
                ->color($pendingOrders > 0 ? 'warning' : 'gray'),
        ];
    }
}
