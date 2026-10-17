<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Real reward points: a ledger of earned / used / returned points per customer,
 * points used on an order, and admin-controlled settings (low default rate).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('reward_point_transactions')) {
            Schema::create('reward_point_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
                $table->integer('points');
                $table->string('type', 20); // earn, reverse, redeem, refund, adjust
                $table->string('note')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'created_at']);
                $table->index(['order_id', 'type']);
            });
        }

        if (! Schema::hasColumn('orders', 'reward_points_used')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->unsignedInteger('reward_points_used')->default(0)->after('discount');
                $table->decimal('reward_discount', 10, 2)->default(0)->after('reward_points_used');
            });
        }

        $now = now();
        $settings = [
            ['rewards', 'rewards_enabled', 'Reward points: on (1) / off (0)', 'text', '1', 200],
            ['rewards', 'rewards_earn_per_100', 'Points earned per ৳100 spent', 'text', '1', 201],
            ['rewards', 'rewards_point_value', 'Value of 1 point in ৳', 'text', '1', 202],
            ['rewards', 'rewards_min_redeem', 'Minimum points before they can be used', 'text', '50', 203],
            ['rewards', 'rewards_max_redeem_percent', 'Max % of an order that points can pay', 'text', '10', 204],
        ];
        foreach ($settings as [$group, $key, $label, $type, $value, $sort]) {
            if (! DB::table('site_settings')->where('key', $key)->exists()) {
                DB::table('site_settings')->insert([
                    'group' => $group, 'key' => $key, 'label' => $label, 'type' => $type,
                    'value' => $value, 'sort_order' => $sort, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        // Keep the points customers already had (old page counted ৳100 = 1 point on delivered orders).
        $delivered = DB::table('orders')
            ->where('status', 'delivered')
            ->whereNotNull('user_id')
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('reward_point_transactions as r')
                ->whereColumn('r.order_id', 'orders.id')->where('r.type', 'earn'))
            ->get(['id', 'user_id', 'subtotal', 'discount', 'order_number']);
        foreach ($delivered as $order) {
            $points = (int) floor(max(0, (float) $order->subtotal - (float) $order->discount) / 100);
            if ($points > 0) {
                DB::table('reward_point_transactions')->insert([
                    'user_id' => $order->user_id, 'order_id' => $order->id, 'points' => $points, 'type' => 'earn',
                    'note' => 'অর্ডার ' . $order->order_number . ' ডেলিভারি', 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_point_transactions');
        if (Schema::hasColumn('orders', 'reward_points_used')) {
            Schema::table('orders', fn (Blueprint $table) => $table->dropColumn(['reward_points_used', 'reward_discount']));
        }
        DB::table('site_settings')->where('group', 'rewards')->delete();
    }
};
