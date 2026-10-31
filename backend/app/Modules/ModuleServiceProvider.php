<?php

namespace App\Modules;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

abstract class ModuleServiceProvider extends ServiceProvider
{
    /** Nom du dossier du module dans app/Modules. */
    abstract protected function module(): string;

    public function boot(): void
    {
        $path = app_path('Modules/'.$this->module());

        if (is_dir($path.'/database/migrations')) {
            $this->loadMigrationsFrom($path.'/database/migrations');
        }

        if (! $this->app->routesAreCached() && is_file($path.'/routes.php')) {
            Route::middleware('api')->prefix('api/v1')->group($path.'/routes.php');
        }

        $this->bootModule();
    }

    /** Point d'extension propre à chaque module. */
    protected function bootModule(): void
    {
    }
}