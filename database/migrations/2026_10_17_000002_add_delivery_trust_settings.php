<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Shop owner's delivery / return / WhatsApp details, editable in Admin → Shop Settings.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $settings = [
            ['delivery', 'free_delivery_min', 'Free delivery from (৳, 0 = off)', 'text', '4000', 210],
            ['delivery', 'delivery_time_text', 'Delivery time (shown to customers)', 'text', 'সাধারণত ২-৩ দিন (এলাকাভেদে কম-বেশি হতে পারে)', 211],
            ['delivery', 'return_policy_text', 'Cancel / return policy (shown to customers)', 'textarea', 'অর্ডার করার ১০ মিনিটের মধ্যে ফ্রি-তে বাতিল করতে পারবেন। এরপর পণ্য ফেরত দিতে চাইলে শুধু ডেলিভারি চার্জ দিতে হবে।', 212],
            ['contact', 'whatsapp_number', 'WhatsApp number', 'phone', '01867683114', 213],
            ['delivery', 'trust_bar_enabled', 'Show delivery/return info bar: on (1) / off (0)', 'text', '1', 214],
        ];
        foreach ($settings as [$group, $key, $label, $type, $value, $sort]) {
            if (! DB::table('site_settings')->where('key', $key)->exists()) {
                DB::table('site_settings')->insert([
                    'group' => $group, 'key' => $key, 'label' => $label, 'type' => $type,
                    'value' => $value, 'sort_order' => $sort, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('site_settings')->whereIn('key', ['free_delivery_min', 'delivery_time_text', 'return_policy_text', 'whatsapp_number', 'trust_bar_enabled'])->delete();
    }
};
