<?php

namespace App\Modules\Paiements\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Facture vue sous l'angle de l'encaissement : montant, déjà payé, reste à payer.
 * Utilisée par « Factures à encaisser » et par l'onglet Paiements d'un dossier.
 *
 * @mixin \App\Modules\Facturation\Models\Document
 */
class InvoiceSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'issued_on' => Carbon::parse($this->issued_on)->toDateString(),
            'total' => (int) $this->total,
            'payment' => $this->paymentSummary(),
            'dossier' => $this->whenLoaded('dossier', fn () => $this->dossier ? [
                'id' => $this->dossier->id,
                'number' => $this->dossier->number,
                'status' => $this->dossier->status->value,
                'client' => $this->dossier->relationLoaded('client') && $this->dossier->client
                    ? ['id' => $this->dossier->client->id, 'name' => $this->dossier->client->name]
                    : null,
            ] : null),
        ];
    }
}