<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Photos that follow the customer's choice:
 * - product_options.image: one photo for a colour / model / variant row
 * - product_images.option_ids: tag a gallery photo with the option(s) it shows (e.g. Black + iPhone 17 Pro)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('product_options', 'image')) {
            Schema::table('product_options', fn (Blueprint $table) => $table->string('image')->nullable());
        }
        if (! Schema::hasColumn('product_images', 'option_ids')) {
            Schema::table('product_images', fn (Blueprint $table) => $table->json('option_ids')->nullable());
        }
    }

    public function down(): void
    {
        Schema::table('product_options', fn (Blueprint $table) => $table->dropColumn('image'));
        Schema::table('product_images', fn (Blueprint $table) => $table->dropColumn('option_ids'));
    }
};
