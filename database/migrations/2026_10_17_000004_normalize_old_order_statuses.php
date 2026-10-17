<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The old admin form allowed "confirmed" and "completed", which the shop does not use.
 * They mean "processing" and "delivered".
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('orders')->where('status', 'confirmed')->update(['status' => 'processing']);
        DB::table('orders')->where('status', 'completed')->update(['status' => 'delivered']);
    }

    public function down(): void
    {
        // not reversible: the old values carried no extra meaning
    }
};
