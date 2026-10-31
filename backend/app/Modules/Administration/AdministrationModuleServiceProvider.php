<?php

namespace App\Modules\Administration;

use App\Modules\ModuleServiceProvider;

class AdministrationModuleServiceProvider extends ModuleServiceProvider
{
    protected function module(): string
    {
        return 'Administration';
    }
}