<?php

namespace App\Modules\Tresorerie\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Tresorerie\Models\Movement */
class MovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'moved_on' => $this->moved_on->toDateString(),
            'direction' => $this->direction->value,
            'amount' => $this->amount,
            'label' => $this->label,
            'is_debours' => $this->is_debours,
            'source' => $this->payment_id ? 'payment' : 'manual',
            'payment_id' => $this->payment_id,
            'account' => $this->whenLoaded('account', fn () => ['id' => $this->account->id, 'name' => $this->account->name]),
            'category' => $this->whenLoaded('category', fn () => ['id' => $this->category->id, 'name' => $this->category->name]),
            'dossier' => $this->whenLoaded('dossier', fn () => $this->dossier ? ['id' => $this->dossier->id, 'number' => $this->dossier->number] : null),
            'cancelled' => $this->isCancelled(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancel_reason' => $this->cancel_reason,
        ];
    }
}