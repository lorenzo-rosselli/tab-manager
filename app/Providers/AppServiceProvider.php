<?php

namespace App\Providers;

use App\Services\AppVersion;
use App\Services\UpdateNotes;
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
        // Note dell'ultimo aggiornamento automatico: il layout le riceve (e le mostra) una volta sola.
        // Titolo della finestra, sempre uguale: nome dell'app e versione installata.
        View::composer('layouts.app', function ($view) {
            $view->with('updateNotes', app(UpdateNotes::class)->pull());
            $view->with('windowTitle', app(AppVersion::class)->title());
        });
    }
}
