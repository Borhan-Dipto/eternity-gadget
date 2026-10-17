<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Models\ProductImage;
use App\Support\MediaUploadLogger;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    protected static ?string $title = 'Product Images';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            FileUpload::make('image')
                ->label('Image')
                ->image()
                ->disk('public')
                ->directory('products')
                ->visibility('public')
                ->required()
                ->columnSpanFull(),
            \Filament\Forms\Components\Select::make('option_ids')
                ->label('This photo shows (optional)')
                ->helperText('Pick the colour and/or model in this photo, e.g. "Black" + "iPhone 17 Pro". When a customer chooses that, this photo comes up.')
                ->multiple()
                ->options(fn () => self::optionLabels($this->getOwnerRecord()))
                ->columnSpanFull(),
            TextInput::make('alt_text')->label('Image Alt Text'),
            Toggle::make('is_primary')->label('Main Product Image')->default(false),
        ]);
    }

    /** @return array<int, string> option id => "Model: iPhone 17 Pro" / "Color: Black" */
    public static function optionLabels($product): array
    {
        return $product->options()->with('deviceModel')->orderBy('sort_order')->get()
            ->mapWithKeys(fn ($o) => [$o->id => ($o->device_model_id ? 'Model' : $o->name) . ': ' . ($o->deviceModel?->name ?? $o->value)])
            ->all();
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('alt_text')
            ->columns([
                ImageColumn::make('image')->disk('public'),
                TextColumn::make('option_ids')->label('Shows')
                    ->state(fn (ProductImage $record) => collect($record->option_ids ?? [])->map(fn ($id) => self::optionLabels($this->getOwnerRecord())[$id] ?? null)->filter()->implode(', '))
                    ->placeholder('All options')->wrap(),
                TextColumn::make('alt_text')->placeholder('—'),
                TextColumn::make('sort_order')->sortable()->label('Order'),
                IconColumn::make('is_primary')->boolean()->label('Main'),
                TextColumn::make('created_at')->dateTime()->sortable()->label('Added'),
            ])
            ->defaultSort('sort_order')
            ->headerActions([
                CreateAction::make()
                    ->label('Add images')
                    ->modalHeading('Add product images')
                    ->schema([
                        FileUpload::make('image')
                            ->label('Images')
                            ->image()
                            ->multiple()
                            ->reorderable()
                            ->disk('public')
                            ->directory('products')
                            ->visibility('public')
                            ->required()
                            ->columnSpanFull()
                            ->helperText('Select multiple files at once.'),
                        TextInput::make('alt_text')->label('Image Alt Text'),
                        Toggle::make('is_primary')->label('Set first upload as main image')->default(false),
                    ])
                    ->using(function (array $data, RelationManager $livewire): ProductImage {
                        $product = $livewire->getOwnerRecord();
                        $paths = MediaUploadLogger::extractPaths($data['image'] ?? null, 'product_images.relation.create');
                        $altText = $data['alt_text'] ?? $product->name;
                        $markPrimary = (bool) ($data['is_primary'] ?? false);
                        $maxSortOrder = (int) $product->images()->max('sort_order');
                        $created = null;

                        foreach ($paths as $index => $path) {
                            $created = $product->images()->create([
                                'image' => $path,
                                'alt_text' => $altText,
                                'sort_order' => $maxSortOrder + 1 + $index,
                                'is_primary' => $markPrimary && $index === 0,
                            ]);
                        }

                        if (! $created) {
                            throw new \RuntimeException('Please upload at least one image.');
                        }

                        return $created;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
