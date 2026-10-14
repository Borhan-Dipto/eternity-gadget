<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('device_models')) {
            Schema::create('device_models', function (Blueprint $table) {
                $table->id();
                $table->string('brand')->nullable();
                $table->string('name');
                $table->string('slug')->unique();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('product_options', 'device_model_id')) {
            Schema::table('product_options', function (Blueprint $table) {
                $table->foreignId('device_model_id')->nullable()->after('product_id')->constrained('device_models')->nullOnDelete();
            });
        }

        $this->backfillDeviceModels();

        if (! Schema::hasColumn('cart_items', 'option_signature')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->string('option_signature')->nullable()->after('product_option_id');
            });
        }

        // MariaDB may use the composite unique indexes to support FKs on user_id/session_id.
        // Add standalone indexes first so those unique indexes can be dropped safely.
        $indexes = collect(DB::select('SHOW INDEX FROM cart_items'))->pluck('Key_name')->unique();

        if (! $indexes->contains('cart_items_user_id_index')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->index('user_id', 'cart_items_user_id_index');
            });
        }

        if ($indexes->contains('cart_items_user_product_option_unique')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->dropUnique('cart_items_user_product_option_unique');
            });
        }

        if ($indexes->contains('cart_items_session_product_option_unique')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->dropUnique('cart_items_session_product_option_unique');
            });
        }

        DB::table('cart_items')
            ->orderBy('id')
            ->get()
            ->each(function (object $item): void {
                DB::table('cart_items')
                    ->where('id', $item->id)
                    ->update([
                        'option_signature' => $item->product_option_id ? (string) $item->product_option_id : null,
                    ]);
            });

        $indexes = collect(DB::select('SHOW INDEX FROM cart_items'))->pluck('Key_name')->unique();

        Schema::table('cart_items', function (Blueprint $table) use ($indexes) {
            if (! $indexes->contains('cart_items_user_product_signature_unique')) {
                $table->unique(['user_id', 'product_id', 'option_signature'], 'cart_items_user_product_signature_unique');
            }
            if (! $indexes->contains('cart_items_session_product_signature_unique')) {
                $table->unique(['session_id', 'product_id', 'option_signature'], 'cart_items_session_product_signature_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropUnique('cart_items_user_product_signature_unique');
            $table->dropUnique('cart_items_session_product_signature_unique');
            $table->dropColumn('option_signature');
            $table->unique(['user_id', 'product_id', 'product_option_id'], 'cart_items_user_product_option_unique');
            $table->unique(['session_id', 'product_id', 'product_option_id'], 'cart_items_session_product_option_unique');
        });

        Schema::table('product_options', function (Blueprint $table) {
            $table->dropConstrainedForeignId('device_model_id');
        });

        Schema::dropIfExists('device_models');
    }

    private function backfillDeviceModels(): void
    {
        $patterns = ['%model%', '%compat%', '%device%', '%iphone%', '%ipad%', '%airpods%', '%samsung%', '%pixel%'];

        $query = DB::table('product_options');
        foreach ($patterns as $index => $pattern) {
            $method = $index === 0 ? 'where' : 'orWhere';
            $query->{$method}('name', 'like', $pattern);
        }

        $query
            ->orderBy('id')
            ->get()
            ->each(function (object $option): void {
                $value = trim((string) $option->value);
                if ($value === '') {
                    return;
                }

                $existing = DB::table('device_models')->where('name', $value)->first();
                $deviceId = $existing?->id;

                if (! $deviceId) {
                    $slugBase = Str::slug($value) ?: 'device-model';
                    $slug = $slugBase;
                    $counter = 2;

                    while (DB::table('device_models')->where('slug', $slug)->exists()) {
                        $slug = $slugBase . '-' . $counter;
                        $counter++;
                    }

                    $deviceId = DB::table('device_models')->insertGetId([
                        'brand' => $this->detectBrand($value),
                        'name' => $value,
                        'slug' => $slug,
                        'sort_order' => 0,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('product_options')
                    ->where('id', $option->id)
                    ->update(['device_model_id' => $deviceId]);
            });
    }

    private function detectBrand(string $value): ?string
    {
        $needle = Str::lower($value);

        return match (true) {
            str_contains($needle, 'iphone'), str_contains($needle, 'ipad'), str_contains($needle, 'airpods') => 'Apple',
            str_contains($needle, 'samsung') => 'Samsung',
            str_contains($needle, 'pixel') => 'Google',
            default => null,
        };
    }
};
