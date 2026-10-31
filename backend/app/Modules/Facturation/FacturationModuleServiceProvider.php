<?php

namespace App\Modules\Facturation;

use App\Modules\ModuleServiceProvider;

class FacturationModuleServiceProvider extends ModuleServiceProvider
{
    protected function module(): string
    {
        return 'Facturation';
    }
}