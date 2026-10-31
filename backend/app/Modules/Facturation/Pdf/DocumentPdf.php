<?php

namespace App\Modules\Facturation\Pdf;

use App\Modules\Facturation\Domain\AmountInWords;
use App\Modules\Facturation\Enums\DocumentState;
use App\Modules\Facturation\Enums\DocumentType;
use App\Modules\Facturation\Models\Document;
use App\Support\Settings;
use Barryvdh\DomPDF\Facade\Pdf;

/** Document imprimable (PDF A4) sur le modèle du client : en-tête et pied de page EETTIL, blocs A et B, montant en lettres. */
class DocumentPdf
{
    /** Montant au format français, avec espaces insécables : 15 069 669 */
    public static function money(int $amount): string
    {
        return number_format($amount, 0, ',', "\u{00A0}");
    }

    private static function xof(int $amount): string
    {
        return self::money($amount)."\u{00A0}XOF";
    }

    private static function number(float $value, int $max = 3): string
    {
        $text = number_format($value, $max, ',', '');

        return str_contains($text, ',') ? rtrim(rtrim($text, '0'), ',') : $text;
    }

    /** Nom du fichier : « FCT-2026-000-011 - Nom du client.pdf ». */
    public static function filename(Document $document): string
    {
        $client = $document->dossier?->client?->name ?? '';
        $name = str_replace('/', '-', $document->number).($client !== '' ? ' - '.$client : '');
        $name = trim(preg_replace('/[\\\\\/:*?"<>|\x00-\x1F]+/u', ' ', $name));

        return preg_replace('/\s+/u', ' ', $name).'.pdf';
    }

    /**
     * Données du modèle d'impression. Le document doit avoir ses lignes, son dossier et le client chargés.
     *
     * @return array<string,mixed>
     */
    public function data(Document $document): array
    {
        $facture = $document->type === DocumentType::Facture;
        $dossier = $document->dossier;
        $client = $dossier?->client;

        $debours = [];
        $prestations = [];
        foreach ($document->lines as $line) {
            $row = [
                'label' => $line->label,
                'note' => $this->discountNote($line),
                'unit' => $line->unit,
                'qty' => self::number((float) $line->quantity),
                'price' => self::money($line->unit_price),
                'total' => self::xof($line->total),
            ];
            if ($line->block === 'D') {
                $debours[] = $row;
            } else {
                $prestations[] = $row;
            }
        }

        $t = $document;
        $rate = (float) $document->vat_rate;

        return [
            'type' => $document->type->value,
            'title' => $facture ? 'FACTURE' : 'QUOTATION',
            'noun' => $facture ? 'facture' : 'quotation',
            'number' => $document->number,
            'date' => $document->issued_on->format('d/m/Y'),
            'dossier_ref' => ($dossier?->number ?? '–').' / '.($dossier?->tracking?->bl_lta ?: '–'),
            'cancelled' => $document->state === DocumentState::Annule,
            'client' => [
                'name' => mb_strtoupper($client?->name ?? ''),
                'address' => $client?->address,
                'ifu' => $client?->ifu,
                'rccm' => $client?->rccm,
            ],
            'meta' => [
                'currency' => $document->currency,
                'rate' => $document->exchange_rate !== null && (float) $document->exchange_rate > 0
                    ? self::number((float) $document->exchange_rate, 4) : '–',
                'caf' => $document->caf_value > 0 ? self::xof($document->caf_value) : '–',
                'containers' => $document->containers_20.' / '.$document->containers_40,
            ],
            'debours' => $debours,
            'debours_title' => 'A. DÉBOURS',
            'debours_total' => self::xof($t->debours_total),
            'prestations' => $prestations,
            'prestations_title' => $debours ? 'B. PRESTATIONS' : 'PRESTATIONS',
            'sub' => self::xof($t->prestations_subtotal),
            'global_discount' => $t->global_discount_amount > 0 ? '− '.self::xof($t->global_discount_amount) : null,
            'ht_after' => $t->global_discount_amount > 0 ? self::xof($t->prestations_ht) : null,
            'vat_rate' => $document->vat_enabled ? number_format($rate, 1, ',', '') : '0,0',
            'vat' => self::xof($t->vat_amount),
            'prestations_total_label' => trim('TOTAL PRESTATIONS '.($document->vat_enabled ? 'TTC ' : '').($debours ? '(B)' : '')),
            'prestations_total' => self::xof($t->prestations_total),
            'grand_label' => 'MONTANT TOTAL DE LA '.mb_strtoupper($facture ? 'facture' : 'quotation').' (XOF)',
            'grand' => self::xof($t->total),
            'words' => AmountInWords::forInvoice($t->total),
            'bank' => [
                'name' => Settings::get('bank_name'),
                'rib' => Settings::get('bank_rib'),
                'swift' => Settings::get('bank_swift'),
            ],
            'sign' => ['title' => Settings::get('signatory_title'), 'name' => Settings::get('signatory_name')],
            'letterhead' => $this->letterhead(),
        ];
    }

    /** PDF prêt à être envoyé. */
    public function render(Document $document): string
    {
        return Pdf::loadView('pdf.document', ['d' => $this->data($document)])
            ->setPaper('a4', 'portrait')
            ->output();
    }

    private function discountNote(object $line): ?string
    {
        if (! $line->discount_kind || $line->discount_amount <= 0) {
            return null;
        }
        $prefix = $line->discount_kind === 'pct' ? 'Remise '.self::number((float) $line->discount_value, 2)."\u{00A0}%" : 'Remise';

        return $prefix.' : − '.self::money($line->discount_amount);
    }

    /** Papier à en-tête (en-tête et pied de page) en image intégrée, ou null s'il est absent. */
    private function letterhead(): ?string
    {
        $path = resource_path('pdf/entete.jpg');

        return is_file($path) ? 'data:image/jpeg;base64,'.base64_encode((string) file_get_contents($path)) : null;
    }
}