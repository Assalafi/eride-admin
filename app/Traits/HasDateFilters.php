<?php

namespace App\Traits;

use Carbon\Carbon;

trait HasDateFilters
{
    /**
     * Get date range based on time filter
     *
     * @param string $timeFilter (daily, weekly, monthly, yearly, custom)
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array [$start, $end]
     */
    protected function getDateRange($timeFilter, $startDate = null, $endDate = null): array
    {
        switch ($timeFilter) {
            case 'daily':
                return [Carbon::today(), Carbon::today()];
            
            case 'weekly':
                return [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()];
            
            case 'monthly':
                return [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()];
            
            case 'yearly':
                return [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()];
            
            case 'last_week':
                return [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()];
            
            case 'last_month':
                return [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()];
            
            case 'last_year':
                return [Carbon::now()->subYear()->startOfYear(), Carbon::now()->subYear()->endOfYear()];
            
            case 'all':
                return [null, null];
            
            case 'custom':
                if ($startDate && $endDate) {
                    return [Carbon::parse($startDate), Carbon::parse($endDate)];
                }
                return [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()];
            
            default:
                return [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()];
        }
    }

    /**
     * Get time filter options for dropdown
     *
     * @return array
     */
    protected function getTimeFilterOptions(): array
    {
        return [
            'daily' => 'Today',
            'weekly' => 'This Week',
            'monthly' => 'This Month',
            'yearly' => 'This Year',
            'last_week' => 'Last Week',
            'last_month' => 'Last Month',
            'last_year' => 'Last Year',
            'all' => 'All Time',
            'custom' => 'Custom Range',
        ];
    }

    /**
     * Get date range label for display
     *
     * @param string $timeFilter
     * @param Carbon|null $start
     * @param Carbon|null $end
     * @return string
     */
    protected function getDateRangeLabel($timeFilter, $start = null, $end = null): string
    {
        switch ($timeFilter) {
            case 'daily':
                return 'Today (' . Carbon::today()->format('M d, Y') . ')';
            
            case 'weekly':
                return 'This Week (' . Carbon::now()->startOfWeek()->format('M d') . ' - ' . Carbon::now()->endOfWeek()->format('M d, Y') . ')';
            
            case 'monthly':
                return Carbon::now()->format('F Y');
            
            case 'yearly':
                return Carbon::now()->format('Y');
            
            case 'last_week':
                return 'Last Week (' . Carbon::now()->subWeek()->startOfWeek()->format('M d') . ' - ' . Carbon::now()->subWeek()->endOfWeek()->format('M d, Y') . ')';
            
            case 'last_month':
                return Carbon::now()->subMonth()->format('F Y');
            
            case 'last_year':
                return Carbon::now()->subYear()->format('Y');
            
            case 'all':
                return 'All Time';
            
            case 'custom':
                if ($start && $end) {
                    return $start->format('M d, Y') . ' - ' . $end->format('M d, Y');
                }
                return 'Custom Range';
            
            default:
                return 'All Time';
        }
    }
}
