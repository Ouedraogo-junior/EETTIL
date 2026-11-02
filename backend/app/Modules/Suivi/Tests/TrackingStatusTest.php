<?php

namespace App\Modules\Suivi\Tests;

use App\Models\User;
use App\Modules\Dossiers\Models\Dossier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackingStatusTest extends TestCase
{
    use RefreshDatabase;

    private function dossier(string $status): Dossier
    {
        $this->actingAs(User::factory()->create(['role' => 'gerant']));
        $dossier = Dossier::factory()->create(['status' => $status]);
        $this->postJson("/api/v1/dossiers/{$dossier->id}/tracking")->assertSuccessful();

        return $dossier;
    }

    private function save(Dossier $dossier, array $dates = []): void
    {
        $this->putJson("/api/v1/dossiers/{$dossier->id}/tracking", array_merge([
            'supplier_invoices' => '', 'dpi_number' => '', 'dpi_date' => '', 'bl_lta' => '',
            'transit_order_number' => '', 'regime' => '', 'comments' => '',
            'nature' => 'Ciment', 'packages' => 10, 'weight_kg' => 500,
            'date_ot' => '', 'date_arrival' => '', 'date_docs' => '',
            'date_declaration' => '', 'date_bae' => '', 'date_delivery' => '',
        ], $dates))->assertOk();
    }

    public function test_la_premiere_date_fait_passer_le_dossier_en_cours(): void
    {
        $dossier = $this->dossier('nouvelle');

        $this->save($dossier, ['date_ot' => now()->toDateString()]);

        $this->assertSame('en_cours', $dossier->refresh()->status->value);
    }

    public function test_sans_date_le_dossier_reste_nouveau(): void
    {
        $dossier = $this->dossier('nouvelle');

        $this->save($dossier);

        $this->assertSame('nouvelle', $dossier->refresh()->status->value);
    }

    public function test_la_livraison_ne_termine_pas_le_dossier(): void
    {
        $dossier = $this->dossier('nouvelle');

        $this->save($dossier, ['date_delivery' => now()->toDateString()]);

        $this->assertSame('en_cours', $dossier->refresh()->status->value);
    }

    public function test_un_dossier_termine_ne_change_pas(): void
    {
        $dossier = $this->dossier('terminee');

        $this->save($dossier, ['date_ot' => now()->toDateString()]);

        $this->assertSame('terminee', $dossier->refresh()->status->value);
    }

    public function test_effacer_les_dates_ne_ramene_pas_le_dossier_en_arriere(): void
    {
        $dossier = $this->dossier('nouvelle');
        $this->save($dossier, ['date_ot' => now()->toDateString()]);

        $this->save($dossier);

        $this->assertSame('en_cours', $dossier->refresh()->status->value);
    }
}