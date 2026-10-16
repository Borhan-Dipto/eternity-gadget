<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Empty settings rows so they appear in Admin → Website Settings, ready to fill in. */
    public function up(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        $rows = [
            ['notifications', 'order_alert_email', 'New-order alert email (yours)', 'email', null, 110],
            ['notifications', 'telegram_bot_token', 'Telegram bot token (new-order alerts)', 'text', null, 111],
            ['notifications', 'telegram_chat_id', 'Telegram chat ID (new-order alerts)', 'text', null, 112],
            ['general', 'sms_enabled', 'Phone OTP login enabled (1 = yes, needs SMS gateway)', 'text', '0', 120],
        ];

        foreach ($rows as [$group, $key, $label, $type, $value, $sort]) {
            if (DB::table('site_settings')->where('key', $key)->exists()) {
                continue;
            }
            DB::table('site_settings')->insert([
                'group' => $group, 'key' => $key, 'label' => $label, 'type' => $type,
                'value' => $value, 'sort_order' => $sort, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('site_settings')) {
            DB::table('site_settings')->whereIn('key', ['order_alert_email', 'telegram_bot_token', 'telegram_chat_id', 'sms_enabled'])->delete();
        }
    }
};
