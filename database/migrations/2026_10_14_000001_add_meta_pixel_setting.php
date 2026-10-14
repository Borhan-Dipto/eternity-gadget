<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds an empty "Meta Pixel ID" setting so it shows up in
     * Admin → Site Settings. Paste the Pixel ID there to turn tracking on.
     */
    public function up(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        if (DB::table('site_settings')->where('key', 'meta_pixel_id')->exists()) {
            return;
        }

        DB::table('site_settings')->insert([
            'group' => 'tracking',
            'key' => 'meta_pixel_id',
            'label' => 'Meta Pixel ID (Facebook)',
            'type' => 'text',
            'value' => null,
            'sort_order' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (Schema::hasTable('site_settings')) {
            DB::table('site_settings')->where('key', 'meta_pixel_id')->delete();
        }
    }
};
