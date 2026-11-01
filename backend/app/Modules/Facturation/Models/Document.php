<?php

namespace App\Modules\Facturation\Models;

use App\Modules\Dossiers\Models\Dossier;
use App\Modules\Facturation\Enums\DocumentState;
use App\Modules\Facturation\Enums\DocumentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Modules\Paiements\Models\Payment;
use Illuminate\Database\Eloquent\Builder;

class Document extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'type' => DocumentType::class,
            'state' => DocumentState::class,
            'issued_on' => 'date',
            'exchange_rate' => 'decimal:4',
            'caf_value' => 'integer',
            'containers_20' => 'integer',
            'containers_40' => 'integer',
            'vat_enabled' => 'boolean',
            'vat_rate' => 'decimal:2',
            'global_discount_value' => 'decimal:2',
            'debours_total' => 'integer',
            'prestations_subtotal' => 'integer',
            'global_discount_amount' => 'integer',
            'prestations_ht' => 'integer',
            'vat_amount' => 'integer',
            'prestations_total' => 'integer',
            'total' => 'integer',
            'cancelled_at' => 'datetime',
        ];
    }

    public function dossier(): BelongsTo
    {
        return $this->belongsTo(Dossier::class)->withTrashed();
    }

    public function lines(): HasMany
    {
        return $this->hasMany(DocumentLine::class)->orderBy('position');
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_document_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** Charge en une requête la somme des paiements non annulés (évite une requête par ligne dans les listes). */
    public function scopeWithPaid(Builder $query): Builder
    {
        return $query->withSum(['payments as paid_sum' => fn ($q) => $q->whereNull('cancelled_at')], 'amount');
    }

    /** Total des paiements reçus sur ce document (annulés exclus). */
    public function paidAmount(): int
    {
        if (array_key_exists('paid_sum', $this->attributes)) {
            return (int) $this->attributes['paid_sum'];
        }

        return (int) $this->payments()->whereNull('cancelled_at')->sum('amount');
    }

    /** @return array{paid:int,rest:int,state:string,state_label:string} */
    public function paymentSummary(): array
    {
        $paid = $this->paidAmount();
        $rest = max($this->total - $paid, 0);
        $state = $paid <= 0 ? 'unpaid' : ($rest > 0 ? 'partial' : 'settled');

        return [
            'paid' => $paid,
            'rest' => $rest,
            'state' => $state,
            'state_label' => ['unpaid' => 'Impayée', 'partial' => 'Partielle', 'settled' => 'Soldée'][$state],
        ];
    }

    public function isEditable(): bool
    {
        return $this->state === DocumentState::Emis;
    }
}