<?php

namespace Versioon\NovaInlineBadgeField;

use Laravel\Nova\Nova;
use Laravel\Nova\Events\ServingNova;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Versioon\NovaInlineBadgeField\Http\Controllers\NovaInlineBadgeFieldController;

class FieldServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Nova::serving(function (ServingNova $event) {
            Nova::script('inline-badge-field', __DIR__ . '/../dist/js/entry.js');
            Nova::style('inline-badge-field', __DIR__ . '/../dist/css/entry.css');
        });

        $this->app->booted(function () {
            $this->routes();
        });
    }

    public function register()
    {
        //
    }

    protected function routes()
    {
        if ($this->app->routesAreCached()) return;

        Route::middleware(['nova'])->prefix('nova-vendor/nova-inline-badge-field')->group(function () {
            Route::post('/update/{resource}', [NovaInlineBadgeFieldController::class, 'update']);
        });
    }
}
