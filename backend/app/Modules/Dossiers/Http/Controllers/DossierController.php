<?php

namespace App\Modules\Dossiers\Http\Controllers;

use App\Modules\Dossiers\Actions\ChangeDossierStatus;
use App\Modules\Dossiers\Actions\CreateDossier;
use App\Modules\Dossiers\Enums\DossierStatus;
use App\Modules\Dossiers\Enums\Service;
use App\Modules\Dossiers\Http\Requests\ChangeStatusRequest;
use App\Modules\Dossiers\Http\Requests\StoreDossierRequest;
use App\Modules\Dossiers\Http\Requests\UpdateDossierRequest;
use App\Modules\Dossiers\Http\Resources\DossierResource;
use App\Modules\Dossiers\Models\Dossier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DossierController
{
    private const RELATIONS = ['client:id,name', 'tracking'];

    public function index(Request $request)
    {
        $perPage = min(max($request->integer('per_page', 20), 1), 100);

        $dossiers = Dossier::query()
            ->with(self::RELATIONS)
            ->search($request->query('q'))
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('service'), fn ($q, $service) => $q->withService($service))
            ->when($request->query('client_id'), fn ($q, $id) => $q->where('client_id', $id))
            ->orderByDesc('opened_on')
            ->orderByDesc('id')
            ->paginate($perPage);

        return DossierResource::collection($dossiers);
    }

    public function store(StoreDossierRequest $request, CreateDossier $create): DossierResource
    {
        $dossier = $create->handle($request->validated());

        return new DossierResource($dossier->load(self::RELATIONS));
    }

    public function show(Dossier $dossier): DossierResource
    {
        return new DossierResource($dossier->load(self::RELATIONS));
    }

    public function update(UpdateDossierRequest $request, Dossier $dossier): DossierResource
    {
        $dossier->update($request->validated());

        return new DossierResource($dossier->load(self::RELATIONS));
    }

    public function changeStatus(ChangeStatusRequest $request, Dossier $dossier, ChangeDossierStatus $change): DossierResource
    {
        $change->handle($dossier, DossierStatus::from($request->validated('status')));

        return new DossierResource($dossier->load(self::RELATIONS));
    }

    public function destroy(Dossier $dossier): Response
    {
        $dossier->delete();

        return response()->noContent();
    }

    /** Listes de valeurs pour les filtres et formulaires de l'interface. */
    public function options(): JsonResponse
    {
        return response()->json(['data' => [
            'statuses' => collect(DossierStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
            'services' => collect(Service::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
            'regimes' => config('eettil.regimes'),
            'tracking_states' => [
                ['value' => 'not_started', 'label' => 'Pas commencé'],
                ['value' => 'in_progress', 'label' => 'En cours'],
                ['value' => 'delivered', 'label' => 'Livré'],
            ],
        ]]);
    }
}