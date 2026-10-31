<?php

namespace App\Modules\Facturation\Tests;

use App\Modules\Facturation\Domain\InvoiceCalculator;
use App\Modules\Facturation\Domain\InvoiceLine;
use PHPUnit\Framework\TestCase;

class InvoiceCalculatorTest extends TestCase
{
    private function debours(int $price, float $qty = 1, ?string $kind = null, float $value = 0): InvoiceLine
    {
        return new InvoiceLine('D', 'Débours', 'ea', $qty, $price, $kind, $value);
    }

    private function prestation(int $price, float $qty = 1, ?string $kind = null, float $value = 0): InvoiceLine
    {
        return new InvoiceLine('P', 'Prestation', 'ea', $qty, $price, $kind, $value);
    }

    private function calc(array $lines, ?string $gk = null, float $gv = 0, bool $vat = false, float $rate = 18)
    {
        return (new InvoiceCalculator)->calculate($lines, $gk, $gv, $vat, $rate);
    }

    public function test_facture_modele_du_client_au_franc_pres(): void
    {
        $t = $this->calc([
            $this->debours(0), $this->debours(0), $this->debours(29_000), $this->debours(21_050),
            $this->debours(15_000), $this->debours(13_728_619),
            $this->prestation(10_000), $this->prestation(1_175_000), $this->prestation(75_000), $this->prestation(16_000),
        ]);

        $this->assertSame(13_793_669, $t->debours);
        $this->assertSame(1_276_000, $t->prestationsSubtotal);
        $this->assertSame(1_276_000, $t->prestationsHt);
        $this->assertSame(0, $t->vat);
        $this->assertSame(1_276_000, $t->prestationsTotal);
        $this->assertSame(15_069_669, $t->total);
        $this->assertSame(
            'QUINZE MILLIONS SOIXANTE NEUF MILLE SIX CENT SOIXANTE NEUF FRANCS CFA',
            $t->toArray()['total_in_words'],
        );
    }

    public function test_les_debours_n_ont_ni_tva_ni_remise(): void
    {
        $t = $this->calc(
            [$this->debours(1_000_000, 1, 'pct', 50), $this->prestation(100_000)],
            'pct', 10, true, 18,
        );

        $this->assertSame(1_000_000, $t->debours);          // la remise saisie sur un débours est ignorée
        $this->assertSame(0, $t->lines[0]->discount);
        $this->assertSame(90_000, $t->prestationsHt);        // remise globale de 10 % sur les prestations seulement
        $this->assertSame(16_200, $t->vat);                  // 18 % de 90 000, jamais sur les débours
        $this->assertSame(106_200, $t->prestationsTotal);
        $this->assertSame(1_106_200, $t->total);
    }

    public function test_la_tva_s_applique_uniquement_aux_prestations(): void
    {
        $t = $this->calc([$this->debours(1_000_000), $this->prestation(100_000)], null, 0, true, 18);

        $this->assertSame(18_000, $t->vat);
        $this->assertSame(118_000, $t->prestationsTotal);
        $this->assertSame(1_118_000, $t->total);
    }

    public function test_sans_tva_le_montant_de_la_tva_est_nul(): void
    {
        $t = $this->calc([$this->prestation(100_000)], null, 0, false, 18);

        $this->assertSame(0, $t->vat);
        $this->assertSame(100_000, $t->total);
    }

    public function test_taux_de_tva_personnalise(): void
    {
        $t = $this->calc([$this->prestation(200_000)], null, 0, true, 10);

        $this->assertSame(20_000, $t->vat);
        $this->assertSame(220_000, $t->total);
    }

    public function test_remise_de_ligne_en_pourcentage_et_en_montant(): void
    {
        $t = $this->calc([
            $this->prestation(50_000, 2, 'pct', 10),    // 100 000 - 10 %
            $this->prestation(80_000, 1, 'amt', 5_000), // 80 000 - 5 000
        ]);

        $this->assertSame(100_000, $t->lines[0]->gross);
        $this->assertSame(10_000, $t->lines[0]->discount);
        $this->assertSame(90_000, $t->lines[0]->total);
        $this->assertSame(75_000, $t->lines[1]->total);
        $this->assertSame(165_000, $t->prestationsSubtotal);
    }

