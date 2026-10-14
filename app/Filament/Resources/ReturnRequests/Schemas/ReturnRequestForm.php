<?php

namespace App\Filament\Resources\ReturnRequests\Schemas;

use App\Models\ReturnRequest;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ReturnRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('order_id')
                    ->relationship('order', 'id')
                    ->required(),
                Select::make('order_item_id')
                    ->relationship('orderItem', 'id')
                    ->default(null),
                Textarea::make('reason')
                    ->required()
                    ->columnSpanFull(),
                Select::make('status')
                    ->options([
                        ReturnRequest::STATUS_PENDING => 'Pending',
                        ReturnRequest::STATUS_APPROVED => 'Approved',
                        ReturnRequest::STATUS_REJECTED => 'Rejected',
                        ReturnRequest::STATUS_COMPLETED => 'Completed',
                    ])
                    ->required()
                    ->default(ReturnRequest::STATUS_PENDING),
                TextInput::make('refund_amount')
                    ->numeric()
                    ->prefix('৳')
                    ->default(null),
            ]);
    }
}
