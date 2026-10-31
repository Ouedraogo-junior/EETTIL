<?php

namespace App\Modules\Rapports;

use App\Modules\ModuleServiceProvider;

class RapportsModuleServiceProvider extends ModuleServiceProvider
{
    protected function module(): string
    {
        return 'Rapports';
    }
}