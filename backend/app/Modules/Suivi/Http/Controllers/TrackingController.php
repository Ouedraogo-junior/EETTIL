<?php

namespace App\Modules\Suivi\Http\Controllers;

use App\Modules\Dossiers\Models\Dossier;
use App\Modules\Suivi\Http\Requests\TrackingRequest;
use App\Modules\Suivi\Http\Resources\TrackingResource;
use Illuminate\Http\JsonResponse;

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

    /** Enregistre le suivi (nature, colis et poids obligatoires). Répond toujours 200, créé ou modifié. */
    public function update(TrackingRequest $request, Dossier $dossier): JsonResponse
    {
        $tracking = $dossier->tracking()->updateOrCreate([], $request->validated());

        return (new TrackingResource($tracking))->response()->setStatusCode(200);
    }
}