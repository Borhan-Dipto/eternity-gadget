<?php

namespace Database\Seeders;

use App\Models\Coupon;
use App\Models\Currency;
use App\Models\ShippingMethod;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class EcommerceSeeder extends Seeder
{
    public function run(): void
    {
        ShippingMethod::updateOrCreate(
            ['code' => 'standard'],
            [
                'name' => 'Standard Delivery',
                'base_cost' => 80,
                'cost_per_kg' => 20,
                'min_days' => 3,
                'max_days' => 5,
                'is_active' => true,
            ]
        );

        ShippingMethod::updateOrCreate(
            ['code' => 'express'],
            [
                'name' => 'Express Delivery',
                'base_cost' => 150,
                'cost_per_kg' => 35,
                'min_days' => 1,
                'max_days' => 2,
                'is_active' => true,
            ]
        );

        Currency::updateOrCreate(
            ['code' => 'BDT'],
            [
                'name' => 'Bangladeshi Taka',
                'symbol' => '৳',
                'rate_to_bdt' => 1,
                'is_active' => true,
            ]
        );

        Currency::updateOrCreate(
            ['code' => 'USD'],
            [
                'name' => 'US Dollar',
                'symbol' => '$',
                'rate_to_bdt' => 110,
                'is_active' => true,
            ]
        );

        Coupon::updateOrCreate(
            ['code' => 'WELCOME10'],
            [
                'type' => Coupon::TYPE_PERCENT,
                'value' => 10,
                'min_order' => 500,
                'max_uses' => 1000,
                'used_count' => 0,
                'starts_at' => now()->startOfDay(),
                'ends_at' => now()->addYear(),
                'is_stackable' => false,
                'is_active' => true,
            ]
        );

        $settings = [
            ['key' => 'tax_rate', 'group' => 'checkout', 'label' => 'Tax Rate (%)', 'type' => 'number', 'value' => '5'],
            ['key' => 'delivery_min_days', 'group' => 'checkout', 'label' => 'Default Min Delivery Days', 'type' => 'number', 'value' => '2'],
            ['key' => 'delivery_max_days', 'group' => 'checkout', 'label' => 'Default Max Delivery Days', 'type' => 'number', 'value' => '5'],
            ['key' => 'default_currency', 'group' => 'checkout', 'label' => 'Default Currency', 'type' => 'text', 'value' => 'BDT'],
            ['key' => 'cod_enabled', 'group' => 'checkout', 'label' => 'Cash on Delivery Enabled', 'type' => 'boolean', 'value' => '1'],
        ];

        foreach ($settings as $index => $setting) {
            SiteSetting::updateOrCreate(
                ['key' => $setting['key']],
                [
                    'group' => $setting['group'],
                    'label' => $setting['label'],
                    'type' => $setting['type'],
                    'value' => $setting['value'],
                    'sort_order' => $index,
                ]
            );
        }
    }
}
