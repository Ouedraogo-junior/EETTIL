<?php

namespace App\Modules\Clients\Tests;

use App\Models\User;
use App\Modules\Clients\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientTest extends TestCase
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

    public function test_un_visiteur_non_connecte_est_refuse(): void
    {
        $this->getJson('/api/v1/clients')->assertUnauthorized();
    }

    public function test_un_gerant_cree_un_client_et_l_ifu_est_mis_en_majuscules(): void
    {
        $this->actingAs($this->gerant())
            ->postJson('/api/v1/clients', ['name' => 'Sahel Négoce', 'ifu' => '00000001a', 'phone' => '+226 70 00 00 01'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Sahel Négoce')
            ->assertJsonPath('data.ifu', '00000001A');

        $this->assertDatabaseHas('clients', ['name' => 'Sahel Négoce', 'ifu' => '00000001A']);
    }

    public function test_le_nom_est_obligatoire(): void
    {
        $this->actingAs($this->gerant())
            ->postJson('/api/v1/clients', ['name' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    public function test_l_ifu_doit_etre_unique(): void
    {
        Client::factory()->create(['ifu' => '123A']);

        $this->actingAs($this->gerant())
            ->postJson('/api/v1/clients', ['name' => 'Autre', 'ifu' => '123a'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ifu');
    }

    public function test_un_client_peut_garder_son_propre_ifu_en_modification(): void
    {
        $client = Client::factory()->create(['ifu' => '123A']);

        $this->actingAs($this->gerant())
            ->putJson("/api/v1/clients/{$client->id}", ['name' => 'Nouveau nom', 'ifu' => '123A'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Nouveau nom');
    }

    public function test_plusieurs_clients_peuvent_ne_pas_avoir_d_ifu(): void
    {
        $user = $this->gerant();

        $this->actingAs($user)->postJson('/api/v1/clients', ['name' => 'A', 'ifu' => ''])->assertCreated();
        $this->actingAs($user)->postJson('/api/v1/clients', ['name' => 'B', 'ifu' => ''])->assertCreated();
    }

    public function test_la_recherche_porte_sur_le_nom_l_ifu_et_le_telephone(): void
    {
        Client::factory()->create(['name' => 'Sahel Négoce', 'ifu' => '111A', 'phone' => '+226 70 11 22 33']);
        Client::factory()->create(['name' => 'Faso Matériaux', 'ifu' => '222B', 'phone' => '+226 76 44 55 66']);
        Client::factory()->create(['name' => 'Kadiogo Pharma', 'ifu' => '333C', 'phone' => '+226 78 77 88 99']);
        $this->actingAs($this->gerant());

        $this->getJson('/api/v1/clients?q=sahel')->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/clients?q=222B')->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Faso Matériaux');
        $this->getJson('/api/v1/clients?q=78778899')->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Kadiogo Pharma');
        $this->getJson('/api/v1/clients')->assertJsonCount(3, 'data');
    }

    public function test_la_liste_est_paginee(): void
    {
        Client::factory()->count(25)->create();

        $this->actingAs($this->gerant())
            ->getJson('/api/v1/clients')
            ->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.total', 25);
    }

    public function test_modification_d_un_client(): void
    {
        $client = Client::factory()->create();

        $this->actingAs($this->gerant())
            ->putJson("/api/v1/clients/{$client->id}", ['name' => 'Renommé', 'address' => 'Bobo-Dioulasso'])
            ->assertOk();

        $this->assertDatabaseHas('clients', ['id' => $client->id, 'name' => 'Renommé', 'address' => 'Bobo-Dioulasso']);
    }

    public function test_un_gerant_ne_peut_pas_supprimer(): void
    {
        $client = Client::factory()->create();

        $this->actingAs($this->gerant())->deleteJson("/api/v1/clients/{$client->id}")->assertForbidden();
        $this->assertNotSoftDeleted('clients', ['id' => $client->id]);
    }

    public function test_un_admin_peut_supprimer(): void
    {
        $client = Client::factory()->create();

        $this->actingAs($this->admin())->deleteJson("/api/v1/clients/{$client->id}")->assertNoContent();
        $this->assertSoftDeleted('clients', ['id' => $client->id]);
    }
}