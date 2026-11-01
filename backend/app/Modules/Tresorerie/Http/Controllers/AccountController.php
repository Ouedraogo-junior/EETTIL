<?php

namespace App\Modules\Tresorerie\Http\Controllers;

use App\Modules\Tresorerie\Http\Resources\AccountResource;
use App\Modules\Tresorerie\Models\Account;

class AccountController
{
    /** Comptes avec leur solde, et le total disponible. */
    public function index()
    {
        $accounts = AccountResource::withBalances(Account::query()->orderBy('position')->orderBy('id')->get());

        return AccountResource::collection($accounts)->additional([
            'total' => (int) $accounts->sum('balance_value'),
        ]);
    }
}