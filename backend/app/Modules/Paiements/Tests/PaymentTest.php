<?php

namespace App\Modules\Paiements\Tests;

use App\Models\User;
use App\Modules\Dossiers\Models\Dossier;
use App\Modules\Tresorerie\Models\Account;
use App\Modules\Tresorerie\Models\Movement;
use Database\Seeders\TreasurySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PaymentTest extends TestCase
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

    private function bank(): Account
    {
        return Account::query()->where('type', 'banque')->firstOrFail();
    }

    private function cash(): Account
    {
        return Account::query()->where('type', 'caisse')->firstOrFail();
    }

    /** Crée une facture d'une seule prestation du montant voulu et renvoie son identifiant. */
    private function facture(Dossier $dossier, int $total = 1_000_000, string $type = 'facture'): int
    {
        return $this->postJson("/api/v1/dossiers/{$dossier->id}/documents", [
            'type' => $type,
            'issued_on' => now()->toDateString(),
            'currency' => 'XOF',
            'vat_enabled' => false,
            'vat_rate' => 18,
            'lines' => [['block' => 'P', 'label' => 'Honoraires', 'unit' => 'ea', 'quantity' => 1, 'unit_price' => $total]],
        ])->assertCreated()->json('data.id');
    }

    private function pay(int $documentId, array $override = []): TestResponse
    {
        return $this->postJson("/api/v1/documents/{$documentId}/payments", array_merge([
            'paid_on' => now()->toDateString(),
            'amount' => 400_000,
            'mode' => 'virement',
            'account_id' => $this->bank()->id,
        ], $override));
    }

    public function test_un_paiement_partiel_cree_l_entree_de_tresorerie(): void
    {
        $this->login();
        $dossier = Dossier::factory()->create();
        $id = $this->facture($dossier);

        $this->pay($id)
            ->assertCreated()
            ->assertJsonPath('data.amount', 400_000)
            ->assertJsonPath('data.reference', 'REC-0001')
            ->assertJsonPath('data.mode_label', 'Virement')
            ->assertJsonPath('invoice.paid', 400_000)
            ->assertJsonPath('invoice.rest', 600_000)
            ->assertJsonPath('invoice.state', 'partial');

        $this->assertDatabaseHas('movements', [
            'account_id' => $this->bank()->id, 'direction' => 'in', 'amount' => 400_000, 'dossier_id' => $dossier->id,
        ]);
        $this->getJson('/api/v1/accounts')->assertOk()->assertJsonPath('total', 400_000);
        $this->getJson("/api/v1/documents/{$id}")
            ->assertJsonPath('data.payment.state', 'partial')
            ->assertJsonPath('data.payment.state_label', 'Partielle');
    }

    public function test_un_paiement_total_solde_la_facture(): void
    {
        $this->login();
        $dossier = Dossier::factory()->create();
        $id = $this->facture($dossier);

        $this->pay($id, ['amount' => 1_000_000])
            ->assertCreated()
            ->assertJsonPath('invoice.rest', 0)
            ->assertJsonPath('invoice.state', 'settled');
    }

    public function test_le_total_des_paiements_ne_depasse_pas_la_facture(): void
    {
        $this->login();
        $dossier = Dossier::factory()->create();
        $id = $this->facture($dossier);

        $this->pay($id, ['amount' => 1_000_001])->assertUnprocessable()->assertJsonValidationErrors('amount');
        $this->pay($id, ['amount' => 400_000])->assertCreated();
        $this->pay($id, ['amount' => 700_000])->assertUnprocessable()->assertJsonValidationErrors('amount');
        $this->pay($id, ['amount' => 600_000])->assertCreated()->assertJsonPath('invoice.state', 'settled');

        $this->assertDatabaseCount('payments', 2);
    }

    public function test_validation_du_paiement(): void
    {
        $this->login();
        $dossier = Dossier::factory()->create();
        $id = $this->facture($dossier);

        $this->pay($id, ['amount' => 0])->assertUnprocessable()->assertJsonValidationErrors('amount');
        $this->pay($id, ['mode' => 'cheque'])->assertUnprocessable()->assertJsonValidationErrors('mode');
        $this->pay($id, ['account_id' => 9999])->assertUnprocessable()->assertJsonValidationErrors('account_id');
        $this->pay($id, ['paid_on' => now()->addDay()->toDateString()])->assertUnprocessable()->assertJsonValidationErrors('paid_on');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_seule_une_facture_emise_recoit_un_paiement(): void
    {
        $this->login();
        $dossier = Dossier::factory()->create();
        $quotation = $this->facture($dossier, 500_000, 'proforma');
        $facture = $this->facture($dossier);

        $this->pay($quotation)->assertUnprocessable()->assertJsonValidationErrors('document');

        $this->postJson("/api/v1/documents/{$facture}/cancel", ['reason' => 'Erreur'])->assertOk();
        $this->pay($facture)->assertUnprocessable()->assertJsonValidationErrors('document');
    }

    public function test_reference_automatique_ou_saisie(): void
    {
        $this->login();
        $dossier = Dossier::factory()->create();
        $id = $this->facture($dossier);

        $this->pay($id, ['amount' => 100_000])->assertCreated()->assertJsonPath('data.reference', 'REC-0001');
        $this->pay($id, ['amount' => 100_000, 'reference' => 'VIR-0412'])->assertCreated()->assertJsonPath('data.reference', 'VIR-0412');
        $this->pay($id, ['amount' => 100_000, 'reference' => '  '])->assertCreated()->assertJsonPath('data.reference', 'REC-0002');
    }

    public function test_annulation_d_un_paiement(): void
    {
        $this->login();
        $dossier = Dossier::factory()->create();
        $id = $this->facture($dossier);
        $paymentId = $this->pay($id)->json('data.id');

        $this->postJson("/api/v1/payments/{$paymentId}/cancel", [])->assertUnprocessable()->assertJsonValidationErrors('reason');

        $this->postJson("/api/v1/payments/{$paymentId}/cancel", ['reason' => 'Virement rejeté par la banque'])
            ->assertOk()
            ->assertJsonPath('data.cancelled', true)
            ->assertJsonPath('data.cancel_reason', 'Virement rejeté par la banque')
            ->assertJsonPath('invoice.paid', 0)
            ->assertJsonPath('invoice.rest', 1_000_000)
            ->assertJsonPath('invoice.state', 'unpaid');

        // L'entrée de trésorerie est annulée avec le paiement : le solde revient à zéro
        $this->assertNotNull(Movement::query()->where('payment_id', $paymentId)->value('cancelled_at'));
        $this->getJson('/api/v1/accounts')->assertJsonPath('total', 0);

        // Le paiement reste visible, et on ne l'annule pas deux fois
        $this->getJson("/api/v1/dossiers/{$dossier->id}/payments")->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.cancelled', true);
        $this->postJson("/api/v1/payments/{$paymentId}/cancel", ['reason' => 'Doublon'])->assertUnprocessable();

        // Le montant libéré peut être encaissé à nouveau
        $this->pay($id, ['amount' => 1_000_000])->assertCreated();
    }

    public function test_une_facture_avec_paiements_ne_s_annule_pas(): void
    {
        $this->login();
        $dossier = Dossier::factory()->create();
        $id = $this->facture($dossier);
        $paymentId = $this->pay($id)->json('data.id');

        $this->postJson("/api/v1/documents/{$id}/cancel", ['reason' => 'Erreur'])
            ->assertUnprocessable()->assertJsonValidationErrors('document');

        $this->postJson("/api/v1/payments/{$paymentId}/cancel", ['reason' => 'Erreur de saisie'])->assertOk();
        $this->postJson("/api/v1/documents/{$id}/cancel", ['reason' => 'Erreur'])->assertOk();
    }

    public function test_le_total_d_une_facture_ne_passe_pas_sous_les_paiements_recus(): void
    {
        $this->login();
        $dossier = Dossier::factory()->create();
        $id = $this->facture($dossier);
        $this->pay($id)->assertCreated();

        $edit = fn (int $price) => [
            'issued_on' => now()->toDateString(), 'currency' => 'XOF', 'vat_enabled' => false, 'vat_rate' => 18,
            'lines' => [['block' => 'P', 'label' => 'Honoraires', 'unit' => 'ea', 'quantity' => 1, 'unit_price' => $price]],
        ];

        $this->putJson("/api/v1/documents/{$id}", $edit(300_000))->assertUnprocessable()->assertJsonValidationErrors('lines');
        $this->putJson("/api/v1/documents/{$id}", $edit(400_000))->assertOk()->assertJsonPath('data.payment.state', 'settled');
    }

    public function test_cloture_et_annulation_du_dossier_suivent_les_paiements(): void
    {
        $this->login();
        $dossier = Dossier::factory()->create();
        $id = $this->facture($dossier);

        // Pas soldée : on ne clôture pas
        $this->patchJson("/api/v1/dossiers/{$dossier->id}/status", ['status' => 'cloturee'])->assertUnprocessable();

        $paymentId = $this->pay($id, ['amount' => 1_000_000])->json('data.id');
        $this->patchJson("/api/v1/dossiers/{$dossier->id}/status", ['status' => 'cloturee'])->assertOk();

        // Dossier clôturé : le paiement ne s'annule pas sans rouvrir le dossier
        $this->postJson("/api/v1/payments/{$paymentId}/cancel", ['reason' => 'Erreur'])->assertUnprocessable();

        // Un dossier avec paiement reçu ne s'annule pas
        $other = Dossier::factory()->create();
        $this->pay($this->facture($other))->assertCreated();
        $this->patchJson("/api/v1/dossiers/{$other->id}/status", ['status' => 'annulee'])->assertUnprocessable();
    }

    public function test_aucun_paiement_sur_un_dossier_annule(): void
    {
        $this->login();
        $dossier = Dossier::factory()->create();
        $id = $this->facture($dossier);
        $this->patchJson("/api/v1/dossiers/{$dossier->id}/status", ['status' => 'annulee'])->assertOk();

        $this->pay($id)->assertUnprocessable()->assertJsonValidationErrors('document');
    }

    public function test_factures_a_encaisser(): void
    {
        $this->login();
        $a = Dossier::factory()->create();
        $b = Dossier::factory()->create();
        $c = Dossier::factory()->create();
        $idA = $this->facture($a, 1_000_000);
        $idB = $this->facture($b, 2_000_000);
        $idC = $this->facture($c, 500_000);

        $this->pay($idA, ['amount' => 1_000_000])->assertCreated();   // soldée : n'apparaît plus
        $this->pay($idB, ['amount' => 500_000])->assertCreated();     // reste 1 500 000

        $this->getJson('/api/v1/payments/receivable')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $idB)
            ->assertJsonPath('data.0.payment.rest', 1_500_000)
            ->assertJsonPath('data.1.id', $idC)
            ->assertJsonPath('total_rest', 2_000_000);
    }

    public function test_historique_et_recherche(): void
    {
        $this->login();
        $a = Dossier::factory()->create();
        $b = Dossier::factory()->create();
        $this->pay($this->facture($a), ['reference' => 'VIR-0001'])->assertCreated();
        $this->pay($this->facture($b), ['reference' => 'REC-9999', 'mode' => 'especes', 'account_id' => $this->cash()->id])->assertCreated();

        $this->getJson('/api/v1/payments')->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('meta.total', 2);
        $this->getJson('/api/v1/payments?q=VIR-0001')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.reference', 'VIR-0001');
        $this->getJson('/api/v1/payments?q=FCT')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_options_de_saisie(): void
    {
        $this->login();

        $this->getJson('/api/v1/payments/options')
            ->assertOk()
            ->assertJsonCount(3, 'data.modes')
            ->assertJsonPath('data.modes.1.value', 'virement')
            ->assertJsonPath('data.modes.1.account_type', 'banque')
            ->assertJsonPath('data.modes.0.account_type', 'caisse')
            ->assertJsonCount(2, 'data.accounts');
    }

    public function test_un_visiteur_non_connecte_est_refuse(): void
    {
        $this->getJson('/api/v1/payments')->assertUnauthorized();
        $this->getJson('/api/v1/payments/receivable')->assertUnauthorized();
    }

    public function test_factures_du_dossier_avec_leur_reste(): void
    {
        $this->login();
        $dossier = Dossier::factory()->create();
        $this->facture($dossier, 300_000, 'proforma');
        $id = $this->facture($dossier);
        $this->pay($id)->assertCreated();

        $this->getJson("/api/v1/dossiers/{$dossier->id}/invoices")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $id)
            ->assertJsonPath('data.0.total', 1_000_000)
            ->assertJsonPath('data.0.payment.paid', 400_000)
            ->assertJsonPath('data.0.payment.rest', 600_000)
            ->assertJsonPath('data.0.dossier.client.name', $dossier->client->name);
    }
}