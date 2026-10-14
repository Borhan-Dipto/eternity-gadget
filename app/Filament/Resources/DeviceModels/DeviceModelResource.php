<?php

namespace App\Filament\Resources\DeviceModels;

use App\Filament\Resources\DeviceModels\Pages\CreateDeviceModel;
use App\Filament\Resources\DeviceModels\Pages\EditDeviceModel;
use App\Filament\Resources\DeviceModels\Pages\ListDeviceModels;
use App\Filament\Resources\DeviceModels\Schemas\DeviceModelForm;
use App\Filament\Resources\DeviceModels\Tables\DeviceModelsTable;
use App\Models\DeviceModel;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DeviceModelResource extends Resource
{
    protected static ?string $model = DeviceModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDevicePhoneMobile;

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?string $navigationLabel = 'Device Models';

    protected static ?string $modelLabel = 'Device Model';

    protected static ?string $pluralModelLabel = 'Device Models';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return DeviceModelForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DeviceModelsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDeviceModels::route('/'),
            'create' => CreateDeviceModel::route('/create'),
            'edit' => EditDeviceModel::route('/{record}/edit'),
        ];
    }
}
