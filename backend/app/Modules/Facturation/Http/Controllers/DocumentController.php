<?php

namespace App\Modules\Facturation\Http\Controllers;

use App\Modules\Dossiers\Models\Dossier;
use App\Modules\Facturation\Actions\CancelDocument;
use App\Modules\Facturation\Actions\ConvertProforma;
use App\Modules\Facturation\Actions\SaveDocument;
use App\Modules\Facturation\Http\Requests\CancelDocumentRequest;
use App\Modules\Facturation\Http\Requests\DocumentRequest;
use App\Modules\Facturation\Http\Requests\PreviewRequest;
use App\Modules\Facturation\Http\Resources\DocumentResource;
use App\Modules\Facturation\Models\Document;
use App\Modules\Facturation\Models\LineTemplate;
use App\Support\Settings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentController
{
    private const RELATIONS = ['lines', 'dossier.client', 'dossier.tracking'];

    /** Documents d'un dossier, du plus récent au plus ancien. */
    public function index(Dossier $dossier)
    {
        return DocumentResource::collection($dossier->documents()->orderByDesc('id')->get());
    }

    public function show(Document $document): DocumentResource
    {
        return new DocumentResource($document->load(self::RELATIONS));
    }

    public function store(DocumentRequest $request, Dossier $dossier, SaveDocument $save): DocumentResource
    {
        $document = $save->create($dossier, $request->validated(), $request->user()->id);

        return new DocumentResource($document->load(self::RELATIONS));
    }

    public function update(DocumentRequest $request, Document $document, SaveDocument $save): DocumentResource
    {
        $document = $save->update($document, $request->validated(), $request->user()->id);

        return new DocumentResource($document->load(self::RELATIONS));
    }

    public function convert(Request $request, Document $document, ConvertProforma $convert): DocumentResource
    {
        $facture = $convert->handle($document->load('lines', 'dossier'), $request->user()->id);

        return new DocumentResource($facture->load(self::RELATIONS));
    }

    public function cancel(CancelDocumentRequest $request, Document $document, CancelDocument $cancel): DocumentResource
    {
        $cancel->handle($document, $request->validated('reason'), $request->user()->id);

        return new DocumentResource($document->refresh()->load(self::RELATIONS));
    }

    /** Calcule les totaux d'un document en cours de saisie, sans rien enregistrer. */
    public function preview(PreviewRequest $request, SaveDocument $save): JsonResponse
    {
        return response()->json(['data' => $save->compute($request->validated())->toArray()]);
    }

    /** Valeurs proposées à la saisie : TVA par défaut, devises, unités, lignes types. */
    public function options(): JsonResponse
    {
        $templates = LineTemplate::query()->orderBy('position')->orderBy('id')->get()->groupBy('block');

        return response()->json(['data' => [
            'vat_rate' => Settings::get('vat_rate'),
            'currencies' => ['XOF', 'EUR', 'USD'],
            'units' => ['ea', 'conteneur', 'jour', 'forfait', 'kg'],
            'templates' => [
                'D' => ($templates['D'] ?? collect())->pluck('label')->values(),
                'P' => ($templates['P'] ?? collect())->pluck('label')->values(),
            ],
        ]]);
    }
}