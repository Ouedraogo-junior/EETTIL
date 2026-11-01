<?php

namespace App\Modules\Tresorerie\Models;

use App\Modules\Dossiers\Models\Dossier;
use App\Modules\Tresorerie\Enums\MovementDirection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Entrée ou sortie d'argent sur un compte. Un paiement client crée son entrée (payment_id renseigné). */
class Movement extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'direction' => MovementDirection::class,
            'amount' => 'integer',
            'moved_on' => 'date',
            'is_debours' => 'boolean',
            'cancelled_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MovementCategory::class, 'category_id');
    }

    public function dossier(): BelongsTo
    {
        return $this->belongsTo(Dossier::class)->withTrashed();
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }
}