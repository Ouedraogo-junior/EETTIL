<?php

namespace App\Modules\Paiements\Models;

use App\Modules\Dossiers\Models\Dossier;
use App\Modules\Facturation\Models\Document;
use App\Modules\Paiements\Enums\PaymentMode;
use App\Modules\Tresorerie\Models\Account;
use App\Modules\Tresorerie\Models\Movement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** Paiement reçu sur une facture. Il crée une entrée de trésorerie ; annulé, il reste visible avec son motif. */
class Payment extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'paid_on' => 'date',
            'amount' => 'integer',
            'mode' => PaymentMode::class,
            'cancelled_at' => 'datetime',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function dossier(): BelongsTo
    {
        return $this->belongsTo(Dossier::class)->withTrashed();
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function movement(): HasOne
    {
        return $this->hasOne(Movement::class, 'payment_id');
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }
}