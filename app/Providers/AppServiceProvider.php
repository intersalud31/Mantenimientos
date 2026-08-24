<?php

namespace App\Providers;

use App\Services\Legacy\NotificationSummaryService;
use App\Services\Legacy\PeriodicidadMantenimientoService;
use App\Services\SemestreService;
use Illuminate\Support\Facades\View;
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
        $this->app->singleton(PeriodicidadMantenimientoService::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        View::composer('panel.partials.topbar-saas', function ($view): void {
            $periodo = app(SemestreService::class)->desdeRequest(request());

            $view->with('notificationSummary', app(NotificationSummaryService::class)->resumen($periodo));
        });
    }
}
