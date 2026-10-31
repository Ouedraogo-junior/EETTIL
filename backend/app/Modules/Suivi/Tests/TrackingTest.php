<?php

namespace App\Modules\Suivi\Tests;

use App\Models\User;
use App\Modules\Clients\Models\Client;
use App\Modules\Dossiers\Models\Dossier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackingTest extends TestCase
{
    use RefreshDatabase;

    private function login(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'gerant']));
    }

    private function valid(array $extra = []): array
    {
        return array_merge(['nature' => 'ciment en sacs', 'packages' => 1, 'weight_kg' => 24000], $extra);
    }

    private function day(int $daysAgo): string
    {
        return now()->subDays($daysAgo)->toDateString();
    }

    public function test_l_activation_du_suivi_est_idempotente(): void
    {
        $dossier = Dossier::factory()->create();
        $this->login();

        $this->postJson("/api/v1/dossiers/{$dossier->id}/tracking")->assertCreated()->assertJsonPath('data.state', 'not_started');
        $this->postJson("/api/v1/dossiers/{$dossier->id}/tracking")->assertOk();
        $this->assertDatabaseCount('dossier_trackings', 1);
    }

    public function test_nature_colis_et_poids_sont_obligatoires(): void
    {
        $dossier = Dossier::factory()->create();
        $this->login();

        $this->putJson("/api/v1/dossiers/{$dossier->id}/tracking", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nature', 'packages', 'weight_kg']);
    }

    public function test_enregistrement_du_suivi_et_etape_actuelle(): void
    {
        $dossier = Dossier::factory()->create();
        $this->login();

        $this->putJson("/api/v1/dossiers/{$dossier->id}/tracking", $this->valid([
            'bl_lta' => 'MSKU4401877', 'regime' => 'IM4', 'date_ot' => $this->day(3),
        ]))
            ->assertOk()
            ->assertJsonPath('data.nature', 'CIMENT EN SACS')
            ->assertJsonPath('data.packages', 1)
            ->assertJsonPath('data.state', 'in_progress')
            ->assertJsonPath('data.current_step.key', 'date_ot')
            ->assertJsonPath('data.date_ot', $this->day(3));
    }

    public function test_l_etape_actuelle_est_la_derniere_renseignee_meme_dans_le_desordre(): void
    {
        $dossier = Dossier::factory()->create();
        $this->login();

        $this->putJson("/api/v1/dossiers/{$dossier->id}/tracking", $this->valid([
            'date_bae' => $this->day(1), 'date_ot' => $this->day(5),
        ]))
            ->assertOk()
            ->assertJsonPath('data.current_step.key', 'date_bae')
            ->assertJsonPath('data.state', 'in_progress');
    }

    public function test_le_dossier_est_livre_quand_la_livraison_est_saisie(): void
    {
        $dossier = Dossier::factory()->create();
        $this->login();

        $this->putJson("/api/v1/dossiers/{$dossier->id}/tracking", $this->valid(['date_delivery' => $this->day(0)]))
            ->assertOk()
            ->assertJsonPath('data.state', 'delivered')
            ->assertJsonPath('data.state_label', 'Livré');
    }

    public function test_une_date_a_venir_est_refusee(): void
    {
        $dossier = Dossier::factory()->create();
        $this->login();

        $this->putJson("/api/v1/dossiers/{$dossier->id}/tracking", $this->valid(['date_docs' => now()->addDay()->toDateString()]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('date_docs');
    }

    public function test_la_date_de_la_dpi_demande_son_numero(): void
    {
        $dossier = Dossier::factory()->create();
        $this->login();

        $this->putJson("/api/v1/dossiers/{$dossier->id}/tracking", $this->valid(['dpi_date' => $this->day(10)]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('dpi_number');

        $this->putJson("/api/v1/dossiers/{$dossier->id}/tracking", $this->valid(['dpi_date' => $this->day(10), 'dpi_number' => 'C8811']))
            ->assertOk();
    }

    public function test_le_regime_doit_etre_dans_la_liste(): void
    {
        $dossier = Dossier::factory()->create();
        $this->login();

        $this->putJson("/api/v1/dossiers/{$dossier->id}/tracking", $this->valid(['regime' => 'ZZ9']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('regime');
    }

    public function test_ecran_de_suivi_filtres_et_recherche(): void
    {
        $alpha = Client::factory()->create(['name' => 'Alpha Import']);

        $pasCommence = Dossier::factory()->create(['client_id' => $alpha->id]);
        $pasCommence->tracking()->create();

        $enCours = Dossier::factory()->create();
        $enCours->tracking()->create(['bl_lta' => 'LTA-777', 'date_ot' => $this->day(2)]);

        $livre = Dossier::factory()->create();
        $livre->tracking()->create(['date_ot' => $this->day(9), 'date_delivery' => $this->day(1)]);

        $annule = Dossier::factory()->create(['status' => 'annulee']);
        $annule->tracking()->create(['date_ot' => $this->day(4)]);

        Dossier::factory()->create(); // sans suivi
        $this->login();

        $this->getJson('/api/v1/suivi')->assertOk()->assertJsonCount(3, 'data');
        $this->getJson('/api/v1/suivi?state=not_started')->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/suivi?state=in_progress')->assertJsonCount(1, 'data')->assertJsonPath('data.0.bl_lta', 'LTA-777');
        $this->getJson('/api/v1/suivi?state=delivered')->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/suivi?q=LTA-777')->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/suivi?q=alpha')->assertJsonCount(1, 'data');
    }

    public function test_un_visiteur_non_connecte_est_refuse(): void
    {
        $this->getJson('/api/v1/suivi')->assertUnauthorized();
    }
}