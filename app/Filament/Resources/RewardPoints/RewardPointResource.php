<?php

namespace App\Filament\Resources\RewardPoints;

use App\Filament\Resources\RewardPoints\Pages\CreateRewardPoint;
use App\Filament\Resources\RewardPoints\Pages\ListRewardPoints;
use App\Models\RewardPointTransaction;
use App\Services\RewardService;
use App\Models\User;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Every point earned / used / returned, plus manual adjustments by the admin.
 * Settings (rates, on/off) live on Settings → Shop Settings.
 */
class RewardPointResource extends Resource
{
    protected static ?string $model = RewardPointTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    protected static string|UnitEnum|null $navigationGroup = 'Customers';

    protected static ?string $navigationLabel = 'Reward Points';

    protected static ?string $modelLabel = 'reward point entry';

    protected static ?string $slug = 'reward-points';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')
                ->label('Customer')
                ->options(fn () => User::query()->orderBy('name')->limit(500)->get()->mapWithKeys(fn (User $u) => [$u->id => trim($u->name . ' — ' . ($u->phone ?: $u->email))]))
                ->searchable()
                ->required()
                ->live()
                ->helperText(fn ($get) => $get('user_id') ? 'Current balance: ' . app(RewardService::class)->balance(User::find($get('user_id'))) . ' points' : null),
            TextInput::make('points')
                ->label('Points to add (use minus to remove, e.g. -20)')
                ->numeric()->integer()->required()->notIn([0])
                ->minValue(-100000)->maxValue(100000),
            TextInput::make('note')->label('Reason (customer can see this)')->maxLength(200)->placeholder('যেমন: বিশেষ উপহার'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Date')->dateTime('d M Y, h:i A')->sortable(),
                TextColumn::make('user.name')->label('Customer')->searchable()
                    ->description(fn (RewardPointTransaction $r) => $r->user?->phone ?: $r->user?->email),
                TextColumn::make('type')->label('What happened')->badge()
                    ->formatStateUsing(fn (string $state) => RewardPointTransaction::typeLabels()[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'earn', 'refund' => 'success',
                        'redeem' => 'warning',
                        'reverse' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('points')->label('Points')->sortable()
                    ->formatStateUsing(fn (int $state) => ($state > 0 ? '+' : '') . $state)
                    ->color(fn (int $state) => $state >= 0 ? 'success' : 'danger')->weight('bold'),
                TextColumn::make('order.order_number')->label('Order')->placeholder('—')->searchable(),
                TextColumn::make('note')->label('Note')->limit(40)->toggleable(),
            ])
            ->filters([
                SelectFilter::make('type')->label('Type')->options(RewardPointTransaction::typeLabels()),
            ]);
    }

    public static function canEdit($record): bool
    {
        return false; // the history must not be rewritten; add a new adjustment instead
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRewardPoints::route('/'),
            'create' => CreateRewardPoint::route('/create'),
        ];
    }
}
