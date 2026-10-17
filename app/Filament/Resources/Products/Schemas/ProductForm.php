<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\DeviceModel;
use App\Models\ProductOption;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            FileUpload::make('product_images')
                ->label('Product Images')
                ->helperText('Add as many photos as you like (up to 10 MB each). Big phone photos are shrunk automatically before upload. The first photo is the main one; drag to reorder. To show a photo when a customer picks a colour/model, add it on that colour/model row below, or tag it under "Images" after saving.')
                ->image()
                ->multiple()
                ->reorderable()
                ->appendFiles()
                ->panelLayout('grid')
                ->maxSize(10240)
                ->imageResizeMode('contain')
                ->imageResizeTargetWidth('1600')
                ->imageResizeTargetHeight('1600')
                ->imageResizeUpscale(false)
                ->disk('public')
                ->directory('products')
                ->visibility('public')
                ->columnSpanFull(),

            Select::make('category_id')->label('Category')->relationship('category', 'name')->searchable()->preload(),

            TextInput::make('name')->required()->maxLength(255)->live(onBlur: true)->afterStateUpdated(fn ($state, $set) => $set('slug', Str::limit(Str::slug($state), 255, ''))),

            TextInput::make('slug')->required()->maxLength(255)->unique(ignoreRecord: true),

            TextInput::make('sku')->label('SKU')->maxLength(255)->unique(ignoreRecord: true),

            Textarea::make('short_description')->label('Short Description')->rows(3)->maxLength(20000)->columnSpanFull(),

            RichEditor::make('description')->label('Full Product Details')->columnSpanFull(),

            TextInput::make('price')->label('Regular Price')->numeric()->minValue(0)->maxValue(9999999999.99)->prefix('৳')->required()->default(0),

            TextInput::make('sale_price')->label('Sale Price')->numeric()->minValue(0)->maxValue(9999999999.99)->prefix('৳'),

            TextInput::make('stock')->label('Stock Quantity')->integer()->minValue(0)->maxValue(4294967295)->required()->default(0),

            Toggle::make('allow_backorder')->label('Allow Backorder'),

            Toggle::make('is_preorder')->label('Preorder Product'),

            Repeater::make('device_options')
                ->relationship(
                    'options',
                    modifyQueryUsing: fn (Builder $query) => $query->whereNotNull('device_model_id'),
                )
                ->label('Compatible Devices')
                ->schema(self::deviceOptionSchema())
                ->columns(2)
                ->columnSpanFull()
                ->addActionLabel('Add compatible device')
                ->defaultItems(0)
                ->helperText('Optional. Shown as pill-shaped choices on the product page (e.g. iPhone 15 Pro Max).'),

            Repeater::make('color_options')
                ->relationship(
                    'options',
                    modifyQueryUsing: fn (Builder $query) => $query
                        ->whereNull('device_model_id')
                        ->where('name', ProductOption::GROUP_COLOR),
                )
                ->label('Available Colors')
                ->schema(self::colorOptionSchema())
                ->columns(2)
                ->columnSpanFull()
                ->addActionLabel('Add color')
                ->defaultItems(0)
                ->helperText('Optional. Shown as pill-shaped color choices on the product page. Color name appears in cart, orders, and invoices.'),

            Repeater::make('variant_options')
                ->relationship(
                    'options',
                    modifyQueryUsing: fn (Builder $query) => $query
                        ->whereNull('device_model_id')
                        ->where('name', '!=', ProductOption::GROUP_COLOR),
                )
                ->label('Other Variants')
                ->schema(self::variantOptionSchema())
                ->columns(2)
                ->columnSpanFull()
                ->addActionLabel('Add variant')
                ->defaultItems(0)
                ->helperText('Optional. Use only for non-color variants such as size or storage.'),

            Toggle::make('is_active')->label('Show on Website')->default(true),

            Toggle::make('is_featured')->label('Featured Product'),

            Toggle::make('is_new')->label('New Arrival')->helperText('Shows first in the New Arrival section on the homepage.'),

            Toggle::make('is_clearance')->label('Clearance Sale')->helperText('Shows in the Clearance Sale section on the homepage.'),

            TextInput::make('meta_title')->label('SEO Title')->maxLength(255),

            Textarea::make('meta_description')->label('SEO Description')->rows(3)->maxLength(20000)->columnSpanFull(),
        ]);
    }

    /**
     * @return array<int, \Filament\Forms\Components\Component>
     */
    private static function deviceOptionSchema(): array
    {
        return [
            self::optionImageField(),
            TextInput::make('value')
                ->label('Device Label')
                ->required()
                ->maxLength(255)
                ->disabled(fn (Get $get): bool => filled($get('device_model_id')))
                ->dehydrated(),
            TextInput::make('price_adjustment')
                ->label('Price Adjustment')
                ->numeric()
                ->minValue(-9999999999.99)
                ->maxValue(9999999999.99)
                ->default(0)
                ->prefix('৳')
                ->helperText('Added to the product base price. Use 0 for the same price.'),
            TextInput::make('stock')
                ->label('Variant Stock')
                ->integer()
                ->minValue(0)
                ->maxValue(4294967295)
                ->default(0),
            Select::make('device_model_id')
                ->label('Compatible Device')
                ->relationship('deviceModel', 'name', fn ($query) => $query->where('is_active', true)->orderBy('brand')->orderBy('name'))
                ->searchable()
                ->preload()
                ->live()
                ->required()
                ->placeholder('Select a device')
                ->helperText('Pick a device from the shared catalog.')
                ->createOptionForm([
                    TextInput::make('brand')->maxLength(100)->placeholder('e.g. Apple'),
                    TextInput::make('name')->label('Device Name')->required()->maxLength(150)->placeholder('e.g. iPhone 17 Pro Max'),
                    TextInput::make('sort_order')->integer()->minValue(0)->default(0),
                    Toggle::make('is_active')->default(true),
                ])
                ->createOptionUsing(function (array $data): int {
                    $baseSlug = Str::slug(($data['brand'] ?? '') . ' ' . $data['name']) ?: Str::slug($data['name']);
                    $baseSlug = Str::limit($baseSlug, 240, '');
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
                })
                ->columnSpanFull(),
            Hidden::make('name')
                ->default('Compatible Device')
                ->dehydrated(),
        ];
    }

    /**
     * @return array<int, \Filament\Forms\Components\Component>
     */
    private static function colorOptionSchema(): array
    {
        return [
            self::optionImageField(),
            TextInput::make('value')
                ->label('Color Name')
                ->required()
                ->maxLength(255)
                ->placeholder('e.g. Midnight Black, Rose Gold')
                ->columnSpanFull(),
            ColorPicker::make('color_hex')
                ->label('Swatch Color')
                ->helperText('Optional. Shows a color dot on the product page pill.'),
            TextInput::make('price_adjustment')
                ->label('Price Adjustment')
                ->numeric()
                ->minValue(-9999999999.99)
                ->maxValue(9999999999.99)
                ->default(0)
                ->prefix('৳'),
            TextInput::make('stock')
                ->label('Variant Stock')
                ->integer()
                ->minValue(0)
                ->maxValue(4294967295)
                ->default(0),
            Hidden::make('name')
                ->default(ProductOption::GROUP_COLOR)
                ->dehydrated(),
        ];
    }

    /**
     * @return array<int, \Filament\Forms\Components\Component>
     */
    private static function variantOptionSchema(): array
    {
        return [
            self::optionImageField(),
            TextInput::make('name')
                ->label('Variant Group')
                ->required()
                ->maxLength(255)
                ->default('Variant')
                ->placeholder('e.g. Size, Storage'),
            TextInput::make('value')
                ->label('Variant Name')
                ->required()
                ->maxLength(255)
                ->placeholder('e.g. XL, 256GB'),
            TextInput::make('price_adjustment')
                ->label('Price Adjustment')
                ->numeric()
                ->minValue(-9999999999.99)
                ->maxValue(9999999999.99)
                ->default(0)
                ->prefix('৳'),
            TextInput::make('stock')
                ->label('Variant Stock')
                ->integer()
                ->minValue(0)
                ->maxValue(4294967295)
                ->default(0),
        ];
    }

    private static function optionImageField(): FileUpload
    {
        return FileUpload::make('image')
            ->label('Photo when chosen (optional)')
            ->helperText('This photo comes up when the customer picks this option.')
            ->image()
            ->disk('public')
            ->directory('products/options')
            ->visibility('public')
            ->maxSize(10240)
            ->imageResizeMode('contain')
            ->imageResizeTargetWidth('1600')
            ->imageResizeTargetHeight('1600')
            ->imageResizeUpscale(false)
            ->columnSpanFull();
    }
}
