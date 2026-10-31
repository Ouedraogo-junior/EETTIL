<?php

namespace App\Modules\Auth;

use App\Modules\Auth\Http\Middleware\EnsureRole;
use App\Modules\ModuleServiceProvider;
use Illuminate\Support\Facades\Route;

class AuthModuleServiceProvider extends ModuleServiceProvider
{
    protected function module(): string
    {
        return 'Auth';
    }

    protected function bootModule(): void
    {
        Route::aliasMiddleware('role', EnsureRole::class);
    }
}