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
                'opay_display_name',
                'opay_country',
                'opay_currency',
                'opay_return_url',
                'opay_cancel_url',
                'opay_callback_url',
            ])
            ->pluck('value', 'key');

        $applicationUrl = rtrim((string) config('app.url', ''), '/');
        if ($applicationUrl === '' || str_contains($applicationUrl, 'localhost')) {
            $applicationUrl = 'https://admin.eride.ng';
        }

        $defaults = [
            'display_name' => $legacy->get('opay_display_name') ?: 'E-RIDE Nigeria',
            'country' => $legacy->get('opay_country') ?: 'NG',
            'currency' => $legacy->get('opay_currency') ?: 'NGN',
            'return_url' => $legacy->get('opay_return_url') ?: $applicationUrl . '/driver/payment/return',
            'cancel_url' => $legacy->get('opay_cancel_url') ?: $applicationUrl . '/driver/payment/cancel',
            'callback_url' => $legacy->get('opay_callback_url') ?: $applicationUrl . '/api/payments/opay/callback',
        ];

        $settings = [
            'live' => [
                'display_name' => 'Live OPay merchant name.',
                'country' => 'Live OPay merchant country code.',
                'currency' => 'Live OPay transaction currency.',
                'return_url' => 'Live OPay completed-checkout return URL.',
                'cancel_url' => 'Live OPay cancelled-checkout return URL.',
                'callback_url' => 'Live OPay callback URL configured in the merchant dashboard.',
            ],
            'demo' => [
                'display_name' => 'Demo OPay merchant name.',
                'country' => 'Demo OPay merchant country code.',
                'currency' => 'Demo OPay transaction currency.',
                'return_url' => 'Demo OPay completed-checkout return URL.',
                'cancel_url' => 'Demo OPay cancelled-checkout return URL.',
                'callback_url' => 'Demo OPay callback URL configured in the merchant dashboard.',
            ],
        ];

        foreach ($settings as $environment => $fields) {
            foreach ($fields as $key => $description) {
                $settingKey = 'opay_' . $environment . '_' . $key;
                $value = $defaults[$key];
                $existing = DB::table('system_settings')->where('key', $settingKey)->first();

                if (!$existing) {
                    DB::table('system_settings')->insert([
                        'key' => $settingKey,
                        'value' => $value,
                        'type' => 'payment',
                        'description' => $description,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                } elseif (trim((string) $existing->value) === '') {
                    DB::table('system_settings')
                        ->where('key', $settingKey)
                        ->update(['value' => $value, 'updated_at' => $now]);
                }
            }
        }
    }

    public function down(): void
    {
        // Keep environment-specific values if an administrator has edited
        // them from Settings after this migration was applied.
    }
};
