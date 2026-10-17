<?php

namespace App\Filament\Resources\OrderItems\Schemas;

use App\Models\OrderItem;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class OrderItemInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                ImageEntry::make('product_image')
                    ->label('Product image')
                    ->disk('public')
                    ->square()
                    ->size(96)
                    ->defaultImageUrl(asset('images/placeholder.svg'))
                    ->getStateUsing(fn (OrderItem $record): ?string => $record->displayImage()),
                TextEntry::make('order.order_number')
                    ->label('Order #'),
                TextEntry::make('product_name')
                    ->label('Product'),
                TextEntry::make('option_summary')
                    ->placeholder('—'),
                TextEntry::make('quantity')
                    ->numeric(),
                TextEntry::make('unit_price')
                    ->money('BDT'),
                TextEntry::make('total')
                    ->money('BDT'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('—'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('—'),
            ]);
    }
}
