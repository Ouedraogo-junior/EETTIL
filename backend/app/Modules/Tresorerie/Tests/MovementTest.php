<?php

namespace App\Modules\Tresorerie\Tests;

use App\Models\User;
use App\Modules\Dossiers\Models\Dossier;
use App\Modules\Tresorerie\Models\Account;
use App\Modules\Tresorerie\Models\MovementCategory;
use Database\Seeders\TreasurySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class MovementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TreasurySeeder::class);
    }

    private function login(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'gerant']));
    }

    private function cash(): Account
    {
        return Account::query()->where('type', 'caisse')->firstOrFail();
    }

    private function bank(): Account
    {
        return Account::query()->where('type', 'banque')->firstOrFail();
    }

    private function category(string $name): MovementCategory
    {
        return MovementCategory::query()->where('name', $name)->firstOrFail();
    }

    private function move(array $override = []): TestResponse
    {
        return $this->postJson('/api/v1/movements', array_merge([
            'direction' => 'in',
            'moved_on' => now()->toDateString(),
            'label' => 'Avance de l’associé',
            'category_id' => $this->category('Autre entrée')->id,
            'amount' => 500_000,
            'account_id' => $this->cash()->id,
        ], $override));
    }

    private function expense(array $override = []): TestResponse
    {
        return $this->move(array_merge([
            'direction' => 'out',
            'label' => 'Loyer du mois',
            'category_id' => $this->category('Loyer et charges')->id,
            'amount' => 200_000,
        ], $override));
    }

    public function test_entrees_sorties_et_soldes(): void
    {
        $this->login();

        $this->move()->assertCreated()->assertJsonPath('data.direction', 'in')->assertJsonPath('data.source', 'manual');
        $this->expense()->assertCreated()->assertJsonPath('data.category.name', 'Loyer et charges');

        $this->getJson('/api/v1/accounts')
            ->assertOk()
            ->assertJsonPath('total', 300_000)
            ->assertJsonPath('data.0.balance', 300_000)
            ->assertJsonPath('data.1.balance', 0);
    }

    public function test_le_solde_initial_compte_dans_le_solde(): void
    {
        $this->login();
        $this->bank()->update(['initial_balance' => 20_000_000]);
        $this->move(['account_id' => $this->bank()->id, 'amount' => 1_000_000])->assertCreated();

        $this->getJson('/api/v1/accounts')->assertJsonPath('data.1.balance', 21_000_000);
    }

    public function test_une_sortie_ne_depasse_pas_le_solde_de_la_caisse(): void
    {
        $this->login();

        $this->expense(['amount' => 100])->assertUnprocessable()->assertJsonValidationErrors('amount');

        $this->move(['amount' => 150_000])->assertCreated();
        $this->expense(['amount' => 150_001])->assertUnprocessable()->assertJsonValidationErrors('amount');
        $this->expense(['amount' => 150_000])->assertCreated();

        // La règle ne vaut que pour la caisse : la banque peut être à découvert
        $this->expense(['account_id' => $this->bank()->id, 'amount' => 50_000])->assertCreated();
    }

    public function test_un_debours_se_rattache_a_un_dossier(): void
    {
        $this->login();
        $this->move(['amount' => 1_000_000])->assertCreated();
        $dossier = Dossier::factory()->create();
        $debours = $this->category('Débours')->id;

        $this->expense(['category_id' => $debours])->assertUnprocessable()->assertJsonValidationErrors('dossier_id');

        $this->expense(['category_id' => $debours, 'dossier_id' => $dossier->id])
            ->assertCreated()
            ->assertJsonPath('data.is_debours', true)
            ->assertJsonPath('data.dossier.id', $dossier->id);
    }

    public function test_une_depense_se_rattache_a_un_dossier_actif_seulement(): void
    {
        $this->login();
        $this->move(['amount' => 1_000_000])->assertCreated();
        $annule = Dossier::factory()->create(['status' => 'annulee']);

        $this->expense(['dossier_id' => $annule->id])->assertUnprocessable()->assertJsonValidationErrors('dossier_id');
        $this->expense(['dossier_id' => 9999])->assertUnprocessable()->assertJsonValidationErrors('dossier_id');
    }

    public function test_categorie_incoherente_ou_reservee_aux_paiements(): void
    {
        $this->login();

        // Une catégorie de sortie sur une entrée
        $this->move(['category_id' => $this->category('Loyer et charges')->id])->assertUnprocessable()->assertJsonValidationErrors('category_id');
        // « Paiement client » ne se saisit pas à la main
        $this->move(['category_id' => MovementCategory::payment()->id])->assertUnprocessable()->assertJsonValidationErrors('category_id');
    }

    public function test_validation_des_champs(): void
    {
        $this->login();

        $this->move(['amount' => 0])->assertUnprocessable()->assertJsonValidationErrors('amount');
        $this->move(['label' => ''])->assertUnprocessable()->assertJsonValidationErrors('label');
        $this->move(['moved_on' => now()->addDay()->toDateString()])->assertUnprocessable()->assertJsonValidationErrors('moved_on');
        $this->move(['direction' => 'autre'])->assertUnprocessable()->assertJsonValidationErrors('direction');
        $this->move(['account_id' => 9999])->assertUnprocessable()->assertJsonValidationErrors('account_id');

        $this->assertDatabaseCount('movements', 0);
    }

    public function test_annulation_d_un_mouvement_saisi_a_la_main(): void
    {
        $this->login();
        $id = $this->move()->json('data.id');

        $this->postJson("/api/v1/movements/{$id}/cancel", [])->assertUnprocessable()->assertJsonValidationErrors('reason');
        $this->postJson("/api/v1/movements/{$id}/cancel", ['reason' => 'Saisi deux fois'])
            ->assertOk()
            ->assertJsonPath('data.cancelled', true)
            ->assertJsonPath('data.cancel_reason', 'Saisi deux fois');

        $this->getJson('/api/v1/accounts')->assertJsonPath('total', 0);
        $this->postJson("/api/v1/movements/{$id}/cancel", ['reason' => 'Encore'])->assertUnprocessable();
    }

    public function test_une_entree_de_paiement_ne_s_annule_pas_ici(): void
    {
        $this->login();
        $dossier = Dossier::factory()->create();
        $doc = $this->postJson("/api/v1/dossiers/{$dossier->id}/documents", [
            'type' => 'facture', 'issued_on' => now()->toDateString(), 'currency' => 'XOF', 'vat_enabled' => false, 'vat_rate' => 18,
            'lines' => [['block' => 'P', 'label' => 'Honoraires', 'quantity' => 1, 'unit_price' => 100_000]],
        ])->assertCreated()->json('data.id');
        $this->postJson("/api/v1/documents/{$doc}/payments", [
            'paid_on' => now()->toDateString(), 'amount' => 100_000, 'mode' => 'especes', 'account_id' => $this->cash()->id,
        ])->assertCreated();

        $movementId = $this->getJson('/api/v1/movements')->json('data.0.id');
        $this->getJson('/api/v1/movements')->assertJsonPath('data.0.source', 'payment');

        $this->postJson("/api/v1/movements/{$movementId}/cancel", ['reason' => 'Test'])
            ->assertUnprocessable()->assertJsonValidationErrors('movement');
    }

    public function test_liste_filtres_et_totaux(): void
    {
        $this->login();
        $dossier = Dossier::factory()->create();
        $this->move(['amount' => 1_000_000])->assertCreated();
        $this->move(['amount' => 300_000, 'account_id' => $this->bank()->id, 'label' => 'Apport banque'])->assertCreated();
        $this->expense(['amount' => 200_000, 'dossier_id' => $dossier->id, 'label' => 'Transport sous-traité'])->assertCreated();
        $annule = $this->expense(['amount' => 50_000, 'label' => 'Erreur'])->json('data.id');
        $this->postJson("/api/v1/movements/{$annule}/cancel", ['reason' => 'Erreur'])->assertOk();

        // Tout : 4 lignes dont une annulée, qui n'entre pas dans les totaux
        $this->getJson('/api/v1/movements')
            ->assertOk()->assertJsonCount(4, 'data')->assertJsonPath('totals.in', 1_300_000)->assertJsonPath('totals.out', 200_000);

        $this->getJson('/api/v1/movements?direction=out')->assertJsonCount(2, 'data')->assertJsonPath('totals.out', 200_000);
        $this->getJson('/api/v1/movements?account_id='.$this->bank()->id)->assertJsonCount(1, 'data')->assertJsonPath('totals.in', 300_000);
        $this->getJson("/api/v1/movements?dossier_id={$dossier->id}")->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/movements?q=Apport')->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/movements?q='.urlencode($dossier->number))->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/movements?category_id='.$this->category('Autre entrée')->id)->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/movements?from='.now()->addDay()->toDateString())->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/movements?to='.now()->toDateString())->assertJsonCount(4, 'data');
    }

    public function test_options_de_saisie(): void
    {
        $this->login();

        $this->getJson('/api/v1/movements/options')
            ->assertOk()
            ->assertJsonCount(2, 'data.accounts')
            ->assertJsonPath('data.categories.in.0.name', 'Paiement client')
            ->assertJsonPath('data.categories.in.0.is_system', true)
            ->assertJsonCount(10, 'data.categories.out')
            ->assertJsonPath('data.categories.out.0.name', 'Débours')
            ->assertJsonPath('data.categories.out.0.is_debours', true);
    }

    public function test_un_visiteur_non_connecte_est_refuse(): void
    {
        $this->getJson('/api/v1/accounts')->assertUnauthorized();
        $this->getJson('/api/v1/movements')->assertUnauthorized();
    }
}