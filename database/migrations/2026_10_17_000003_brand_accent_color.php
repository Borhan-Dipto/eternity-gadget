<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Menu highlight / link colour: the old green becomes the logo's orange (only if it was never changed). */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('site_settings')->where('key', 'accent_color')->where('value', '#83B735')->update(['value' => '#F1592A', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('site_settings')->where('key', 'accent_color')->where('value', '#F1592A')->update(['value' => '#83B735', 'updated_at' => now()]);
    }
};
