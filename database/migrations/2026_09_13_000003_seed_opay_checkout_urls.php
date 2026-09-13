<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $applicationUrl = rtrim((string) config('app.url', ''), '/');

        // Never seed local development URLs into the admin settings that are
        // used by the live OPay merchant configuration.
        if ($applicationUrl === '' || str_contains($applicationUrl, 'localhost')) {
            $applicationUrl = 'https://admin.eride.ng';
        }

        $urls = [
            'opay_return_url' => [
                'value' => $applicationUrl . '/driver/payment/return',
                'description' => 'URL OPay opens after a completed checkout.',
            ],
            'opay_cancel_url' => [
                'value' => $applicationUrl . '/driver/payment/cancel',
                'description' => 'URL OPay opens after a cancelled checkout.',
            ],
            'opay_callback_url' => [
                'value' => $applicationUrl . '/api/payments/opay/callback',
                'description' => 'Public callback URL configured in OPay merchant dashboard.',
            ],
        ];

        foreach ($urls as $key => $data) {
            $setting = DB::table('system_settings')->where('key', $key)->first();

            if (!$setting) {
                DB::table('system_settings')->insert([
                    'key' => $key,
                    'value' => $data['value'],
                    'type' => 'payment',
                    'description' => $data['description'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                continue;
            }

            // An administrator may already have configured a custom URL.
            // Only complete missing values so deployment cannot overwrite it.
            if (trim((string) $setting->value) === '') {
                DB::table('system_settings')
                    ->where('key', $key)
                    ->update([
                        'value' => $data['value'],
                        'updated_at' => $now,
                    ]);
            }
        }
    }

    public function down(): void
    {
        // Keep these settings on rollback because they may have been edited
        // from the admin panel after the migration ran.
    }
};
