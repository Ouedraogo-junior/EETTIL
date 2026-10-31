<?php

namespace App\Modules\Suivi\Http\Controllers;

use App\Modules\Dossiers\Enums\DossierStatus;
use App\Modules\Suivi\Http\Resources\TrackingResource;
use App\Modules\Suivi\Models\DossierTracking;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class SuiviController
{
    public function index(Request $request)
    {
        $perPage = min(max($request->integer('per_page', 25), 1), 100);

        $rows = DossierTracking::query()
            ->whereHas('dossier', fn (Builder $d) => $d->where('status', '!=', DossierStatus::Annulee->value))
            ->with('dossier.client')
            ->inState($request->query('state'))
            ->search($request->query('q'))
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate($perPage);

        return TrackingResource::collection($rows);
    }
}