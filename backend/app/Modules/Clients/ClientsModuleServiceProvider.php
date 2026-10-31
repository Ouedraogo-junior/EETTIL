<?php

namespace App\Modules\Clients;

use App\Modules\ModuleServiceProvider;

class ClientsModuleServiceProvider extends ModuleServiceProvider
{
    protected function module(): string
    {
        return 'Clients';
    }
}