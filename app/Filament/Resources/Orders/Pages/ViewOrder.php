<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\Concerns\MarksOrderAsViewed;
use App\Filament\Resources\Orders\OrderResource;
use App\Services\OrderInvoiceService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    use MarksOrderAsViewed;

    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadInvoice')
                ->label('Download Invoice')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function (OrderInvoiceService $invoices) {
                    $order = $this->getRecord();
                    $pdf = $invoices->download($order);

                    return response()->streamDownload(
                        static function () use ($pdf): void {
                            echo $pdf->getContent();
                        },
                        'invoice-'.$order->order_number.'.pdf',
                        ['Content-Type' => 'application/pdf']
                    );
                }),
            EditAction::make(),
        ];
    }
}
