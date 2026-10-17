<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('order_number')->required(),
            Select::make('status')->options([
                'pending' => 'Pending (new, 10-minute cancel window)',
                'processing' => 'Processing (confirmed by phone)',
                'shipped' => 'Shipped (handed to courier)',
                'delivered' => 'Delivered (customer received it)',
                'cancelled' => 'Cancelled',
            ])->required()
                ->helperText('The customer is notified when you change this. Delivered gives reward points; Cancelled puts the stock back.'),
            Select::make('payment_status')->label('Payment')->options([
                'pending' => 'Not paid yet',
                'paid' => 'Paid',
                'refunded' => 'Refunded',
                'failed' => 'Failed',
            ])->required(),
            TextInput::make('carrier')->label('Courier (e.g. Steadfast, Pathao)')->maxLength(100),
            TextInput::make('tracking_number')->label('Courier tracking number')->maxLength(100),
            TextInput::make('customer_name')->required(),
            TextInput::make('customer_email')->email(),
            TextInput::make('customer_phone')->required(),
            Textarea::make('shipping_address')->required()->columnSpanFull(),
            TextInput::make('subtotal')->numeric()->prefix('৳')->required(),
            TextInput::make('discount')->numeric()->prefix('৳')->required(),
            TextInput::make('shipping_cost')->numeric()->prefix('৳')->required(),
            TextInput::make('total')->numeric()->prefix('৳')->required(),
            Textarea::make('notes')->rows(3)->columnSpanFull(),
        ]);
    }
}