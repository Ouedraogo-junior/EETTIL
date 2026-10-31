<?php

namespace App\Modules\Dossiers\Http\Resources;

use App\Modules\Dossiers\Enums\Service;
use App\Modules\Suivi\Http\Resources\TrackingResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Dossiers\Models\Dossier */
class DossierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'opened_on' => $this->opened_on?->toDateString(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'services' => collect($this->services)
                ->map(fn (string $s) => ['value' => $s, 'label' => Service::tryFrom($s)?->label() ?? $s])
                ->values(),
            'description' => $this->description,
            'notes' => $this->notes,
            'client_id' => $this->client_id,
            'client' => $this->relationLoaded('client') && $this->client
                ? ['id' => $this->client->id, 'name' => $this->client->name]
                : null,
            'tracking' => $this->relationLoaded('tracking') && $this->tracking
                ? new TrackingResource($this->tracking)
                : null,
        ];
    }
}