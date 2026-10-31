<?php

namespace App\Modules\Dossiers\Tests;

use App\Models\User;
use App\Modules\Clients\Models\Client;
use App\Modules\Dossiers\Models\Dossier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DossierTest extends TestCase
{
    use RefreshDatabase;

    private function gerant(): User
    {
        return User::factory()->create(['role' => 'gerant']);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function payload(Client $client, array $extra = []): array
    {
        return array_merge(['client_id' => $client->id, 'services' => ['transit', 'dedouanement'], 'description' => 'Ciment'], $extra);
    }

    public function test_les_numeros_sont_attribues_dans_l_ordre(): void
    {
        $client = Client::factory()->create();
        $year = now()->year;
        $this->actingAs($this->gerant());

        $this->postJson('/api/v1/dossiers', $this->payload($client))
            ->assertCreated()
            ->assertJsonPath('data.number', "{$year}-0001")
            ->assertJsonPath('data.status', 'nouvelle')
            ->assertJsonPath('data.tracking.state', 'not_started');

        $this->postJson('/api/v1/dossiers', $this->payload($client))
            ->assertJsonPath('data.number', "{$year}-0002");
    }

    public function test_la_numerotation_repart_a_un_chaque_annee(): void
    {
        $client = Client::factory()->create();
        $this->actingAs($this->gerant());

        $this->travelTo(Carbon::create(2026, 12, 30, 10));
        $this->postJson('/api/v1/dossiers', $this->payload($client))->assertJsonPath('data.number', '2026-0001');

        $this->travelTo(Carbon::create(2027, 1, 5, 9));
        $this->postJson('/api/v1/dossiers', $this->payload($client))->assertJsonPath('data.number', '2027-0001');
    }

    public function test_le_suivi_peut_etre_desactive_a_la_creation(): void
    {
        $client = Client::factory()->create();

        $this->actingAs($this->gerant())
            ->postJson('/api/v1/dossiers', $this->payload($client, ['services' => ['manutention'], 'track' => false]))
            ->assertCreated()
            ->assertJsonPath('data.tracking', null);

        $this->assertDatabaseCount('dossier_trackings', 0);
    }

    public function test_validation_a_la_creation(): void
    {
        $client = Client::factory()->create();
        $this->actingAs($this->gerant());

        $this->postJson('/api/v1/dossiers', ['client_id' => 9999, 'services' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['client_id', 'services']);

        $this->postJson('/api/v1/dossiers', $this->payload($client, ['services' => ['vol']]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('services.0');
    }

    public function test_recherche_et_filtres(): void
    {
        $sahel = Client::factory()->create(['name' => 'Sahel Négoce']);
        $faso = Client::factory()->create(['name' => 'Faso Matériaux']);
        Dossier::factory()->create(['client_id' => $sahel->id, 'number' => '2026-0001', 'services' => ['transit']]);
        $d2 = Dossier::factory()->create([
            'client_id' => $faso->id, 'number' => '2026-0002', 'status' => 'en_cours', 'services' => ['manutention', 'transport'],
        ]);
        $d2->tracking()->create(['bl_lta' => 'MSKU123']);
        $this->actingAs($this->gerant());

        $this->getJson('/api/v1/dossiers')->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/dossiers?q=sahel')->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/dossiers?q=2026-0002')->assertJsonCount(1, 'data')->assertJsonPath('data.0.number', '2026-0002');
        $this->getJson('/api/v1/dossiers?q=MSKU')->assertJsonCount(1, 'data')->assertJsonPath('data.0.number', '2026-0002');
        $this->getJson('/api/v1/dossiers?status=en_cours')->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/dossiers?service=manutention')->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/dossiers?service=transit')->assertJsonCount(1, 'data')->assertJsonPath('data.0.number', '2026-0001');
        $this->getJson("/api/v1/dossiers?client_id={$faso->id}")->assertJsonCount(1, 'data')->assertJsonPath('data.0.number', '2026-0002');
    }

    public function test_la_fiche_inclut_le_client_et_le_suivi(): void
    {
        $dossier = Dossier::factory()->create();
        $dossier->tracking()->create();

        $this->actingAs($this->gerant())
            ->getJson("/api/v1/dossiers/{$dossier->id}")
            ->assertOk()
            ->assertJsonPath('data.client.id', $dossier->client_id)
            ->assertJsonPath('data.tracking.state', 'not_started');
    }

    public function test_la_modification_ne_change_ni_le_client_ni_le_numero(): void
    {
        $dossier = Dossier::factory()->create(['number' => '2026-0042']);
        $autre = Client::factory()->create();

        $this->actingAs($this->gerant())
            ->putJson("/api/v1/dossiers/{$dossier->id}", [
                'services' => ['transport'], 'description' => 'Nouvelle description', 'notes' => 'Une note',
                'client_id' => $autre->id, 'number' => '9999-0001',
            ])
            ->assertOk();

        $this->assertDatabaseHas('dossiers', [
            'id' => $dossier->id, 'client_id' => $dossier->client_id, 'number' => '2026-0042',
            'description' => 'Nouvelle description', 'notes' => 'Une note',
        ]);
    }

    public function test_changement_de_statut_et_regles_de_cloture_et_d_annulation(): void
    {
        $dossier = Dossier::factory()->create();
        $this->actingAs($this->gerant());
        $url = "/api/v1/dossiers/{$dossier->id}/status";

        $this->patchJson($url, ['status' => 'en_cours'])->assertOk()->assertJsonPath('data.status', 'en_cours');
        $this->patchJson($url, ['status' => 'terminee'])->assertOk();

        // Aucune facture soldée n'existe encore : la clôture est refusée.
        $this->patchJson($url, ['status' => 'cloturee'])->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->assertSame('terminee', $dossier->fresh()->status->value);

        $this->patchJson($url, ['status' => 'inconnu'])->assertUnprocessable();
        $this->patchJson($url, ['status' => 'annulee'])->assertOk()->assertJsonPath('data.status_label', 'Annulée');
    }

    public function test_un_gerant_ne_peut_pas_supprimer_un_dossier(): void
    {
        $dossier = Dossier::factory()->create();

        $this->actingAs($this->gerant())->deleteJson("/api/v1/dossiers/{$dossier->id}")->assertForbidden();
        $this->assertNotSoftDeleted('dossiers', ['id' => $dossier->id]);
    }

    public function test_un_admin_peut_supprimer_un_dossier(): void
    {
        $dossier = Dossier::factory()->create();

        $this->actingAs($this->admin())->deleteJson("/api/v1/dossiers/{$dossier->id}")->assertNoContent();
        $this->assertSoftDeleted('dossiers', ['id' => $dossier->id]);
    }

    public function test_un_client_qui_a_des_dossiers_ne_peut_pas_etre_supprime(): void
    {
        $dossier = Dossier::factory()->create();

        $this->actingAs($this->admin())
            ->deleteJson("/api/v1/clients/{$dossier->client_id}")
            ->assertStatus(409);

        $this->assertNotSoftDeleted('clients', ['id' => $dossier->client_id]);
    }

    public function test_les_listes_d_options(): void
    {
        $this->actingAs($this->gerant())
            ->getJson('/api/v1/dossiers/options')
            ->assertOk()
            ->assertJsonCount(5, 'data.statuses')
            ->assertJsonCount(5, 'data.services')
            ->assertJsonPath('data.regimes.0', 'IM4');
    }
}