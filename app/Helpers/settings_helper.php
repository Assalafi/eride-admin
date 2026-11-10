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

if (!function_exists('user_pagination_size')) {
    /**
     * Return the signed-in user's preferred page size.
     * The value "all" intentionally disables visible pagination controls.
     */
    function user_pagination_size(int $default = 20): int|string
    {
        if (!auth()->check()) {
            return $default;
        }

        $value = auth()->user()->getPreference('pagination_per_page', $default);
        $value = $value !== 'all' && is_numeric($value) ? (int) $value : $value;
        $allowed = [10, 20, 25, 50, 100, 'all'];

        return in_array($value, $allowed, true) ? $value : $default;
    }
}

if (!function_exists('paginate_for_user')) {
    /**
     * Paginate a query using the current user's preference.
     * "All" still returns a LengthAwarePaginator so existing views continue to
     * support totals, row numbering and links without special-case controllers.
     */
    function paginate_for_user($query, int $default = 20, string $pageName = 'page')
    {
        $perPage = user_pagination_size($default);

        if ($perPage === 'all') {
            $items = $query->get();
            $total = $items->count();

            return new \Illuminate\Pagination\LengthAwarePaginator(
                $items,
                $total,
                max($total, 1),
                1,
                [
                    'path' => request()->url(),
                    'query' => request()->query(),
                    'pageName' => $pageName,
                ]
            );
        }

        return $query->paginate($perPage, ['*'], $pageName)->withQueryString();
    }
}
