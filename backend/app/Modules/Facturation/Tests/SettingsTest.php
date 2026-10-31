<?php

namespace App\Modules\Facturation\Tests;

use App\Support\Counter;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_les_parametres_ont_des_valeurs_par_defaut_modifiables(): void
    {
        $this->assertSame('000', Settings::get('series'));
        $this->assertSame(18, Settings::get('vat_rate'));

        Settings::set('series', '001');
        Settings::set('vat_rate', 10);

        $this->assertSame('001', Settings::get('series'));
        $this->assertSame(10, Settings::get('vat_rate'));
        $this->assertSame('FCT', Settings::all()['invoice_prefix']);
    }

    public function test_le_compteur_avance_et_peut_reprendre_la_numerotation_existante(): void
    {
        $this->assertSame(1, Counter::next('facture-2030'));
        $this->assertSame(2, Counter::next('facture-2030'));

        Counter::set('facture-2031', 10);   // dernière facture déjà émise : n° 010
        $this->assertSame(11, Counter::next('facture-2031'));
    }
}