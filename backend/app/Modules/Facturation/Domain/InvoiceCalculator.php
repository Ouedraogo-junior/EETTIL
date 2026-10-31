<?php

namespace App\Modules\Facturation\Domain;

/**
 * Règles de calcul d'un document (proforma ou facture) :
 *  - A. Débours : repris à leur montant exact, sans TVA ni remise ;
 *  - B. Prestations : remises (par ligne puis globale, en % ou en montant, plafonnées au montant concerné),
 *    puis TVA facultative appliquée uniquement aux prestations HT ;
 *  - Montant total = A + B.
 */
final class InvoiceCalculator
{
    /**
     * @param  list<InvoiceLine>  $lines
     * @param  ?string  $globalDiscountKind  'pct', 'amt' ou null
     */
    public function calculate(
        array $lines,
        ?string $globalDiscountKind = null,
        float $globalDiscountValue = 0,
        bool $vatEnabled = false,
        float $vatRate = 18,
    ): InvoiceTotals {
        $lineTotals = [];
        $debours = 0;
        $subtotal = 0;

        foreach (array_values($lines) as $i => $line) {
            $gross = self::round($line->quantity * $line->unitPrice);

            if ($line->isDebours()) {
                $lineTotals[] = new LineTotals($i, $gross, 0, false, $gross);
                $debours += $gross;

                continue;
            }

            [$discount, $over] = self::discount($gross, $line->discountKind, $line->discountValue);
            $total = $gross - $discount;
            $lineTotals[] = new LineTotals($i, $gross, $discount, $over, $total);
            $subtotal += $total;
        }

        [$globalDiscount, $globalOver] = self::discount($subtotal, $globalDiscountKind, $globalDiscountValue);
        $ht = $subtotal - $globalDiscount;
        $vat = $vatEnabled ? self::round($ht * $vatRate / 100) : 0;
        $prestationsTotal = $ht + $vat;

        return new InvoiceTotals(
            $lineTotals, $debours, $subtotal, $globalDiscount, $globalOver, $ht, $vat, $prestationsTotal, $debours + $prestationsTotal,
        );
    }

    /**
     * Remise plafonnée entre 0 et le montant concerné.
     *
     * @return array{0:int, 1:bool} [remise appliquée, la remise saisie dépassait-elle le montant ?]
     */
    private static function discount(int $base, ?string $kind, float $value): array
    {
        $raw = match ($kind) {
            InvoiceLine::DISCOUNT_PERCENT => $base * $value / 100,
            InvoiceLine::DISCOUNT_AMOUNT => $value,
            default => 0.0,
        };

        $over = $raw > $base;

        return [self::round(min(max($raw, 0), $base)), $over];
    }

    /** Arrondi au franc le plus proche (la demie monte), identique à la maquette validée. */
    private static function round(float|int $x): int
    {
        return (int) floor($x + 0.5);
    }
}