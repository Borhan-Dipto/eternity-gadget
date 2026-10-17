<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Optional 2-step login for the admin panel (Google Authenticator / Authy code + recovery codes). */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'app_authentication_secret')) {
            Schema::table('users', function (Blueprint $table) {
                $table->text('app_authentication_secret')->nullable();
                $table->text('app_authentication_recovery_codes')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['app_authentication_secret', 'app_authentication_recovery_codes']));
    }
};
