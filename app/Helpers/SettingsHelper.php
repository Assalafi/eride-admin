<?php

use App\Models\SystemSetting;

if (!function_exists('setting')) {
    /**
     * Get a system setting value
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function setting(string $key, $default = null)
    {
        return SystemSetting::get($key, $default);
    }
}

if (!function_exists('set_setting')) {
    /**
     * Set a system setting value
     * 
     * @param string $key
     * @param mixed $value
     * @param string $type
     * @param string|null $description
     * @return void
     */
    function set_setting(string $key, $value, string $type = 'text', string $description = null): void
    {
        SystemSetting::set($key, $value, $type, $description);
    }
}