    public function test_une_remise_ne_peut_pas_depasser_le_montant_de_la_ligne(): void
    {
        $t = $this->calc([
            $this->prestation(100_000, 1, 'pct', 150),
            $this->prestation(40_000, 1, 'amt', 90_000),
        ]);

        $this->assertTrue($t->lines[0]->overLimit);
        $this->assertSame(100_000, $t->lines[0]->discount);
        $this->assertSame(0, $t->lines[0]->total);
        $this->assertTrue($t->lines[1]->overLimit);
        $this->assertSame(40_000, $t->lines[1]->discount);
        $this->assertSame(0, $t->total);
    }

    public function test_une_remise_negative_est_ramenee_a_zero(): void
    {
        $t = $this->calc([$this->prestation(100_000, 1, 'amt', -500)]);

        $this->assertSame(0, $t->lines[0]->discount);
        $this->assertSame(100_000, $t->total);
    }

    public function test_remise_globale_puis_tva(): void
    {
        $t = $this->calc(
            [$this->prestation(200_000, 1, 'pct', 10), $this->prestation(100_000)],
            'pct', 10, true, 18,
        );

        $this->assertSame(280_000, $t->prestationsSubtotal);  // 180 000 + 100 000
        $this->assertSame(28_000, $t->globalDiscount);
        $this->assertSame(252_000, $t->prestationsHt);
        $this->assertSame(45_360, $t->vat);
        $this->assertSame(297_360, $t->prestationsTotal);
        $this->assertSame(297_360, $t->total);
    }

    public function test_remise_globale_en_montant_plafonnee(): void
    {
        $t = $this->calc([$this->prestation(100_000)], 'amt', 250_000);

        $this->assertTrue($t->globalOverLimit);
        $this->assertSame(100_000, $t->globalDiscount);
        $this->assertSame(0, $t->prestationsHt);
        $this->assertSame(0, $t->total);
    }

    public function test_arrondi_au_franc_le_plus_proche(): void
    {
        $t = $this->calc([
            $this->prestation(333, 1.5),             // 499,5 -> 500
            $this->prestation(1_000, 1, 'pct', 7.5), // remise 75
        ], null, 0, true, 18);

        $this->assertSame(500, $t->lines[0]->total);
        $this->assertSame(75, $t->lines[1]->discount);
        $this->assertSame(1_425, $t->prestationsSubtotal);   // 500 + 925
        $this->assertSame(257, $t->vat);                     // 1 425 × 18 % = 256,5 -> 257
    }

    public function test_quantite_decimale(): void
    {
        $t = $this->calc([$this->prestation(75_000, 0.5), $this->debours(10_000, 2.5)]);

        $this->assertSame(37_500, $t->prestationsSubtotal);
        $this->assertSame(25_000, $t->debours);
        $this->assertSame(62_500, $t->total);
    }

    public function test_document_sans_ligne(): void
    {
        $t = $this->calc([]);

        $this->assertSame(0, $t->total);
        $this->assertSame('ZÉRO FRANC CFA', $t->toArray()['total_in_words']);
    }

    public function test_le_total_est_toujours_debours_plus_prestations(): void
    {
        $t = $this->calc(
            [$this->debours(123_457), $this->debours(9_999, 3), $this->prestation(45_678, 3, 'pct', 12.5), $this->prestation(10_001)],
            'amt', 3_333, true, 18,
        );

        $this->assertSame($t->debours + $t->prestationsTotal, $t->total);
        $this->assertSame($t->prestationsHt + $t->vat, $t->prestationsTotal);
        $this->assertSame($t->prestationsSubtotal - $t->globalDiscount, $t->prestationsHt);
    }
}