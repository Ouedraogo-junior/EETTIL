<?php

namespace App\Modules\Facturation\Actions;

use App\Modules\Facturation\Enums\DocumentState;
use App\Modules\Facturation\Enums\DocumentType;
use App\Modules\Facturation\Models\Document;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Annulation d'un document avec motif : on n'efface jamais une facture, on l'annule. */
class CancelDocument
{
    public function handle(Document $document, string $reason, ?int $userId): Document
    {
        return DB::transaction(function () use ($document, $reason, $userId) {
            $document = Document::query()->lockForUpdate()->findOrFail($document->id);

            if ($document->state !== DocumentState::Emis) {
                throw ValidationException::withMessages(['document' => 'Seul un document émis peut être annulé.']);
            }
            if ($document->type === DocumentType::Facture && $this->hasPayments($document)) {
                throw ValidationException::withMessages(['document' => 'Annulez d’abord les paiements reçus pour annuler cette facture.']);
            }

            $document->update([
                'state' => DocumentState::Annule,
                'cancelled_at' => now(),
                'cancel_reason' => $reason,
                'cancelled_by' => $userId,
                'updated_by' => $userId,
            ]);

            return $document;
        });
    }

    /** À brancher sur les paiements à l'étape 5. */
    private function hasPayments(Document $document): bool
    {
        return false;
    }
}