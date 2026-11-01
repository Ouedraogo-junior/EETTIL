<?php

namespace App\Modules\Tresorerie\Models;

use App\Modules\Tresorerie\Enums\AccountType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Compte de trésorerie (caisse ou banque). Son solde se calcule : solde initial + entrées - sorties, mouvements annulés exclus. */
class Account extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'type' => AccountType::class,
            'initial_balance' => 'integer',
            'active' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function movements(): HasMany
    {
        return $this->hasMany(Movement::class);
    }

    public function balance(): int
    {
        return self::balances()[$this->id] ?? (int) $this->initial_balance;
    }

    /** Solde de tous les comptes, en une seule requête : [id du compte => solde]. */
    public static function balances(): array
    {
        $sums = Movement::query()
            ->whereNull('cancelled_at')
            ->selectRaw("account_id, SUM(CASE WHEN direction = 'in' THEN amount ELSE 0 END) AS total_in, SUM(CASE WHEN direction = 'out' THEN amount ELSE 0 END) AS total_out")
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');

        $balances = [];
        foreach (self::query()->get(['id', 'initial_balance']) as $account) {
            $row = $sums->get($account->id);
            $balances[$account->id] = (int) $account->initial_balance + (int) ($row->total_in ?? 0) - (int) ($row->total_out ?? 0);
        }

        return $balances;
    }
}