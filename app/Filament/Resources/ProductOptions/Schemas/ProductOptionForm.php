<?php

namespace App\Filament\Resources\ProductOptions\Schemas;

use App\Models\DeviceModel;
use App\Models\ProductOption;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductOptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->relationship('product', 'name')
                    ->required(),
                Select::make('option_kind')
                    ->label('Option Type')
                    ->options([
                        'device' => 'Compatible Device',
                        'color' => 'Color',
                        'variant' => 'Other Variant',
                    ])
                    ->default('variant')
                    ->live()
                    ->dehydrated(false)
                    ->afterStateUpdated(function (?string $state, Set $set): void {
                        if ($state === 'color') {
                            $set('name', ProductOption::GROUP_COLOR);
                            $set('device_model_id', null);

                            return;
                        }

                        if ($state === 'device') {
                            $set('name', 'Compatible Device');
                            $set('color_hex', null);

                            return;
                        }

                        $set('name', 'Variant');
                        $set('device_model_id', null);
                        $set('color_hex', null);
                    })
                    ->afterStateHydrated(function (Select $component, ?ProductOption $record): void {
                        if (! $record) {
                            return;
                        }

                        if ($record->isCompatibilityOption()) {
                            $component->state('device');
                        } elseif ($record->isColorOption()) {
                            $component->state('color');
                        } else {
                            $component->state('variant');
                        }
                    }),
                Hidden::make('name')->default('Variant')->dehydrated(),
                TextInput::make('value')
                    ->label(fn (Get $get): string => match ($get('option_kind')) {
                        'color' => 'Color Name',
                        'device' => 'Device Label',
                        default => 'Variant Name',
                    })
                    ->required()
                    ->maxLength(255)
                    ->disabled(fn (Get $get): bool => $get('option_kind') === 'device' && filled($get('device_model_id')))
                    ->dehydrated(),
                ColorPicker::make('color_hex')
                    ->label('Swatch Color')
                    ->visible(fn (Get $get): bool => $get('option_kind') === 'color'),
                TextInput::make('price_adjustment')
                    ->label('Price Adjustment')
                    ->numeric()
                    ->default(0)
                    ->prefix('৳')
                    ->helperText('Added to the product base price.'),
                TextInput::make('stock')
                    ->label('Variant Stock')
                    ->numeric()
                    ->default(0),
                Select::make('device_model_id')
                    ->label('Compatible Device')
                    ->relationship('deviceModel', 'name', fn ($query) => $query->where('is_active', true)->orderBy('brand')->orderBy('name'))
                    ->searchable()
                    ->preload()
                    ->live()
                    ->visible(fn (Get $get): bool => $get('option_kind') === 'device')
                    ->required(fn (Get $get): bool => $get('option_kind') === 'device')
                    ->createOptionForm([
                        TextInput::make('brand')->maxLength(255),
                        TextInput::make('name')->label('Device Name')->required()->maxLength(255),
                        TextInput::make('sort_order')->numeric()->default(0),
                        Toggle::make('is_active')->default(true),
                    ])
                    ->createOptionUsing(function (array $data): int {
                        $baseSlug = Str::slug(($data['brand'] ?? '') . ' ' . $data['name']) ?: Str::slug($data['name']);
                        $slug = $baseSlug ?: 'device-model';
                        $counter = 2;

                        while (DeviceModel::query()->where('slug', $slug)->exists()) {
                            $slug = $baseSlug . '-' . $counter;
                            $counter++;
                        }

                        return DeviceModel::query()->create([
                            'brand' => $data['brand'] ?: null,
                            'name' => $data['name'],
                            'slug' => $slug,
                            'sort_order' => (int) ($data['sort_order'] ?? 0),
                            'is_active' => (bool) ($data['is_active'] ?? true),
                        ])->getKey();
                    })
                    ->afterStateUpdated(function ($state, Set $set): void {
                        if (! $state) {
                            return;
                        }

                        $set('value', DeviceModel::query()->find($state)?->name);
                        $set('name', 'Compatible Device');
                    }),
                TextInput::make('variant_group')
                    ->label('Variant Group')
                    ->maxLength(255)
                    ->default('Variant')
                    ->visible(fn (Get $get): bool => $get('option_kind') === 'variant')
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (?string $state, Set $set) => $set('name', filled($state) ? $state : 'Variant'))
                    ->dehydrated(false)
                    ->afterStateHydrated(function (TextInput $component, ?ProductOption $record): void {
                        if ($record && ! $record->isCompatibilityOption() && ! $record->isColorOption()) {
                            $component->state($record->name);
                        }
                    }),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),
            ]);
    }
}
