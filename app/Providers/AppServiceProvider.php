<?php

namespace App\Providers;

use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\ThermalPrintSettingResolverService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Singleton so the in-request resolve() memo is shared across the
        // header/footer/badge partials rendering the same print document.
        $this->app->singleton(PrintSettingResolverService::class);
        $this->app->singleton(ThermalPrintSettingResolverService::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
