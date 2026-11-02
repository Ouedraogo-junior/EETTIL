<?php

namespace App\Modules\Suivi\Http\Controllers;

use App\Modules\Dossiers\Models\Dossier;
use App\Modules\Suivi\Http\Requests\TrackingRequest;
use App\Modules\Suivi\Http\Resources\TrackingResource;
use Illuminate\Http\JsonResponse;
use App\Modules\Dossiers\Enums\DossierStatus;

class TrackingController
{
    /** Active le suivi du transit d'un dossier (sans effet s'il est déjà actif). */
    public function store(Dossier $dossier): JsonResponse
    {
        $tracking = $dossier->tracking ?? $dossier->tracking()->create();

        return (new TrackingResource($tracking))
            ->response()
            ->setStatusCode($tracking->wasRecentlyCreated ? 201 : 200);
    }

    public function update(TrackingRequest $request, Dossier $dossier): TrackingResource
    {
        $tracking = $dossier->tracking()->updateOrCreate([], $request->validated());

        // Dès que la première date réelle est saisie, le dossier passe de « Nouvelle » à « En cours ».
        // On ne revient jamais en arrière, et les autres statuts ne changent pas.
        if ($dossier->status === DossierStatus::Nouvelle && $this->hasAnyStepDate($tracking)) {
            $dossier->update(['status' => DossierStatus::EnCours]);
        }

        // Une modification répond toujours 200, même si la ligne vient d'être créée.
        $tracking->wasRecentlyCreated = false;

        return new TrackingResource($tracking);
    }

    private function hasAnyStepDate($tracking): bool
    {
        $steps = $tracking::STEPS;
        $keys = array_is_list($steps) ? $steps : array_keys($steps);

        foreach ($keys as $key) {
            if ($tracking->{$key} !== null) {
                return true;
            }
        }

        return false;
    }
}