<?php

namespace App\Modules\Tresorerie;

use App\Modules\ModuleServiceProvider;

class TresorerieModuleServiceProvider extends ModuleServiceProvider
{
    protected function module(): string
    {
        return 'Tresorerie';
    }
}