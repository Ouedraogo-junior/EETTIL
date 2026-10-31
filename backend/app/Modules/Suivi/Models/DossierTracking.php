<?php

namespace App\Modules\Suivi\Models;

use App\Modules\Dossiers\Models\Dossier;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DossierTracking extends Model
{
    /** Les six étapes, dans l'ordre. Chaque étape est facultative et porte une date réelle. */
    public const STEPS = [
        'date_ot' => 'Ordre de transit',
        'date_arrival' => 'Arrivée aéroport / port',
        'date_docs' => 'Remise documentaire complète',
        'date_declaration' => 'Déclaration enregistrée',
        'date_bae' => 'Bon à enlever obtenu',
        'date_delivery' => 'Livraison à l’agence',
    ];

    protected $fillable = [
        'supplier_invoices', 'dpi_number', 'dpi_date', 'bl_lta', 'transit_order_number', 'regime',
        'nature', 'packages', 'weight_kg',
        'date_ot', 'date_arrival', 'date_docs', 'date_declaration', 'date_bae', 'date_delivery',
        'comments',
    ];

    protected function casts(): array
    {
        $dates = array_fill_keys(array_keys(self::STEPS), 'date');

        return $dates + [
            'dpi_date' => 'date',
            'packages' => 'integer',
            'weight_kg' => 'decimal:2',
        ];
    }

    public function dossier(): BelongsTo
    {
        return $this->belongsTo(Dossier::class);
    }

    /** Clé de la dernière étape renseignée (selon l'ordre des étapes), ou null. */
    public function currentStepKey(): ?string
    {
        $current = null;
        foreach (array_keys(self::STEPS) as $key) {
            if ($this->{$key} !== null) {
                $current = $key;
            }
        }

        return $current;
    }

    /** not_started | in_progress | delivered */
    public function state(): string
    {
        if ($this->date_delivery !== null) {
            return 'delivered';
        }

        return $this->currentStepKey() ? 'in_progress' : 'not_started';
    }

    public function lastDate(): ?CarbonInterface
    {
        $dates = array_filter(array_map(fn (string $k) => $this->{$k}, array_keys(self::STEPS)));

        return $dates ? collect($dates)->max() : null;
    }

    public function scopeInState(Builder $query, ?string $state): Builder
    {
        $columns = array_keys(self::STEPS);

        return match ($state) {
            'not_started' => $query->where(function (Builder $w) use ($columns) {
                foreach ($columns as $c) {
                    $w->whereNull($c);
                }
            }),
            'in_progress' => $query->whereNull('date_delivery')->where(function (Builder $w) use ($columns) {
                foreach ($columns as $c) {
                    $w->orWhereNotNull($c);
                }
            }),
            'delivered' => $query->whereNotNull('date_delivery'),
            default => $query,
        };
    }

    /** Recherche sur les informations du fichier de suivi, le numéro de dossier et le nom du client. */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim(str_replace(['%', '_'], '', (string) $term));
        if ($term === '') {
            return $query;
        }
        $like = '%'.$term.'%';

        return $query->where(function (Builder $w) use ($like) {
            $w->where('supplier_invoices', 'like', $like)
                ->orWhere('dpi_number', 'like', $like)
                ->orWhere('bl_lta', 'like', $like)
                ->orWhere('transit_order_number', 'like', $like)
                ->orWhere('nature', 'like', $like)
                ->orWhereHas('dossier', fn (Builder $d) => $d
                    ->where('number', 'like', $like)
                    ->orWhereHas('client', fn (Builder $c) => $c->where('name', 'like', $like)));
        });
    }
}