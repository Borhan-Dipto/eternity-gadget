<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Where each order came from (ad / campaign / click IDs) + data Meta needs to match it. */
    public function up(): void
    {
        if (Schema::hasColumn('orders', 'utm_source')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->string('utm_source', 200)->nullable()->index();
            $table->string('utm_medium', 200)->nullable();
            $table->string('utm_campaign', 200)->nullable()->index();
            $table->string('utm_content', 200)->nullable();
            $table->string('utm_term', 200)->nullable();
            $table->string('fbclid', 200)->nullable();
            $table->string('gclid', 200)->nullable();
            $table->string('landing_page', 500)->nullable();
            $table->string('referrer', 500)->nullable();
            $table->string('fbp', 120)->nullable();
            $table->string('fbc', 500)->nullable();
            $table->string('client_ip', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['utm_source']);
            $table->dropIndex(['utm_campaign']);
            $table->dropColumn([
                'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'fbclid', 'gclid',
                'landing_page', 'referrer', 'fbp', 'fbc', 'client_ip', 'user_agent',
            ]);
        });
    }
};
