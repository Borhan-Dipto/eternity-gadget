<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Privacy / Terms / Returns / Delivery pages still had placeholder text ("can be edited ...").
 * Fill them with real Bangla content, but never overwrite a page the owner already wrote.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['privacy-policy', 'terms-conditions', 'returns', 'delivery-information'] as $slug) {
            $file = database_path("content/{$slug}.html");
            if (! is_file($file)) {
                continue;
            }
            DB::table('pages')
                ->where('slug', $slug)
                ->where(fn ($q) => $q->whereNull('content')->orWhere('content', '')->orWhere('content', 'like', '%can be edited%'))
                ->update(['content' => file_get_contents($file), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // content stays
    }
};
