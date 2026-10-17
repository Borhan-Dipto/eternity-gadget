<?php

namespace App\Filament\Resources\RewardPoints\Pages;

use App\Filament\Pages\ShopSettings;
use App\Filament\Resources\RewardPoints\RewardPointResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRewardPoints extends ListRecords
{
    protected static string $resource = RewardPointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('settings')->label('Point settings')->color('gray')->url(ShopSettings::getUrl()),
            CreateAction::make()->label('Give / remove points'),
        ];
    }
}
