<?php

namespace App\Modules\Suivi\Http\Controllers;

use App\Modules\Dossiers\Enums\DossierStatus;
use App\Modules\Suivi\Actions\ExportSuivi;
use App\Modules\Suivi\Http\Resources\TrackingResource;
use App\Modules\Suivi\Models\DossierTracking;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SuiviController
{
    /** Dossiers suivis (hors dossiers annulés), filtrés par recherche et par état. */
    private function query(Request $request): Builder
    {
        return DossierTracking::query()
            ->whereHas('dossier', fn (Builder $d) => $d->where('status', '!=', DossierStatus::Annulee->value))
            ->with('dossier.client')
            ->inState($request->query('state'))
            ->search($request->query('q'))
            ->orderByDesc('updated_at')
            ->orderByDesc('id');
    }

    public function index(Request $request)
    {
        $perPage = min(max($request->integer('per_page', 25), 1), 100);

        return TrackingResource::collection($this->query($request)->paginate($perPage));
    }

    public function export(Request $request, ExportSuivi $export): StreamedResponse
    {
        return $export->stream($this->query($request), 'suivi-dossiers-'.now()->format('Y-m-d').'.xlsx');
    }
}