<?php

namespace App\Modules\Tresorerie\Actions;

use App\Modules\Tresorerie\Models\Movement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Annulation d'un mouvement saisi à la main, avec motif. Un mouvement de paiement s'annule en annulant le paiement. */
class CancelMovement
{
    public function handle(Movement $movement, string $reason, ?int $userId): Movement
    {
        return DB::transaction(function () use ($movement, $reason, $userId) {
            $movement = Movement::query()->lockForUpdate()->findOrFail($movement->id);

            if ($movement->payment_id !== null) {
                throw ValidationException::withMessages(['movement' => 'Cette entrée vient d’un paiement : annulez le paiement pour l’annuler.']);
            }
            if ($movement->isCancelled()) {
                throw ValidationException::withMessages(['movement' => 'Ce mouvement est déjà annulé.']);
            }

            $movement->update(['cancelled_at' => now(), 'cancel_reason' => $reason, 'cancelled_by' => $userId]);

            return $movement->load(['account', 'category', 'dossier']);
        });
    }
}