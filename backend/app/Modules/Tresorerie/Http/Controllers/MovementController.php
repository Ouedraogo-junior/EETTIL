<?php

namespace App\Modules\Tresorerie\Http\Controllers;

use App\Modules\Tresorerie\Actions\CancelMovement;
use App\Modules\Tresorerie\Actions\RecordMovement;
use App\Modules\Tresorerie\Enums\MovementDirection;
use App\Modules\Tresorerie\Http\Requests\CancelMovementRequest;
use App\Modules\Tresorerie\Http\Requests\StoreMovementRequest;
use App\Modules\Tresorerie\Http\Resources\AccountResource;
use App\Modules\Tresorerie\Http\Resources\MovementResource;
use App\Modules\Tresorerie\Models\Account;
use App\Modules\Tresorerie\Models\Movement;
use App\Modules\Tresorerie\Models\MovementCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MovementController
{
    private const RELATIONS = ['account', 'category', 'dossier'];

    /** Liste filtrable. Les totaux (entrées et sorties) portent sur toute la sélection, mouvements annulés exclus. */
    public function index(Request $request)
    {
        $perPage = min(max((int) $request->query('per_page', 20), 1), 100);

        $query = Movement::query()
            ->when($request->query('account_id'), fn ($q, $id) => $q->where('account_id', $id))
            ->when($request->query('direction'), fn ($q, $d) => $q->where('direction', $d))
            ->when($request->query('category_id'), fn ($q, $id) => $q->where('category_id', $id))
            ->when($request->query('dossier_id'), fn ($q, $id) => $q->where('dossier_id', $id))
            ->when($request->query('from'), fn ($q, $d) => $q->whereDate('moved_on', '>=', $d))
            ->when($request->query('to'), fn ($q, $d) => $q->whereDate('moved_on', '<=', $d))
            ->when($request->query('q'), function ($q, $term) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], trim($term)).'%';
                $q->where(fn ($w) => $w
                    ->where('label', 'like', $like)
                    ->orWhereHas('dossier', fn ($d) => $d->where('number', 'like', $like)));
            });

        $totals = (clone $query)->whereNull('cancelled_at')
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'in' THEN amount ELSE 0 END), 0) AS total_in, COALESCE(SUM(CASE WHEN direction = 'out' THEN amount ELSE 0 END), 0) AS total_out")
            ->first();

        $movements = $query->with(self::RELATIONS)->orderByDesc('moved_on')->orderByDesc('id')->paginate($perPage);

        return MovementResource::collection($movements)->additional([
            'totals' => ['in' => (int) $totals->total_in, 'out' => (int) $totals->total_out],
        ]);
    }

    public function store(StoreMovementRequest $request, RecordMovement $record): JsonResponse
    {
        $movement = $record->handle($request->validated(), $request->user()->id);

        return (new MovementResource($movement))->response()->setStatusCode(201);
    }

    public function cancel(CancelMovementRequest $request, Movement $movement, CancelMovement $cancel): MovementResource
    {
        return new MovementResource($cancel->handle($movement, $request->validated('reason'), $request->user()->id));
    }

    /** Valeurs proposées à la saisie : comptes actifs avec leur solde, catégories par sens. */
    public function options(): JsonResponse
    {
        $categories = MovementCategory::query()->orderBy('position')->orderBy('id')->get();
        $map = fn ($c) => ['id' => $c->id, 'name' => $c->name, 'is_debours' => $c->isDebours(), 'is_system' => $c->is_system];

        return response()->json(['data' => [
            'accounts' => AccountResource::collection(AccountResource::withBalances(Account::query()->where('active', true)->orderBy('position')->orderBy('id')->get()))->resolve(),
            'categories' => [
                'in' => $categories->filter(fn ($c) => $c->direction === MovementDirection::In)->values()->map($map),
                'out' => $categories->filter(fn ($c) => $c->direction === MovementDirection::Out)->values()->map($map),
            ],
        ]]);
    }
}