<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SystemSetting;

echo "Checking charging fee...\n\n";

$chargingFee = SystemSetting::get('charging_per_session', '5000');

echo "Charging Fee (from SystemSetting): ₦" . number_format($chargingFee, 2) . "\n";
echo "Raw value: " . $chargingFee . "\n\n";

// Check if the setting exists in database
$setting = SystemSetting::where('key', 'charging_per_session')->first();
if ($setting) {
    echo "Setting found in database:\n";
    echo "Key: " . $setting->key . "\n";
    echo "Value: " . $setting->value . "\n";
} else {
    echo "Setting NOT found in database (using default: 5000)\n";
}

// Clear OpCache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "\n✓ OpCache cleared!\n";
}
