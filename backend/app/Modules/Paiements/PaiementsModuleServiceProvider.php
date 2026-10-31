<?php

namespace App\Modules\Paiements;

use App\Modules\ModuleServiceProvider;

class PaiementsModuleServiceProvider extends ModuleServiceProvider
{
    protected function module(): string
    {
        return 'Paiements';
    }
}