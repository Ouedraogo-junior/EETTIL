<?php

namespace App\Modules\Clients\Models;

use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\Dossiers\Models\Dossier;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'phone', 'email', 'address', 'ifu', 'rccm', 'notes'];

    protected static function newFactory(): ClientFactory
    {
        return ClientFactory::new();
    }

    public function dossiers(): HasMany
    {
        return $this->hasMany(Dossier::class);
    }

    /** Recherche par nom, téléphone (espaces ignorés) ou IFU. */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim(str_replace(['%', '_'], '', (string) $term));
        if ($term === '') {
            return $query;
        }

        $like = '%'.$term.'%';
        $phoneLike = '%'.str_replace(' ', '', $term).'%';

        return $query->where(function (Builder $q) use ($like, $phoneLike) {
            $q->where('name', 'like', $like)
                ->orWhere('ifu', 'like', $like)
                ->orWhereRaw("REPLACE(phone, ' ', '') like ?", [$phoneLike]);
        });
    }
}