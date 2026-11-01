<?php

namespace App\Modules\Paiements\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Paiements\Models\Payment */
class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'paid_on' => $this->paid_on->toDateString(),
            'amount' => $this->amount,
            'mode' => $this->mode->value,
            'mode_label' => $this->mode->label(),
            'reference' => $this->reference,
            'account' => $this->whenLoaded('account', fn () => ['id' => $this->account->id, 'name' => $this->account->name]),
            'document' => $this->whenLoaded('document', fn () => ['id' => $this->document->id, 'number' => $this->document->number]),
            'dossier' => $this->whenLoaded('dossier', fn () => $this->dossier ? [
                'id' => $this->dossier->id,
                'number' => $this->dossier->number,
                'client' => $this->dossier->relationLoaded('client') && $this->dossier->client
                    ? ['id' => $this->dossier->client->id, 'name' => $this->dossier->client->name]
                    : null,
            ] : null),
            'cancelled' => $this->isCancelled(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancel_reason' => $this->cancel_reason,
        ];
    }
}