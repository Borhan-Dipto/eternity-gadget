<?php

namespace App\Filament\Resources\SiteSettings\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class SiteSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('group')
                ->label('Where this appears')
                ->options([
                    'branding' => 'Website Branding',
                    'theme' => 'Theme Colors',
                    'contact' => 'Contact Details',
                    'footer' => 'Footer',
                    'seo' => 'SEO',
                    'tracking' => 'Tracking & Pixels',
                    'notifications' => 'Order Alerts',
                    'checkout' => 'Checkout',
                    'rewards' => 'Reward Points',
                    'delivery' => 'Delivery & Returns',
                    'general' => 'General',
                ])
                ->required(),
            TextInput::make('label')->label('Admin Label')->required(),
            TextInput::make('key')->label('System Key')->required()->unique(ignoreRecord: true),
            Select::make('type')
                ->options([
                    'text' => 'Short Text',
                    'textarea' => 'Long Text',
                    'image' => 'Image',
                    'color' => 'Color',
                    'url' => 'Link / URL',
                    'phone' => 'Phone Number',
                    'email' => 'Email Address',
                ])
                ->required()
                ->live(),
            FileUpload::make('value')
                ->label('Image')
                ->image()
                ->disk('public')
                ->directory('settings')
                ->visibility('public')
                ->columnSpanFull()
                ->visible(fn (Get $get): bool => $get('type') === 'image'),
            Textarea::make('value')
                ->label('Value')
                ->rows(4)
                ->columnSpanFull()
                ->visible(fn (Get $get): bool => $get('type') !== 'image'),
            TextInput::make('sort_order')->numeric()->default(0),
        ]);
    }
}
