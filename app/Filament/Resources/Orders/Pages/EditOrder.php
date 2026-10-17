<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\Concerns\MarksOrderAsViewed;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    use MarksOrderAsViewed;

    protected static string $resource = OrderResource::class;

    private ?string $statusBeforeSave = null;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        $this->statusBeforeSave = $this->record->getOriginal('status');
    }

    /** Same side effects as when the shop changes status anywhere else. */
    protected function afterSave(): void
    {
        /** @var Order $order */
        $order = $this->record;
        $old = $this->statusBeforeSave;

        if ($old === null || $old === $order->status) {
            return;
        }

        $service = app(OrderService::class);

        if ($order->status === Order::STATUS_CANCELLED && $old !== Order::STATUS_CANCELLED) {
            $service->releaseStockOnCancel($order);
        }

        if ($order->status === Order::STATUS_DELIVERED && ! $order->delivered_at) {
            $order->forceFill(['delivered_at' => now()])->saveQuietly();
        }

        $service->sendStatusNotification($order->fresh(['items', 'user']));
    }
}
