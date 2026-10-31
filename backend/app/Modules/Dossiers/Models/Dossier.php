<?php

namespace App\Modules\Dossiers\Models;

use App\Modules\Clients\Models\Client;
use App\Modules\Dossiers\Enums\DossierStatus;
use App\Modules\Dossiers\Enums\Service;
use App\Modules\Suivi\Models\DossierTracking;
use Database\Factories\DossierFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\Facturation\Models\Document;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dossier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['number', 'client_id', 'opened_on', 'status', 'services', 'description', 'notes'];

    protected function casts(): array
    {
        return [
            'opened_on' => 'date',
            'status' => DossierStatus::class,
            'services' => 'array',
        ];
    }

    protected static function newFactory(): DossierFactory
    {
        return DossierFactory::new();
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }
    
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function tracking(): HasOne
    {
        return $this->hasOne(DossierTracking::class);
    }

    /** Recherche par numéro, nom du client ou BL / LTA. */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim(str_replace(['%', '_'], '', (string) $term));
        if ($term === '') {
            return $query;
        }
        $like = '%'.$term.'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('number', 'like', $like)
                ->orWhereHas('client', fn (Builder $c) => $c->where('name', 'like', $like))
                ->orWhereHas('tracking', fn (Builder $t) => $t->where('bl_lta', 'like', $like));
        });
    }

    /** Filtre par service. Les services sont des codes ASCII (« transit »…), donc un LIKE sur le JSON est sûr. */
    public function scopeWithService(Builder $query, string $service): Builder
    {
        $code = Service::tryFrom($service)?->value ?? '__aucun__';

        return $query->where('services', 'like', '%"'.$code.'"%');
    }
}