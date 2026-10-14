<?php

namespace App\Filament\Resources\ProductImages\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductImagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('image')->disk('public'),
                TextColumn::make('product.name')
                    ->searchable()
                    ->sortable()
                    ->limit(35),
                TextColumn::make('alt_text')
                    ->searchable()
                    ->limit(30)
                    ->placeholder('—'),
                IconColumn::make('is_primary')
                    ->boolean()
                    ->label('Main'),
                TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable()
                    ->label('Order'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Added'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
