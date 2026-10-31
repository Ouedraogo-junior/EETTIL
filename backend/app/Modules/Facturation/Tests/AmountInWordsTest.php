<?php

namespace App\Modules\Facturation\Tests;

use App\Modules\Facturation\Domain\AmountInWords;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class AmountInWordsTest extends TestCase
{
    public function test_montant_de_la_facture_modele_du_client(): void
    {
        $this->assertSame(
            'QUINZE MILLIONS SOIXANTE NEUF MILLE SIX CENT SOIXANTE NEUF',
            AmountInWords::words(15_069_669),
        );
        $this->assertSame(
            'QUINZE MILLIONS SOIXANTE NEUF MILLE SIX CENT SOIXANTE NEUF FRANCS CFA',
            AmountInWords::forInvoice(15_069_669),
        );
    }

    public function test_nombres_particuliers_du_francais(): void
    {
        $cases = [
            0 => 'ZÉRO',
            1 => 'UN',
            16 => 'SEIZE',
            17 => 'DIX SEPT',
            21 => 'VINGT ET UN',
            22 => 'VINGT DEUX',
            71 => 'SOIXANTE ET ONZE',
            77 => 'SOIXANTE DIX SEPT',
            80 => 'QUATRE VINGTS',
            81 => 'QUATRE VINGT UN',
            91 => 'QUATRE VINGT ONZE',
            99 => 'QUATRE VINGT DIX NEUF',
            100 => 'CENT',
            101 => 'CENT UN',
            200 => 'DEUX CENTS',
            201 => 'DEUX CENT UN',
            999 => 'NEUF CENT QUATRE VINGT DIX NEUF',
            1_000 => 'MILLE',
            1_001 => 'MILLE UN',
            2_000 => 'DEUX MILLE',
            21_000 => 'VINGT ET UN MILLE',
            80_000 => 'QUATRE VINGT MILLE',
            200_000 => 'DEUX CENT MILLE',
            1_000_000 => 'UN MILLION',
            2_000_000 => 'DEUX MILLIONS',
            80_000_000 => 'QUATRE VINGTS MILLIONS',
            200_000_000 => 'DEUX CENTS MILLIONS',
            1_000_000_000 => 'UN MILLIARD',
            2_200_000_000 => 'DEUX MILLIARDS DEUX CENTS MILLIONS',
        ];

        foreach ($cases as $number => $expected) {
            $this->assertSame($expected, AmountInWords::words($number), "Montant {$number}");
        }
    }

    public function test_franc_au_singulier_puis_au_pluriel(): void
    {
        $this->assertSame('ZÉRO FRANC CFA', AmountInWords::forInvoice(0));
        $this->assertSame('UN FRANC CFA', AmountInWords::forInvoice(1));
        $this->assertSame('DEUX FRANCS CFA', AmountInWords::forInvoice(2));
    }

    public function test_un_montant_negatif_est_refuse(): void
    {
        $this->expectException(InvalidArgumentException::class);

        AmountInWords::words(-5);
    }
}