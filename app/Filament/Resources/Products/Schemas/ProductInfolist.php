<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Overview')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('name')
                            ->size('lg')
                            ->weight('bold')
                            ->columnSpan(2),
                        TextEntry::make('category.name')
                            ->label('Category')
                            ->badge()
                            ->placeholder('—'),
                        TextEntry::make('sku')
                            ->label('SKU')
                            ->placeholder('—'),
                        TextEntry::make('slug')
                            ->placeholder('—')
                            ->columnSpan(2),
                        IconEntry::make('is_active')
                            ->label('Active')
                            ->boolean(),
                        IconEntry::make('is_featured')
                            ->label('Featured')
                            ->boolean(),
                        IconEntry::make('is_new')
                            ->label('New')
                            ->boolean(),
                        IconEntry::make('is_clearance')
                            ->label('Clearance')
                            ->boolean(),
                    ]),

                Section::make('Pricing & Stock')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('price')
                            ->formatStateUsing(fn ($state) => money_bd($state))
                            ->label('Regular Price'),
                        TextEntry::make('sale_price')
                            ->formatStateUsing(fn ($state) => filled($state) ? money_bd($state) : null)
                            ->placeholder('—')
                            ->label('Sale Price'),
                        TextEntry::make('stock')
                            ->formatStateUsing(fn ($state) => number_format((int) $state))
                            ->label('Stock Qty'),
                        TextEntry::make('allow_backorder')
                            ->label('Backorder')
                            ->formatStateUsing(fn ($state) => $state ? 'Allowed' : 'No')
                            ->badge()
                            ->color(fn ($state) => $state ? 'warning' : 'gray'),
                    ]),

                Section::make('Product Images')
                    ->schema([
                        RepeatableEntry::make('images')
                            ->label('')
                            ->table([
                                TableColumn::make('Preview')->width('80px'),
                                TableColumn::make('Alt Text'),
                                TableColumn::make('Main')->width('70px'),
                                TableColumn::make('Order')->width('70px'),
                            ])
                            ->schema([
                                ImageEntry::make('image')->disk('public')->height(56),
                                TextEntry::make('alt_text')->placeholder('—'),
                                IconEntry::make('is_primary')->boolean()->label(''),
                                TextEntry::make('sort_order'),
                            ])
                            ->contained(false)
                            ->columnSpanFull(),
                    ]),

                Section::make('Variants')
                    ->schema([
                        RepeatableEntry::make('options')
                            ->label('')
                            ->table([
                                TableColumn::make('Group'),
                                TableColumn::make('Value'),
                                TableColumn::make('Price +/-'),
                                TableColumn::make('Stock'),
                            ])
                            ->schema([
                                TextEntry::make('name'),
                                TextEntry::make('value'),
                                TextEntry::make('price_adjustment')
                                    ->formatStateUsing(fn ($state) => money_bd($state))
                                    ->placeholder('৳0'),
                                TextEntry::make('stock'),
                            ])
                            ->contained(false)
                            ->placeholder('No variants configured.')
                            ->columnSpanFull(),
                    ]),

                Section::make('Descriptions')
                    ->schema([
                        TextEntry::make('short_description')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('description')
                            ->html()
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ]),

                Section::make('SEO')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        TextEntry::make('meta_title')->placeholder('—'),
                        TextEntry::make('created_at')->dateTime()->label('Created'),
                        TextEntry::make('meta_description')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('updated_at')->dateTime()->label('Updated'),
                    ]),
            ]);
    }
}
