<?php

namespace App\Modules\Paiements\Http\Controllers;

use App\Modules\Dossiers\Models\Dossier;
use App\Modules\Facturation\Enums\DocumentState;
use App\Modules\Facturation\Enums\DocumentType;
use App\Modules\Paiements\Http\Resources\InvoiceSummaryResource;
use App\Modules\Facturation\Models\Document;
use App\Modules\Paiements\Actions\CancelPayment;
use App\Modules\Paiements\Actions\RecordPayment;
use App\Modules\Paiements\Enums\PaymentMode;
use App\Modules\Paiements\Http\Requests\CancelPaymentRequest;
use App\Modules\Paiements\Http\Requests\StorePaymentRequest;
use App\Modules\Paiements\Http\Resources\PaymentResource;
use App\Modules\Paiements\Models\Payment;
use App\Modules\Tresorerie\Http\Resources\AccountResource;
use App\Modules\Tresorerie\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController
{
    private const RELATIONS = ['document', 'dossier.client', 'account'];

    /** Historique des paiements, recherche par client, numéro de facture ou référence. */
    public function index(Request $request)
    {
        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);

        $payments = Payment::query()
            ->with(self::RELATIONS)
            ->when($request->query('q'), function ($q, $term) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], trim($term)).'%';
                $q->where(fn ($w) => $w
                    ->where('reference', 'like', $like)
                    ->orWhereHas('document', fn ($d) => $d->where('number', 'like', $like))
                    ->orWhereHas('dossier.client', fn ($c) => $c->where('name', 'like', $like)));
            })
            ->orderByDesc('paid_on')->orderByDesc('id')
            ->paginate($perPage);

        return PaymentResource::collection($payments);
    }

    /** Paiements d'un dossier (annulés compris, pour la traçabilité). */
    public function forDossier(Dossier $dossier)
    {
        return PaymentResource::collection(
            Payment::query()->with(self::RELATIONS)->where('dossier_id', $dossier->id)->orderByDesc('paid_on')->orderByDesc('id')->get()
        );
    }

    /** Factures émises d'un dossier, avec ce qui est payé et ce qui reste. */
    public function invoicesForDossier(Dossier $dossier)
    {
        return InvoiceSummaryResource::collection(
            Document::query()
                ->withPaid()
                ->with('dossier.client')
                ->where('dossier_id', $dossier->id)
                ->where('type', DocumentType::Facture->value)
                ->where('state', DocumentState::Emis->value)
                ->orderBy('id')
                ->get()
        );
    }

    /** Factures émises qui ont un reste à payer, les plus grosses d'abord. */
    public function receivable()
    {
        $documents = Document::query()
            ->withPaid()
            ->with('dossier.client')
            ->where('type', DocumentType::Facture->value)
            ->where('state', DocumentState::Emis->value)
            ->whereHas('dossier', fn ($d) => $d->where('status', '!=', 'annulee'))
            ->whereRaw('documents.total > (select coalesce(sum(p.amount), 0) from payments p where p.document_id = documents.id and p.cancelled_at is null)')
            ->limit(300)
            ->get()
            ->sortByDesc(fn (Document $d) => $d->total - $d->paidAmount())
            ->values();

        return InvoiceSummaryResource::collection($documents)->additional([
            'total_rest' => (int) $documents->sum(fn (Document $d) => $d->total - $d->paidAmount()),
        ]);
    }

    public function store(StorePaymentRequest $request, Document $document, RecordPayment $record): JsonResponse
    {
        $payment = $record->handle($document, $request->validated(), $request->user()->id);

        return (new PaymentResource($payment))
            ->additional(['invoice' => $payment->document->fresh()->paymentSummary()])
            ->response()->setStatusCode(201);
    }

    public function cancel(CancelPaymentRequest $request, Payment $payment, CancelPayment $cancel): PaymentResource
    {
        $payment = $cancel->handle($payment, $request->validated('reason'), $request->user()->id);

        return (new PaymentResource($payment))
            ->additional(['invoice' => $payment->document->fresh()->paymentSummary()]);
    }

    /** Valeurs proposées à la saisie : modes de paiement et comptes actifs. */
    public function options(): JsonResponse
    {
        return response()->json(['data' => [
            'modes' => collect(PaymentMode::cases())->map(fn (PaymentMode $m) => [
                'value' => $m->value,
                'label' => $m->label(),
                'account_type' => $m->usualAccountType()->value,
            ])->values(),
            'accounts' => AccountResource::collection(
                AccountResource::withBalances(Account::query()->where('active', true)->orderBy('position')->orderBy('id')->get())
            )->resolve(),
        ]]);
    }
}