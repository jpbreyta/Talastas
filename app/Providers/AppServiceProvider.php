<?php

namespace App\Providers;

use App\Models\IctInventory;
use App\Models\Institution;
use App\Models\InstitutionAnalytics;
use App\Observers\IctInventoryObserver;
use App\Observers\InstitutionAnalyticsObserver;
use App\Observers\InstitutionObserver;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Institution::observe(
            InstitutionObserver::class
        );

        IctInventory::observe(
            IctInventoryObserver::class
        );

        InstitutionAnalytics::observe(
            InstitutionAnalyticsObserver::class
        );

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
