<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Category / menu clean-up found in the audit (only touches rows that still look exactly as found):
 * - "Dongle & Reader", "Apple Pen & S Pen" were under "iPhone Case" -> moved to "Accessories"
 * - "Samsung Authentic Charger" was under "Samsung Case" -> moved to "Charger"
 * - menu "iPhone 15" / "iPhone 14" had no link -> now open their series pages
 * - test menu entries "Sumsunm 26 ultra" / "sumsung" (no link) -> switched off
 * - test product named "18" with no category -> hidden (not deleted)
 */
return new class extends Migration
{
    public function up(): void
    {
        $id = fn (string $slug) => DB::table('categories')->where('slug', $slug)->value('id');
        $now = now();

        if ($acc = $id('accessories')) {
            DB::table('categories')->whereIn('slug', ['dongle-reader', 'apple-pen-s-pen'])->where('parent_id', $id('iphone-case'))
                ->update(['parent_id' => $acc, 'updated_at' => $now]);
        }
        if ($chg = $id('charger')) {
            DB::table('categories')->where('slug', 'samsung-authentic-charger')->where('parent_id', $id('samsung-case'))
                ->update(['parent_id' => $chg, 'updated_at' => $now]);
        }

        foreach (['iPhone 15' => '/category/iphone-15-series', 'iPhone 14' => '/category/iphone-14-series'] as $label => $url) {
            DB::table('menu_items')->whereRaw('TRIM(label) = ?', [$label])->where(fn ($q) => $q->whereNull('url')->orWhere('url', ''))
                ->update(['url' => $url, 'updated_at' => $now]);
        }
        DB::table('menu_items')->whereIn('label', ['Sumsunm 26 ultra', 'sumsung'])->where(fn ($q) => $q->whereNull('url')->orWhere('url', ''))
            ->update(['is_active' => false, 'updated_at' => $now]);

        DB::table('products')->where('name', '18')->where('slug', '18')->whereNull('category_id')
            ->update(['is_active' => false, 'updated_at' => $now]);
    }

    public function down(): void
    {
        // Data tidy-up only; nothing to undo automatically.
    }
};
