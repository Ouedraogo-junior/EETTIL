<?php

namespace App\Modules\Suivi\Http\Resources;

use App\Modules\Suivi\Models\DossierTracking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DossierTracking */
class TrackingResource extends JsonResource
{
    private const STATE_LABELS = ['not_started' => 'Pas commencé', 'in_progress' => 'En cours', 'delivered' => 'Livré'];

    public function toArray(Request $request): array
    {
        $steps = [];
        foreach (DossierTracking::STEPS as $key => $label) {
            $steps[] = ['key' => $key, 'label' => $label, 'date' => $this->{$key}?->toDateString()];
        }

        $currentKey = $this->currentStepKey();
        $state = $this->state();

        $data = [
            'id' => $this->id,
            'dossier_id' => $this->dossier_id,
            'supplier_invoices' => $this->supplier_invoices,
            'dpi_number' => $this->dpi_number,
            'dpi_date' => $this->dpi_date?->toDateString(),
            'bl_lta' => $this->bl_lta,
            'transit_order_number' => $this->transit_order_number,
            'regime' => $this->regime,
            'nature' => $this->nature,
            'packages' => $this->packages,
            'weight_kg' => $this->weight_kg !== null ? (float) $this->weight_kg : null,
            'comments' => $this->comments,
            'steps' => $steps,
            'state' => $state,
            'state_label' => self::STATE_LABELS[$state],
            'current_step' => $currentKey
                ? ['key' => $currentKey, 'label' => DossierTracking::STEPS[$currentKey]]
                : null,
            'last_date' => $this->lastDate()?->toDateString(),
            'dossier' => $this->whenLoaded('dossier', fn () => [
                'id' => $this->dossier->id,
                'number' => $this->dossier->number,
                'status' => $this->dossier->status->value,
                'status_label' => $this->dossier->status->label(),
                'client' => $this->dossier->client
                    ? ['id' => $this->dossier->client->id, 'name' => $this->dossier->client->name]
                    : null,
            ]),
        ];

        // Dates à plat (date_ot, date_arrival…) pour pré-remplir le formulaire.
        foreach (array_keys(DossierTracking::STEPS) as $key) {
            $data[$key] = $this->{$key}?->toDateString();
        }

        return $data;
    }
}