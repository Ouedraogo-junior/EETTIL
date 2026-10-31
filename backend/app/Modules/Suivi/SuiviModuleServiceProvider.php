<?php

namespace App\Modules\Suivi;

use App\Modules\ModuleServiceProvider;

class SuiviModuleServiceProvider extends ModuleServiceProvider
{
    protected function module(): string
    {
        return 'Suivi';
    }
}