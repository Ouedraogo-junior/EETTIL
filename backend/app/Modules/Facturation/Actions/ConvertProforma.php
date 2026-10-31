<?php

namespace App\Modules\Facturation\Actions;

use App\Modules\Facturation\Enums\DocumentState;
use App\Modules\Facturation\Enums\DocumentType;
use App\Modules\Facturation\Models\Document;
use Illuminate\Validation\ValidationException;

/** Transforme une quotation en facture : mêmes lignes, remises et informations d'en-tête, nouveau numéro de facture. */
class ConvertProforma
{
    public function __construct(private SaveDocument $save) {}

    public function handle(Document $proforma, ?int $userId): Document
    {
        if ($proforma->type !== DocumentType::Proforma) {
            throw ValidationException::withMessages(['document' => 'Seule une quotation peut être transformée en facture.']);
        }
        if ($proforma->state !== DocumentState::Emis) {
            throw ValidationException::withMessages(['document' => 'Cette quotation ne peut plus être transformée en facture.']);
        }

        $data = [
            'type' => DocumentType::Facture->value,
            'from_proforma_id' => $proforma->id,
            'issued_on' => now()->toDateString(),
            'currency' => $proforma->currency,
            'exchange_rate' => $proforma->exchange_rate,
            'caf_value' => $proforma->caf_value,
            'containers_20' => $proforma->containers_20,
            'containers_40' => $proforma->containers_40,
            'vat_enabled' => $proforma->vat_enabled,
            'vat_rate' => (float) $proforma->vat_rate,
            'global_discount_kind' => $proforma->global_discount_kind,
            'global_discount_value' => (float) $proforma->global_discount_value,
            'lines' => $proforma->lines->map(fn ($l) => [
                'block' => $l->block,
                'label' => $l->label,
                'unit' => $l->unit,
                'quantity' => (float) $l->quantity,
                'unit_price' => $l->unit_price,
                'discount_kind' => $l->discount_kind,
                'discount_value' => (float) $l->discount_value,
            ])->all(),
        ];

        return $this->save->create($proforma->dossier, $data, $userId);
    }
}