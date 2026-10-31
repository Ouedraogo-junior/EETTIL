<?php

namespace App\Modules\Facturation\Models;

use App\Modules\Dossiers\Models\Dossier;
use App\Modules\Facturation\Enums\DocumentState;
use App\Modules\Facturation\Enums\DocumentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        return $this->belongsTo(Dossier::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(DocumentLine::class)->orderBy('position');
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_document_id');
    }

    public function isEditable(): bool
    {
        return $this->state === DocumentState::Emis;
    }
}