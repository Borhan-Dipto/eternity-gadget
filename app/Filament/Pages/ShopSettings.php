<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * One simple page for the shop owner: reward points, free delivery,
 * delivery time, return policy and WhatsApp number.
 */
class ShopSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Shop Settings';

    protected static ?int $navigationSort = -10;

    protected static ?string $title = 'Shop Settings';

    protected static ?string $slug = 'shop-settings';

    /** key => [group, label, type, default] */
    private const FIELDS = [
        'rewards_enabled' => ['rewards', 'Reward points: on (1) / off (0)', 'text', '1'],
        'rewards_earn_per_100' => ['rewards', 'Points earned per ৳100 spent', 'text', '1'],
        'rewards_point_value' => ['rewards', 'Value of 1 point in ৳', 'text', '1'],
        'rewards_min_redeem' => ['rewards', 'Minimum points before they can be used', 'text', '50'],
        'rewards_max_redeem_percent' => ['rewards', 'Max % of an order that points can pay', 'text', '10'],
        'free_delivery_min' => ['delivery', 'Free delivery from (৳, 0 = off)', 'text', '4000'],
        'delivery_time_text' => ['delivery', 'Delivery time (shown to customers)', 'text', ''],
        'return_policy_text' => ['delivery', 'Cancel / return policy (shown to customers)', 'textarea', ''],
        'trust_bar_enabled' => ['delivery', 'Show delivery/return info bar: on (1) / off (0)', 'text', '1'],
        'whatsapp_number' => ['contact', 'WhatsApp number', 'phone', ''],
        'meta_pixel_id' => ['tracking', 'Meta Pixel ID (Facebook)', 'text', ''],
        'meta_capi_token' => ['tracking', 'Meta Conversions API access token', 'text', ''],
        'meta_test_event_code' => ['tracking', 'Meta test event code (only while testing)', 'text', ''],
        'ga4_measurement_id' => ['tracking', 'Google Analytics 4 measurement ID', 'text', ''],
    ];

    private const TOGGLES = ['rewards_enabled', 'trust_bar_enabled'];

    public ?array $data = [];

    protected function getHeaderActions(): array
    {
        $external = \App\Support\ImageLocalizer::status()['external'];

        return [
            Action::make('copyImages')
                ->label("Copy {$external} outside photo(s) to this server")
                ->icon(Heroicon::OutlinedPhoto)
                ->color('warning')
                ->visible($external > 0)
                ->requiresConfirmation()
                ->modalDescription('Some product/category photos are still loaded from the old store\'s server. This downloads them here so they never disappear. It can take a minute.')
                ->action(function (): void {
                    $result = \App\Support\ImageLocalizer::run();
                    $note = Notification::make()->title("Copied {$result['copied']} photo(s)");
                    if ($result['failed']) {
                        $note->warning()->body('Could not download these (please upload new photos): ' . implode(', ', array_slice($result['failed'], 0, 12)))->persistent();
                    } else {
                        $note->success();
                    }
                    $note->send();
                }),
        ];
    }

    public function mount(): void
    {
        $values = SiteSetting::query()->whereIn('key', array_keys(self::FIELDS))->pluck('value', 'key');
        $data = [];
        foreach (self::FIELDS as $key => [, , , $default]) {
            $value = $values[$key] ?? $default;
            $data[$key] = in_array($key, self::TOGGLES, true) ? $value === '1' : $value;
        }
        $this->form->fill($data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Reward points')
                    ->description('Customers earn points when an order is DELIVERED, and can use them at checkout. Keep the numbers low; you can change them any time.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('rewards_enabled')->label('Reward points are ON')->live()->columnSpanFull(),
                        TextInput::make('rewards_earn_per_100')->label('Points earned for every ৳100 spent')->numeric()->minValue(0)->maxValue(100)->step('0.1')->required()
                            ->helperText('Example: 1 → a ৳2,500 order gives 25 points.')->visible(fn (Get $get) => (bool) $get('rewards_enabled')),
                        TextInput::make('rewards_point_value')->label('How many ৳ one point is worth')->numeric()->minValue(0)->maxValue(100)->step('0.1')->required()
                            ->helperText('Example: 1 → 50 points = ৳50 off.')->visible(fn (Get $get) => (bool) $get('rewards_enabled')),
                        TextInput::make('rewards_min_redeem')->label('Minimum points before they can be used')->numeric()->integer()->minValue(0)->required()
                            ->helperText('Customers need at least this many points to use them.')->visible(fn (Get $get) => (bool) $get('rewards_enabled')),
                        TextInput::make('rewards_max_redeem_percent')->label('Points can pay at most this % of an order')->numeric()->minValue(0)->maxValue(100)->suffix('%')->required()
                            ->helperText('Example: 10 → on a ৳3,000 order, at most ৳300 can be paid with points.')->visible(fn (Get $get) => (bool) $get('rewards_enabled')),
                    ]),
                Section::make('Delivery & returns')
                    ->description('Shown on product pages, cart and checkout.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('free_delivery_min')->label('Free delivery when products total at least')->numeric()->minValue(0)->prefix('৳')->required()
                            ->helperText('0 = no free delivery.'),
                        TextInput::make('whatsapp_number')->label('WhatsApp number')->tel()->placeholder('01XXXXXXXXX')->maxLength(20),
                        TextInput::make('delivery_time_text')->label('Delivery time (in Bangla, as customers should read it)')->maxLength(150)->columnSpanFull(),
                        Textarea::make('return_policy_text')->label('Cancel / return policy (in Bangla)')->rows(3)->maxLength(600)->columnSpanFull(),
                        Toggle::make('trust_bar_enabled')->label('Show the delivery / return / WhatsApp info box on product pages')->columnSpanFull(),
                    ]),
                Section::make('Tracking (Facebook & Google)')
                    ->description(fn () => 'Leave a box empty to switch that part off. ' . (($s = \App\Support\MetaConversions::lastStatus()) ? 'Last Conversions API send: ' . ($s['ok'] ? 'OK' : 'FAILED') . ' — ' . $s['event'] . ' at ' . $s['at'] . ' (' . $s['message'] . ')' : 'Conversions API has not sent anything yet.'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('meta_pixel_id')->label('Meta Pixel ID')->regex('/^\d{10,20}$/')->maxLength(20)->placeholder('1803267180870109'),
                        TextInput::make('ga4_measurement_id')->label('Google Analytics 4 ID')->regex('/^G-[A-Z0-9]{4,15}$/')->maxLength(20)->placeholder('G-XXXXXXXXXX'),
                        TextInput::make('meta_capi_token')->label('Conversions API access token')->password()->revealable()->maxLength(500)->columnSpanFull()
                            ->helperText('Events Manager → your pixel → Settings → Conversions API → Generate access token. Sends every order to Meta from the server too (ad blockers cannot stop it).'),
                        TextInput::make('meta_test_event_code')->label('Test event code (optional)')->maxLength(30)->placeholder('TEST12345')
                            ->helperText('Only while checking in Events Manager → Test events. Empty it afterwards.'),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('Save settings')->submit('save')->keyBindings(['mod+s']),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach (self::FIELDS as $key => [$group, $label, $type]) {
            if (! array_key_exists($key, $data)) {
                continue; // hidden while reward points are off: keep the saved value
            }
            $value = $data[$key];
            if (in_array($key, self::TOGGLES, true)) {
                $value = $value ? '1' : '0';
            }
            $setting = SiteSetting::firstOrNew(['key' => $key]);
            $setting->fill([
                'group' => $setting->group ?: $group,
                'label' => $setting->label ?: $label,
                'type' => $setting->type ?: $type,
                'value' => $value === null ? '' : trim((string) $value),
            ])->save();
        }

        Notification::make()->success()->title('Settings saved')->send();
    }
}
