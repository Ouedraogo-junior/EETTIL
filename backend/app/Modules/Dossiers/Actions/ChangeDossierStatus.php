<?php

namespace App\Modules\Dossiers\Actions;

use App\Modules\Dossiers\Enums\DossierStatus;
use App\Modules\Dossiers\Models\Dossier;
use Illuminate\Validation\ValidationException;

class ChangeDossierStatus
{
    public function handle(Dossier $dossier, DossierStatus $to): Dossier
    {
        if ($to === DossierStatus::Cloturee && ! $this->invoiceIsSettled($dossier)) {
            throw ValidationException::withMessages([
                'status' => 'Un dossier ne peut être clôturé que si sa facture est soldée.',
            ]);
        }

        if ($to === DossierStatus::Annulee && $this->hasPayments($dossier)) {
            throw ValidationException::withMessages([
                'status' => 'Annulez d’abord les paiements reçus pour annuler ce dossier.',
            ]);
        }

        $dossier->update(['status' => $to]);

        return $dossier;
    }

    // À brancher sur les vraies données aux étapes 4 (factures) et 5 (paiements).
    private function invoiceIsSettled(Dossier $dossier): bool
    {
        return false;
    }

    private function hasPayments(Dossier $dossier): bool
    {
        return false;
    }
}