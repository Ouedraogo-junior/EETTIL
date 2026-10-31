<?php

namespace App\Modules\Facturation\Http\Resources;

use App\Modules\Facturation\Domain\AmountInWords;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Facturation\Models\Document */
class DocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'dossier_id' => $this->dossier_id,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'number' => $this->number,
            'state' => $this->state->value,
            'state_label' => $this->state->label(),
            'is_editable' => $this->isEditable(),
            'issued_on' => $this->issued_on->toDateString(),
            'currency' => $this->currency,
            'exchange_rate' => $this->exchange_rate !== null ? (float) $this->exchange_rate : null,
            'caf_value' => $this->caf_value,
            'containers_20' => $this->containers_20,
            'containers_40' => $this->containers_40,
            'vat_enabled' => $this->vat_enabled,
            'vat_rate' => (float) $this->vat_rate,
            'global_discount' => [
                'kind' => $this->global_discount_kind,
                'value' => (float) $this->global_discount_value,
            ],
            'totals' => [
                'debours' => $this->debours_total,
                'prestations_subtotal' => $this->prestations_subtotal,
                'global_discount' => $this->global_discount_amount,
                'prestations_ht' => $this->prestations_ht,
                'vat' => $this->vat_amount,
                'prestations_total' => $this->prestations_total,
                'total' => $this->total,
                'total_in_words' => AmountInWords::forInvoice($this->total),
            ],
            'source_document_id' => $this->source_document_id,
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancel_reason' => $this->cancel_reason,
            'lines' => $this->whenLoaded('lines', fn () => $this->lines->map(fn ($l) => [
                'id' => $l->id,
                'block' => $l->block,
                'label' => $l->label,
                'unit' => $l->unit,
                'quantity' => (float) $l->quantity,
                'unit_price' => $l->unit_price,
                'discount_kind' => $l->discount_kind,
                'discount_value' => (float) $l->discount_value,
                'discount_amount' => $l->discount_amount,
                'total' => $l->total,
            ])->values()),
            'dossier' => $this->whenLoaded('dossier', fn () => [
                'id' => $this->dossier->id,
                'number' => $this->dossier->number,
                'status' => $this->dossier->status->value,
                'bl_lta' => $this->dossier->relationLoaded('tracking') ? $this->dossier->tracking?->bl_lta : null,
                'client' => $this->dossier->client ? [
                    'id' => $this->dossier->client->id,
                    'name' => $this->dossier->client->name,
                    'address' => $this->dossier->client->address,
                    'ifu' => $this->dossier->client->ifu,
                    'rccm' => $this->dossier->client->rccm,
                ] : null,
            ]),
        ];
    }
}