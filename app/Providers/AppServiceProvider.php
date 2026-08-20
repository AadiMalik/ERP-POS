<?php

namespace App\Providers;

use App\Services\Concrete\Admin\AccessControlService;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\ThermalPrintSettingResolverService;
use Illuminate\Support\Facades\Blade;
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

        // Singleton so its permission->module map is built once per request,
        // not once per sidebar/dashboard @canAccess call.
        $this->app->singleton(AccessControlService::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Combined "user has permission AND business package includes its
        // module" check for view-layer gating (sidebar, dashboard, search,
        // report buttons) - see AccessControlService.
        Blade::if('canAccess', function (string $permission, ?string $moduleKey = null) {
            return app(AccessControlService::class)->allows($permission, $moduleKey);
        });

        Blade::if('canAccessAny', function (array $permissions) {
            return app(AccessControlService::class)->allowsAny($permissions);
        });
    }
}
