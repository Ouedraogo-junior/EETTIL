<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Modules\Auth\AuthModuleServiceProvider::class,
    App\Modules\Clients\ClientsModuleServiceProvider::class,
    App\Modules\Dossiers\DossiersModuleServiceProvider::class,
    App\Modules\Suivi\SuiviModuleServiceProvider::class,
    App\Modules\Facturation\FacturationModuleServiceProvider::class,
    App\Modules\Paiements\PaiementsModuleServiceProvider::class,
    App\Modules\Tresorerie\TresorerieModuleServiceProvider::class,
    App\Modules\Rapports\RapportsModuleServiceProvider::class,
    App\Modules\Administration\AdministrationModuleServiceProvider::class,
];