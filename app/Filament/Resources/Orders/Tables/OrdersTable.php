<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Models\Order;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                IconColumn::make('viewed')
                    ->label('Seen')
                    ->boolean()
                    ->getStateUsing(fn (Order $record): bool => $record->viewedByAdmin())
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-bell-alert')
                    ->trueColor('success')
                    ->falseColor('warning'),
                ImageColumn::make('product_images')
                    ->label('Products')
                    ->circular()
                    ->stacked()
                    ->limit(3)
                    ->limitedRemainingText()
                    ->getStateUsing(function (Order $record): array {
                        return $record->items
                            ->map(fn ($item) => $item->product?->primaryImage())
                            ->filter()
                            ->unique()
                            ->values()
                            ->all();
                    })
                    ->disk('public')
                    ->defaultImageUrl(asset('images/placeholder.svg')),
                TextColumn::make('order_number')
                    ->searchable()
                    ->sortable()
                    ->weight(fn (Order $record): string => $record->viewedByAdmin() ? 'normal' : 'bold')
                    ->color(fn (Order $record): string => $record->viewedByAdmin() ? 'gray' : 'primary'),
                TextColumn::make('customer_name')
                    ->searchable()
                    ->limit(24),
                TextColumn::make('customer_phone')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Order::STATUS_PENDING => 'warning',
                        Order::STATUS_PROCESSING => 'info',
                        Order::STATUS_SHIPPED => 'primary',
                        Order::STATUS_DELIVERED => 'success',
                        Order::STATUS_CANCELLED => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('payment_status')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total')
                    ->money('BDT')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Ordered'),
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
