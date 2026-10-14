<?php

namespace App\Filament\Resources\ProductOptions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ProductOptionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('product.name')
                    ->label('Product'),
                TextEntry::make('name'),
                TextEntry::make('deviceModel.name')
                    ->label('Compatible Device')
                    ->placeholder('-'),
                TextEntry::make('value'),
                TextEntry::make('price_adjustment')
                    ->numeric(),
                TextEntry::make('stock')
                    ->numeric(),
                TextEntry::make('sort_order')
                    ->numeric(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
