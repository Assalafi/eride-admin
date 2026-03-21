<?php

namespace App\Providers;

use App\Events\MaintenanceCompleted;
use App\Events\PaymentApproved;
use App\Listeners\ProcessMaintenanceCompletion;
use App\Listeners\UpdateDailyLedger;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Use Bootstrap 5 pagination
        Paginator::useBootstrapFive();

        // Register event listeners
        Event::listen(
            PaymentApproved::class,
            UpdateDailyLedger::class,
        );

        Event::listen(
            MaintenanceCompleted::class,
            ProcessMaintenanceCompletion::class,
        );
    }
}
