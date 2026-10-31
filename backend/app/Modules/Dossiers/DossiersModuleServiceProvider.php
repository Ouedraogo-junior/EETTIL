<?php

namespace App\Modules\Dossiers;

use App\Modules\ModuleServiceProvider;

class DossiersModuleServiceProvider extends ModuleServiceProvider
{
    protected function module(): string
    {
        return 'Dossiers';
    }
}