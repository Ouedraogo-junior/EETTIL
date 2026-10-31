<?php

namespace App\Modules\Facturation\Domain;

/** Résultat du calcul d'un document entier. Tous les montants sont en francs CFA entiers. */
final readonly class InvoiceTotals
{
    /**
     * @param  list<LineTotals>  $lines
     */
    public function __construct(
        public array $lines,
        public int $debours,               // total A : somme des débours, sans TVA ni remise
        public int $prestationsSubtotal,   // somme des lignes de prestations, après remises de ligne
        public int $globalDiscount,        // remise globale sur les prestations
        public bool $globalOverLimit,
        public int $prestationsHt,         // sous-total - remise globale
        public int $vat,                   // TVA sur les prestations HT uniquement
        public int $prestationsTotal,      // total B : prestations HT + TVA
        public int $total,                 // A + B
    ) {}

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'lines' => array_map(fn (LineTotals $l) => $l->toArray(), $this->lines),
            'debours' => $this->debours,
            'prestations_subtotal' => $this->prestationsSubtotal,
            'global_discount' => $this->globalDiscount,
            'global_over_limit' => $this->globalOverLimit,
            'prestations_ht' => $this->prestationsHt,
            'vat' => $this->vat,
            'prestations_total' => $this->prestationsTotal,
            'total' => $this->total,
            'total_in_words' => AmountInWords::forInvoice($this->total),
        ];
    }
}