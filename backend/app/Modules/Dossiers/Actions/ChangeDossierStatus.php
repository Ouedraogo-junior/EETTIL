<?php

namespace App\Modules\Dossiers\Actions;

use App\Modules\Dossiers\Enums\DossierStatus;
use App\Modules\Dossiers\Models\Dossier;
use Illuminate\Validation\ValidationException;
use App\Modules\Facturation\Enums\DocumentState;
use App\Modules\Facturation\Enums\DocumentType;
use App\Modules\Paiements\Models\Payment;

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

    private function invoiceIsSettled(Dossier $dossier): bool
    {
        $facture = $dossier->documents()
            ->where('type', DocumentType::Facture->value)
            ->where('state', DocumentState::Emis->value)
            ->first();

        return $facture !== null && $facture->paidAmount() >= $facture->total;
    }

    private function hasPayments(Dossier $dossier): bool
    {
        return Payment::query()->where('dossier_id', $dossier->id)->whereNull('cancelled_at')->exists();
    }
}