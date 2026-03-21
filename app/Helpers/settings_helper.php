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

if (!function_exists('app_name')) {
    /**
     * Get the application name
     *
     * @return string
     */
    function app_name(): string
    {
        return setting('system_name', 'eRide Transport Management');
    }
}

if (!function_exists('app_logo')) {
    /**
     * Get the application logo URL
     *
     * @return string|null
     */
    function app_logo(): ?string
    {
        $logo = setting('system_logo');
        return $logo ? asset('storage/' . $logo) : null;
    }
}

if (!function_exists('app_favicon')) {
    /**
     * Get the application favicon URL
     *
     * @return string
     */
    function app_favicon(): string
    {
        $favicon = setting('system_favicon');
        return $favicon ? asset('storage/' . $favicon) : asset('assets/images/favicon.png');
    }
}

if (!function_exists('company_email')) {
    /**
     * Get the company email
     *
     * @return string
     */
    function company_email(): string
    {
        return setting('company_email', 'info@eride.ng');
    }
}

if (!function_exists('company_phone')) {
    /**
     * Get the company phone
     *
     * @return string
     */
    function company_phone(): string
    {
        return setting('company_phone', '+234 000 000 0000');
    }
}

if (!function_exists('company_address')) {
    /**
     * Get the company address
     *
     * @return string
     */
    function company_address(): string
    {
        return setting('company_address', 'Nigeria');
    }
}
