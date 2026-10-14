<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('product_options', 'color_hex')) {
            return;
        }

        Schema::table('product_options', function (Blueprint $table) {
            $table->string('color_hex', 7)->nullable()->after('value');
        });
    }

    public function down(): void
    {
        Schema::table('product_options', function (Blueprint $table) {
            $table->dropColumn('color_hex');
        });
    }
};
