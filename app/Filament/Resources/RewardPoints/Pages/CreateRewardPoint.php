<?php

namespace App\Filament\Resources\RewardPoints\Pages;

use App\Filament\Resources\RewardPoints\RewardPointResource;
use App\Models\RewardPointTransaction;
use App\Models\User;
use App\Services\RewardService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateRewardPoint extends CreateRecord
{
    protected static string $resource = RewardPointResource::class;

    protected static ?string $title = 'Give or remove points';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $points = (int) $data['points'];
        $balance = app(RewardService::class)->balance(User::find($data['user_id']));
        if ($points < 0 && $balance + $points < 0) {
            throw ValidationException::withMessages(['data.points' => "This customer only has {$balance} points."]);
        }

        $data['type'] = RewardPointTransaction::TYPE_ADJUST;
        $data['points'] = $points;
        $data['note'] = $data['note'] ?: null;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
