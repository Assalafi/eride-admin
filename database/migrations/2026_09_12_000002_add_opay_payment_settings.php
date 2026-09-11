<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $settings = [
            ['key' => 'opay_enabled', 'value' => '1', 'type' => 'boolean', 'description' => 'Enable OPay checkout for new driver payments.'],
            ['key' => 'opay_public_key', 'value' => null, 'type' => 'payment', 'description' => 'OPay public key used to create cashier checkouts.'],
            ['key' => 'opay_secret_key', 'value' => null, 'type' => 'payment', 'description' => 'OPay secret key used to sign status requests and verify callbacks.'],
            ['key' => 'opay_merchant_id', 'value' => null, 'type' => 'payment', 'description' => 'OPay merchant ID.'],
            ['key' => 'opay_base_url', 'value' => 'https://liveapi.opaycheckout.com', 'type' => 'payment', 'description' => 'OPay API base URL. Use the test URL only during testing.'],
            ['key' => 'opay_country', 'value' => 'NG', 'type' => 'payment', 'description' => 'OPay merchant country code.'],
            ['key' => 'opay_currency', 'value' => 'NGN', 'type' => 'payment', 'description' => 'OPay transaction currency.'],
            ['key' => 'opay_return_url', 'value' => null, 'type' => 'payment', 'description' => 'URL OPay opens after a completed checkout.'],
            ['key' => 'opay_cancel_url', 'value' => null, 'type' => 'payment', 'description' => 'URL OPay opens after a cancelled checkout.'],
            ['key' => 'opay_callback_url', 'value' => null, 'type' => 'payment', 'description' => 'Public callback URL configured in OPay merchant dashboard.'],
        ];

        foreach ($settings as $setting) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $setting['key']],
                array_merge($setting, ['created_at' => $now, 'updated_at' => $now])
            );
        }
    }

    public function down(): void
    {
        DB::table('system_settings')->whereIn('key', [
            'opay_enabled',
            'opay_public_key',
            'opay_secret_key',
            'opay_merchant_id',
            'opay_base_url',
            'opay_country',
            'opay_currency',
            'opay_return_url',
            'opay_cancel_url',
            'opay_callback_url',
        ])->delete();
    }
};
