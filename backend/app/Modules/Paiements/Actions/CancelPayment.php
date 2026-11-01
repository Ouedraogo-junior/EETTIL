<?php

namespace App\Modules\Paiements\Actions;

use App\Modules\Dossiers\Enums\DossierStatus;
use App\Modules\Dossiers\Models\Dossier;
use App\Modules\Paiements\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Annule un paiement avec motif : il reste visible, et son entrée de trésorerie est annulée avec lui. */
class CancelPayment
{
    public function handle(Payment $payment, string $reason, ?int $userId): Payment
    {
        return DB::transaction(function () use ($payment, $reason, $userId) {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($payment->isCancelled()) {
                throw ValidationException::withMessages(['payment' => 'Ce paiement est déjà annulé.']);
            }
            if (Dossier::query()->find($payment->dossier_id)?->status === DossierStatus::Cloturee) {
                throw ValidationException::withMessages(['payment' => 'Ce dossier est clôturé : rouvrez-le avant d’annuler un paiement.']);
            }

            $payment->update(['cancelled_at' => now(), 'cancel_reason' => $reason, 'cancelled_by' => $userId]);

            $payment->movement()->whereNull('cancelled_at')->update([
                'cancelled_at' => now(),
                'cancel_reason' => 'Paiement annulé : '.$reason,
                'cancelled_by' => $userId,
            ]);

            return $payment->load(['document', 'dossier.client', 'account']);
        });
    }
}