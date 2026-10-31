<?php

namespace App\Modules\Facturation\Actions;

use App\Modules\Dossiers\Enums\DossierStatus;
use App\Modules\Dossiers\Models\Dossier;
use App\Modules\Facturation\Domain\InvoiceCalculator;
use App\Modules\Facturation\Domain\InvoiceLine;
use App\Modules\Facturation\Domain\InvoiceTotals;
use App\Modules\Facturation\Enums\DocumentState;
use App\Modules\Facturation\Enums\DocumentType;
use App\Modules\Facturation\Models\Document;
use App\Support\Settings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveDocument
{
    public function __construct(
        private InvoiceCalculator $calculator,
        private GenerateDocumentNumber $numbers,
    ) {}

    /** Calcul seul (sans contrôle des remises ni enregistrement) : sert à l'aperçu en direct. */
    public function compute(array $data): InvoiceTotals
    {
        $lines = array_map(fn (array $l) => new InvoiceLine(
            $l['block'],
            (string) ($l['label'] ?? ''),
            (string) ($l['unit'] ?? 'ea'),
            (float) ($l['quantity'] ?? 0),
            (int) ($l['unit_price'] ?? 0),
            $l['block'] === InvoiceLine::PRESTATION ? ($l['discount_kind'] ?? null) : null,
            (float) ($l['discount_value'] ?? 0),
        ), array_values($data['lines'] ?? []));

        return $this->calculator->calculate(
            $lines,
            $data['global_discount_kind'] ?? null,
            (float) ($data['global_discount_value'] ?? 0),
            (bool) ($data['vat_enabled'] ?? false),
            (float) ($data['vat_rate'] ?? Settings::get('vat_rate')),
        );
    }

    public function create(Dossier $dossier, array $data, ?int $userId): Document
    {
        return DB::transaction(function () use ($dossier, $data, $userId) {
            $type = DocumentType::from($data['type']);

            if ($type === DocumentType::Facture && $this->hasActiveFacture($dossier)) {
                throw ValidationException::withMessages(['type' => 'Ce dossier a déjà une facture.']);
            }

            $source = $this->findSource($dossier, $type, $data['from_proforma_id'] ?? null);
            $totals = $this->computeOrFail($data);
            $number = $this->numbers->handle($type, Carbon::parse($data['issued_on']));

            $document = Document::create([
                'dossier_id' => $dossier->id,
                'source_document_id' => $source?->id,
                'type' => $type,
                'number' => $number,
                'state' => DocumentState::Emis,
                'created_by' => $userId,
                'updated_by' => $userId,
            ] + $this->attributes($data, $totals));

            $this->storeLines($document, $data['lines'], $totals);

            $source?->update(['state' => DocumentState::Transforme, 'updated_by' => $userId]);

            if ($type === DocumentType::Facture && $dossier->status === DossierStatus::Nouvelle) {
                $dossier->update(['status' => DossierStatus::EnCours]);
            }

            return $document->load('lines');
        });
    }

    public function update(Document $document, array $data, ?int $userId): Document
    {
        return DB::transaction(function () use ($document, $data, $userId) {
            $document = Document::query()->lockForUpdate()->findOrFail($document->id);

            if (! $document->isEditable()) {
                throw ValidationException::withMessages([
                    'document' => 'Ce document est '.($document->state === DocumentState::Annule ? 'annulé' : 'déjà transformé en facture').' : il n’est plus modifiable.',
                ]);
            }

            $totals = $this->computeOrFail($data);

            $paid = $this->paidAmount($document);
            if ($document->type === DocumentType::Facture && $totals->total < $paid) {
                throw ValidationException::withMessages([
                    'lines' => 'Le montant total ne peut pas être inférieur aux paiements déjà reçus ('.self::money($paid).').',
                ]);
            }

            $document->update($this->attributes($data, $totals) + ['updated_by' => $userId]);
            $this->storeLines($document, $data['lines'], $totals);

            return $document->load('lines');
        });
    }

    /** Total des paiements reçus sur ce document. À brancher sur les paiements à l'étape 5. */
    private function paidAmount(Document $document): int
    {
        return 0;
    }

    private function hasActiveFacture(Dossier $dossier): bool
    {
        return Document::query()
            ->where('dossier_id', $dossier->id)
            ->where('type', DocumentType::Facture->value)
            ->where('state', '!=', DocumentState::Annule->value)
            ->exists();
    }

    private function findSource(Dossier $dossier, DocumentType $type, ?int $proformaId): ?Document
    {
        if ($proformaId === null) {
            return null;
        }
        if ($type !== DocumentType::Facture) {
            throw ValidationException::withMessages(['from_proforma_id' => 'Seule une facture peut être créée à partir d’une proforma.']);
        }

        $source = Document::query()->lockForUpdate()->find($proformaId);
        if (! $source || $source->dossier_id !== $dossier->id || $source->type !== DocumentType::Proforma || $source->state !== DocumentState::Emis) {
            throw ValidationException::withMessages(['from_proforma_id' => 'Cette proforma ne peut pas être transformée en facture.']);
        }

        return $source;
    }

    /** Calcule les totaux et refuse une remise supérieure au montant sur lequel elle porte. */
    private function computeOrFail(array $data): InvoiceTotals
    {
        $totals = $this->compute($data);
        $errors = [];

        foreach ($totals->lines as $i => $line) {
            if ($line->overLimit) {
                $errors["lines.{$i}.discount_value"] = 'Ligne '.($i + 1).' : la remise ne peut pas dépasser le montant de la ligne ('.self::money($line->gross).').';
            }
        }
        if ($totals->globalOverLimit) {
            $errors['global_discount_value'] = 'La remise globale ne peut pas dépasser le sous-total des prestations ('.self::money($totals->prestationsSubtotal).').';
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        return $totals;
    }

    /** @return array<string,mixed> */
    private function attributes(array $data, InvoiceTotals $t): array
    {
        $currency = $data['currency'] ?? 'XOF';
        $discountKind = $data['global_discount_kind'] ?? null;

        return [
            'issued_on' => $data['issued_on'],
            'currency' => $currency,
            'exchange_rate' => $currency === 'XOF' ? null : ($data['exchange_rate'] ?? null),
            'caf_value' => $data['caf_value'] ?? 0,
            'containers_20' => $data['containers_20'] ?? 0,
            'containers_40' => $data['containers_40'] ?? 0,
            'vat_enabled' => (bool) ($data['vat_enabled'] ?? false),
            'vat_rate' => $data['vat_rate'] ?? Settings::get('vat_rate'),
            'global_discount_kind' => $discountKind,
            'global_discount_value' => $discountKind ? ($data['global_discount_value'] ?? 0) : 0,
            'debours_total' => $t->debours,
            'prestations_subtotal' => $t->prestationsSubtotal,
            'global_discount_amount' => $t->globalDiscount,
            'prestations_ht' => $t->prestationsHt,
            'vat_amount' => $t->vat,
            'prestations_total' => $t->prestationsTotal,
            'total' => $t->total,
        ];
    }

    private function storeLines(Document $document, array $lines, InvoiceTotals $totals): void
    {
        $document->lines()->delete();

        foreach (array_values($lines) as $i => $line) {
            $isPrestation = $line['block'] === InvoiceLine::PRESTATION;
            $discountKind = $isPrestation ? ($line['discount_kind'] ?? null) : null;

            $document->lines()->create([
                'position' => $i + 1,
                'block' => $line['block'],
                'label' => $line['label'],
                'unit' => $line['unit'] ?? 'ea',
                'quantity' => $line['quantity'],
                'unit_price' => $line['unit_price'],
                'discount_kind' => $discountKind,
                'discount_value' => $discountKind ? ($line['discount_value'] ?? 0) : 0,
                'discount_amount' => $totals->lines[$i]->discount,
                'total' => $totals->lines[$i]->total,
            ]);
        }
    }

    private static function money(int $amount): string
    {
        return number_format($amount, 0, ',', ' ').' FCFA';
    }
}