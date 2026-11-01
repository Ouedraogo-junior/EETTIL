<?php

namespace App\Modules\Tresorerie\Http\Resources;

use App\Modules\Tresorerie\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/** @mixin \App\Modules\Tresorerie\Models\Account */
class AccountResource extends JsonResource
{
    /**
     * Comptes avec leur solde, calculé en une seule requête pour tous les comptes.
     *
     * @param  Collection<int,Account>  $accounts
     */
    public static function withBalances(Collection $accounts): Collection
    {
        $balances = Account::balances();

        return $accounts->each(fn (Account $a) => $a->setAttribute('balance_value', $balances[$a->id] ?? (int) $a->initial_balance));
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'initial_balance' => $this->initial_balance,
            'active' => $this->active,
            'balance' => $this->balance_value ?? $this->balance(),
        ];
    }
}