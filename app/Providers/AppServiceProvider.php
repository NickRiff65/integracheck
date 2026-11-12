<?php

namespace App\Providers;

use App\Enums\EstadoIncidente;
use App\Models\Incidente;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
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
        App::setLocale('es');
        Carbon::setLocale('es');
        Paginator::useBootstrapFive();

        View::composer(['layouts.app', 'dashboard'], function ($view) {
            $abiertos = 0;

            if (Schema::hasTable('incidentes')) {
                $abiertos = Incidente::query()
                    ->where('estado', EstadoIncidente::Abierto)
                    ->count();
            }

            $view->with('incidentesAbiertos', $abiertos);
        });
    }
}
