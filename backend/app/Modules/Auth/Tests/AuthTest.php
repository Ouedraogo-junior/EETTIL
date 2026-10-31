<?php

namespace App\Modules\Auth\Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Sanctum ne démarre la session que pour les requêtes venant du front déclaré.
        config(['sanctum.stateful' => ['localhost']]);
        $this->withHeader('Referer', 'http://localhost');
    }

    public function test_un_utilisateur_actif_peut_se_connecter(): void
    {
        User::factory()->create(['login' => 'gerant', 'password' => 'secret123']);

        $this->postJson('/api/v1/auth/login', ['login' => 'gerant', 'password' => 'secret123'])
            ->assertOk()
            ->assertJsonPath('data.login', 'gerant')
            ->assertJsonPath('data.role', 'gerant');
    }

    public function test_un_mauvais_mot_de_passe_est_refuse(): void
    {
        User::factory()->create(['login' => 'gerant', 'password' => 'secret123']);

        $this->postJson('/api/v1/auth/login', ['login' => 'gerant', 'password' => 'faux'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('login');
    }

    public function test_un_utilisateur_inactif_ne_peut_pas_se_connecter(): void
    {
        User::factory()->inactive()->create(['login' => 'ancien', 'password' => 'secret123']);

        $this->postJson('/api/v1/auth/login', ['login' => 'ancien', 'password' => 'secret123'])
            ->assertUnprocessable();
    }

    public function test_me_exige_une_connexion(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_me_renvoie_l_utilisateur_connecte(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.role', 'admin');
    }

    public function test_la_deconnexion_fonctionne(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/v1/auth/logout')->assertNoContent();
    }
}