<?php

namespace App\Providers;

use App\Models\IctInventory;
use App\Models\Institution;
use App\Observers\IctInventoryObserver;
use App\Observers\InstitutionObserver;
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
        Institution::observe(InstitutionObserver::class);
        IctInventory::observe(IctInventoryObserver::class);
    }
}
