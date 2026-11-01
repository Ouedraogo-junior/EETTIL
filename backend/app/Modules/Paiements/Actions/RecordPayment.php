<?php

namespace App\Modules\Paiements\Actions;

use App\Modules\Dossiers\Enums\DossierStatus;
use App\Modules\Dossiers\Models\Dossier;
use App\Modules\Facturation\Enums\DocumentState;
use App\Modules\Facturation\Enums\DocumentType;
use App\Modules\Facturation\Models\Document;
use App\Modules\Paiements\Models\Payment;
use App\Modules\Tresorerie\Enums\MovementDirection;
use App\Modules\Tresorerie\Models\Account;
use App\Modules\Tresorerie\Models\Movement;
use App\Modules\Tresorerie\Models\MovementCategory;
use App\Support\Counter;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Enregistre un paiement sur une facture et crée l'entrée de trésorerie correspondante. */
class RecordPayment
{
    public function handle(Document $document, array $data, ?int $userId): Payment
    {
        return DB::transaction(function () use ($document, $data, $userId) {
            // La facture est verrouillée : deux paiements simultanés ne peuvent pas dépasser son montant.
            $document = Document::query()->lockForUpdate()->findOrFail($document->id);
            $dossier = Dossier::query()->with('client')->findOrFail($document->dossier_id);

            if ($document->type !== DocumentType::Facture || $document->state !== DocumentState::Emis) {
                throw ValidationException::withMessages(['document' => 'Seule une facture émise peut recevoir un paiement.']);
            }
            if ($dossier->status === DossierStatus::Annulee) {
                throw ValidationException::withMessages(['document' => 'Ce dossier est annulé : aucun paiement ne peut être enregistré.']);
            }

            $amount = (int) $data['amount'];
            $rest = $document->total - $document->paidAmount();
            if ($amount > $rest) {
                throw ValidationException::withMessages([
                    'amount' => 'Ce montant dépasse le reste à payer de la facture ('.Money::format(max($rest, 0)).').',
                ]);
            }

            $account = Account::query()->findOrFail($data['account_id']);
            $reference = trim((string) ($data['reference'] ?? ''));
            if ($reference === '') {
                $reference = sprintf('REC-%04d', Counter::next('receipt'));
            }

            $payment = Payment::create([
                'document_id' => $document->id,
                'dossier_id' => $dossier->id,
                'account_id' => $account->id,
                'paid_on' => $data['paid_on'],
                'amount' => $amount,
                'mode' => $data['mode'],
                'reference' => $reference,
                'created_by' => $userId,
            ]);

            Movement::create([
                'account_id' => $account->id,
                'category_id' => MovementCategory::payment()->id,
                'dossier_id' => $dossier->id,
                'payment_id' => $payment->id,
                'direction' => MovementDirection::In,
                'amount' => $amount,
                'moved_on' => $data['paid_on'],
                'label' => 'Paiement '.$document->number.' – '.($dossier->client?->name ?? ''),
                'is_debours' => false,
                'created_by' => $userId,
            ]);

            return $payment->load(['document', 'dossier.client', 'account']);
        });
    }
}