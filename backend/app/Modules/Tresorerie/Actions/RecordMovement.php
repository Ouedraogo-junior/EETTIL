<?php

namespace App\Modules\Tresorerie\Actions;

use App\Modules\Dossiers\Enums\DossierStatus;
use App\Modules\Dossiers\Models\Dossier;
use App\Modules\Tresorerie\Enums\AccountType;
use App\Modules\Tresorerie\Enums\MovementDirection;
use App\Modules\Tresorerie\Models\Account;
use App\Modules\Tresorerie\Models\Movement;
use App\Modules\Tresorerie\Models\MovementCategory;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Saisie d'une entrée ou d'une sortie d'argent (les entrées de paiement client passent par le paiement). */
class RecordMovement
{
    public function handle(array $data, ?int $userId): Movement
    {
        return DB::transaction(function () use ($data, $userId) {
            $direction = MovementDirection::from($data['direction']);
            $amount = (int) $data['amount'];
            $category = MovementCategory::query()->findOrFail($data['category_id']);

            if ($category->direction !== $direction) {
                throw ValidationException::withMessages(['category_id' => 'Cette catégorie ne correspond pas au sens du mouvement.']);
            }
            if ($category->is_system) {
                throw ValidationException::withMessages(['category_id' => 'Cette catégorie est réservée aux paiements : enregistrez le paiement sur la facture.']);
            }

            $dossierId = $direction === MovementDirection::Out ? ($data['dossier_id'] ?? null) : null;
            if ($category->isDebours() && ! $dossierId) {
                throw ValidationException::withMessages(['dossier_id' => 'Un débours se rattache à un dossier : choisissez le dossier concerné.']);
            }
            if ($dossierId && Dossier::query()->findOrFail($dossierId)->status === DossierStatus::Annulee) {
                throw ValidationException::withMessages(['dossier_id' => 'Ce dossier est annulé : on ne peut plus y rattacher de dépense.']);
            }

            // Le compte est verrouillé le temps de contrôler son solde, pour que deux sorties simultanées ne le dépassent pas.
            $account = Account::query()->lockForUpdate()->findOrFail($data['account_id']);
            if ($direction === MovementDirection::Out && $account->type === AccountType::Caisse && $account->balance() < $amount) {
                throw ValidationException::withMessages([
                    'amount' => 'Solde insuffisant en caisse : '.Money::format(max($account->balance(), 0)).' disponibles.',
                ]);
            }

            return Movement::create([
                'account_id' => $account->id,
                'category_id' => $category->id,
                'dossier_id' => $dossierId,
                'direction' => $direction,
                'amount' => $amount,
                'moved_on' => $data['moved_on'],
                'label' => trim($data['label']),
                'is_debours' => $category->isDebours(),
                'created_by' => $userId,
            ])->load(['account', 'category', 'dossier']);
        });
    }
}