<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $legacy = DB::table('system_settings')
            ->whereIn('key', [
                'opay_public_key',
                'opay_secret_key',
                'opay_merchant_id',
                'opay_base_url',
            ])
            ->pluck('value', 'key');

        $settings = [
            [
                'key' => 'opay_environment',
                'value' => 'live',
                'type' => 'payment',
                'description' => 'Choose whether new checkouts use the live or demo OPay account.',
            ],
            [
                'key' => 'opay_display_name',
                'value' => 'E-RIDE Nigeria',
                'type' => 'payment',
                'description' => 'Merchant name shown on the OPay Cashier page.',
            ],
            [
                'key' => 'opay_live_public_key',
                'value' => $legacy->get('opay_public_key'),
                'type' => 'payment',
                'description' => 'Live OPay public key.',
            ],
            [
                'key' => 'opay_live_secret_key',
                'value' => $legacy->get('opay_secret_key'),
                'type' => 'payment',
                'description' => 'Live OPay secret key.',
            ],
            [
                'key' => 'opay_live_merchant_id',
                'value' => $legacy->get('opay_merchant_id'),
                'type' => 'payment',
                'description' => 'Live OPay merchant ID.',
            ],
            [
                'key' => 'opay_live_base_url',
                'value' => $legacy->get('opay_base_url') ?: 'https://liveapi.opaycheckout.com',
                'type' => 'payment',
                'description' => 'Live OPay API endpoint.',
            ],
            [
                'key' => 'opay_demo_public_key',
                'value' => null,
                'type' => 'payment',
                'description' => 'Demo/staging OPay public key.',
            ],
            [
                'key' => 'opay_demo_secret_key',
                'value' => null,
                'type' => 'payment',
                'description' => 'Demo/staging OPay secret key.',
            ],
            [
                'key' => 'opay_demo_merchant_id',
                'value' => null,
                'type' => 'payment',
                'description' => 'Demo/staging OPay merchant ID.',
            ],
            [
                'key' => 'opay_demo_base_url',
                'value' => 'https://testapi.opaycheckout.com',
                'type' => 'payment',
                'description' => 'Demo/staging OPay API endpoint.',
            ],
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
            'opay_environment',
            'opay_display_name',
            'opay_live_public_key',
            'opay_live_secret_key',
            'opay_live_merchant_id',
            'opay_live_base_url',
            'opay_demo_public_key',
            'opay_demo_secret_key',
            'opay_demo_merchant_id',
            'opay_demo_base_url',
        ])->delete();
    }
};
