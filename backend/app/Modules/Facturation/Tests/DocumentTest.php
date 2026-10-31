<?php

namespace App\Modules\Facturation\Tests;

use App\Models\User;
use App\Modules\Dossiers\Models\Dossier;
use Database\Seeders\LineTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Modules\Facturation\Models\Document;
use App\Modules\Facturation\Pdf\DocumentPdf;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    private function login(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'gerant']));
    }

    /** Lignes de la facture modèle du client : 13 793 669 de débours + 1 276 000 de prestations. */
    private function lines(): array
    {
        return [
            ['block' => 'D', 'label' => 'Remise documentaire', 'unit' => 'ea', 'quantity' => 1, 'unit_price' => 29_000],
            ['block' => 'D', 'label' => 'Passage magasin douane', 'unit' => 'ea', 'quantity' => 1, 'unit_price' => 21_050],
            ['block' => 'D', 'label' => 'CBC', 'unit' => 'ea', 'quantity' => 1, 'unit_price' => 15_000],
            ['block' => 'D', 'label' => 'Droits & Taxes de Douane', 'unit' => 'ea', 'quantity' => 1, 'unit_price' => 13_728_619],
            ['block' => 'P', 'label' => 'Ouverture dossier', 'unit' => 'ea', 'quantity' => 1, 'unit_price' => 10_000],
            ['block' => 'P', 'label' => 'Honoraires des agréés en douane', 'unit' => 'ea', 'quantity' => 1, 'unit_price' => 1_175_000],
            ['block' => 'P', 'label' => 'Manutention, transport, livraison', 'unit' => 'ea', 'quantity' => 1, 'unit_price' => 75_000],
            ['block' => 'P', 'label' => 'Commission sur transit', 'unit' => 'ea', 'quantity' => 1, 'unit_price' => 16_000],
        ];
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'type' => 'facture',
            'issued_on' => now()->toDateString(),
            'currency' => 'XOF',
            'vat_enabled' => false,
            'vat_rate' => 18,
            'lines' => $this->lines(),
        ], $override);
    }

    public function test_creation_d_une_facture_numerotee_et_calculee_par_le_serveur(): void
    {
        $dossier = Dossier::factory()->create();
        $year = now()->year;
        $this->login();

        $this->postJson("/api/v1/dossiers/{$dossier->id}/documents", $this->payload(['total' => 1, 'debours_total' => 5]))
            ->assertCreated()
            ->assertJsonPath('data.number', "FCT/{$year}-000-001")
            ->assertJsonPath('data.state', 'emis')
            ->assertJsonPath('data.totals.debours', 13_793_669)
            ->assertJsonPath('data.totals.prestations_ht', 1_276_000)
            ->assertJsonPath('data.totals.vat', 0)
            ->assertJsonPath('data.totals.total', 15_069_669)
            ->assertJsonPath('data.totals.total_in_words', 'QUINZE MILLIONS SOIXANTE NEUF MILLE SIX CENT SOIXANTE NEUF FRANCS CFA')
            ->assertJsonCount(8, 'data.lines');

        $this->assertSame('en_cours', $dossier->fresh()->status->value);
    }

    public function test_les_proformas_ont_leur_propre_compteur_et_peuvent_etre_plusieurs(): void
    {
        $dossier = Dossier::factory()->create();
        $year = now()->year;
        $this->login();
        $url = "/api/v1/dossiers/{$dossier->id}/documents";

        $this->postJson($url, $this->payload(['type' => 'proforma']))->assertCreated()->assertJsonPath('data.number', "QUO/{$year}-000-001");
        $this->postJson($url, $this->payload(['type' => 'proforma']))->assertCreated()->assertJsonPath('data.number', "QUO/{$year}-000-002");
        $this->postJson($url, $this->payload())->assertCreated()->assertJsonPath('data.number', "FCT/{$year}-000-001");

        // Une proforma n'a aucun effet sur le statut du dossier
        $this->assertSame('en_cours', $dossier->fresh()->status->value);
    }

    public function test_un_dossier_n_a_qu_une_seule_facture_active(): void
    {
        $dossier = Dossier::factory()->create();
        $this->login();
        $url = "/api/v1/dossiers/{$dossier->id}/documents";

        $id = $this->postJson($url, $this->payload())->assertCreated()->json('data.id');

        $this->postJson($url, $this->payload())->assertUnprocessable()->assertJsonValidationErrors('type');

        // Après annulation, une nouvelle facture est possible, avec un nouveau numéro (jamais réutilisé)
        $this->postJson("/api/v1/documents/{$id}/cancel", ['reason' => 'Erreur de saisie'])->assertOk();
        $this->postJson($url, $this->payload())->assertCreated()->assertJsonPath('data.number', 'FCT/'.now()->year.'-000-002');
    }

    public function test_la_numerotation_repart_a_un_chaque_annee_selon_la_date_du_document(): void
    {
        $dossier = Dossier::factory()->create();
        $this->login();
        $url = "/api/v1/dossiers/{$dossier->id}/documents";

        $this->postJson($url, $this->payload(['type' => 'proforma', 'issued_on' => '2025-12-30']))
            ->assertCreated()->assertJsonPath('data.number', 'QUO/2025-000-001');
        $this->postJson($url, $this->payload(['type' => 'proforma', 'issued_on' => '2026-01-05']))
            ->assertCreated()->assertJsonPath('data.number', 'QUO/2026-000-001');
        $this->postJson($url, $this->payload(['type' => 'proforma', 'issued_on' => '2025-12-31']))
            ->assertCreated()->assertJsonPath('data.number', 'QUO/2025-000-002');
    }

    public function test_validation_des_champs(): void
    {
        $dossier = Dossier::factory()->create();
        $this->login();
        $url = "/api/v1/dossiers/{$dossier->id}/documents";

        $this->postJson($url, $this->payload(['lines' => []]))->assertUnprocessable()->assertJsonValidationErrors('lines');
        $this->postJson($url, $this->payload(['type' => 'devis']))->assertUnprocessable()->assertJsonValidationErrors('type');
        $this->postJson($url, $this->payload(['issued_on' => now()->addDay()->toDateString()]))->assertUnprocessable()->assertJsonValidationErrors('issued_on');
        $this->postJson($url, $this->payload(['lines' => [['block' => 'P', 'label' => '', 'quantity' => 1, 'unit_price' => 1000]]]))
            ->assertUnprocessable()->assertJsonValidationErrors('lines.0.label');
        $this->postJson($url, $this->payload(['lines' => [['block' => 'P', 'label' => 'X', 'quantity' => 0, 'unit_price' => 1000]]]))
            ->assertUnprocessable()->assertJsonValidationErrors('lines.0.quantity');
        $this->postJson($url, $this->payload(['lines' => [['block' => 'Z', 'label' => 'X', 'quantity' => 1, 'unit_price' => 1000]]]))
            ->assertUnprocessable()->assertJsonValidationErrors('lines.0.block');

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_une_remise_superieure_au_montant_est_refusee(): void
    {
        $dossier = Dossier::factory()->create();
        $this->login();
        $url = "/api/v1/dossiers/{$dossier->id}/documents";

        $line = ['block' => 'P', 'label' => 'Honoraires', 'quantity' => 1, 'unit_price' => 100_000, 'discount_kind' => 'pct', 'discount_value' => 150];
        $this->postJson($url, $this->payload(['lines' => [$line]]))
            ->assertUnprocessable()->assertJsonValidationErrors('lines.0.discount_value');

        $ok = ['block' => 'P', 'label' => 'Honoraires', 'quantity' => 1, 'unit_price' => 100_000];
        $this->postJson($url, $this->payload(['lines' => [$ok], 'global_discount_kind' => 'amt', 'global_discount_value' => 250_000]))
            ->assertUnprocessable()->assertJsonValidationErrors('global_discount_value');
    }

    public function test_remises_et_tva_sont_appliquees_aux_prestations_seulement(): void
    {
        $dossier = Dossier::factory()->create();
        $this->login();

        $lines = [
            ['block' => 'D', 'label' => 'Droits', 'quantity' => 1, 'unit_price' => 1_000_000],
            ['block' => 'P', 'label' => 'Honoraires', 'quantity' => 1, 'unit_price' => 200_000, 'discount_kind' => 'pct', 'discount_value' => 10],
            ['block' => 'P', 'label' => 'Transport', 'quantity' => 1, 'unit_price' => 100_000],
        ];

        $this->postJson("/api/v1/dossiers/{$dossier->id}/documents", $this->payload([
            'lines' => $lines, 'global_discount_kind' => 'pct', 'global_discount_value' => 10, 'vat_enabled' => true, 'vat_rate' => 18,
        ]))
            ->assertCreated()
            ->assertJsonPath('data.totals.prestations_subtotal', 280_000)
            ->assertJsonPath('data.totals.global_discount', 28_000)
            ->assertJsonPath('data.totals.prestations_ht', 252_000)
            ->assertJsonPath('data.totals.vat', 45_360)
            ->assertJsonPath('data.totals.total', 1_297_360);
    }

    public function test_modification_d_un_document_emis_et_verrouillage_apres_annulation(): void
    {
        $dossier = Dossier::factory()->create();
        $this->login();
        $created = $this->postJson("/api/v1/dossiers/{$dossier->id}/documents", $this->payload())->assertCreated();
        $id = $created->json('data.id');
        $number = $created->json('data.number');

        $changed = $this->payload(['lines' => [['block' => 'P', 'label' => 'Conseil', 'quantity' => 2, 'unit_price' => 50_000]]]);
        unset($changed['type']);

        $this->putJson("/api/v1/documents/{$id}", $changed)
            ->assertOk()
            ->assertJsonPath('data.number', $number)
            ->assertJsonPath('data.totals.total', 100_000)
            ->assertJsonCount(1, 'data.lines');

        $this->postJson("/api/v1/documents/{$id}/cancel", ['reason' => 'Client a renoncé'])
            ->assertOk()
            ->assertJsonPath('data.state', 'annule')
            ->assertJsonPath('data.cancel_reason', 'Client a renoncé');

        $this->putJson("/api/v1/documents/{$id}", $changed)->assertUnprocessable()->assertJsonValidationErrors('document');
    }

    public function test_l_annulation_exige_un_motif_et_ne_se_fait_qu_une_fois(): void
    {
        $dossier = Dossier::factory()->create();
        $this->login();
        $id = $this->postJson("/api/v1/dossiers/{$dossier->id}/documents", $this->payload())->json('data.id');

        $this->postJson("/api/v1/documents/{$id}/cancel", [])->assertUnprocessable()->assertJsonValidationErrors('reason');
        $this->postJson("/api/v1/documents/{$id}/cancel", ['reason' => 'Doublon'])->assertOk();
        $this->postJson("/api/v1/documents/{$id}/cancel", ['reason' => 'Doublon'])->assertUnprocessable();
    }

    public function test_transformation_d_une_proforma_en_facture(): void
    {
        $dossier = Dossier::factory()->create();
        $this->login();
        $proformaId = $this->postJson("/api/v1/dossiers/{$dossier->id}/documents", $this->payload(['type' => 'proforma']))->json('data.id');

        $this->postJson("/api/v1/documents/{$proformaId}/convert")
            ->assertCreated()
            ->assertJsonPath('data.type', 'facture')
            ->assertJsonPath('data.number', 'FCT/'.now()->year.'-000-001')
            ->assertJsonPath('data.source_document_id', $proformaId)
            ->assertJsonPath('data.totals.total', 15_069_669)
            ->assertJsonCount(8, 'data.lines');

        $this->getJson("/api/v1/documents/{$proformaId}")->assertJsonPath('data.state', 'transforme');
        $this->assertSame('en_cours', $dossier->fresh()->status->value);

        // La proforma transformée n'est plus modifiable ni transformable
        $edit = $this->payload();
        unset($edit['type']);
        $this->putJson("/api/v1/documents/{$proformaId}", $edit)->assertUnprocessable();
        $this->postJson("/api/v1/documents/{$proformaId}/convert")->assertUnprocessable();
    }

    public function test_on_ne_transforme_pas_en_facture_quand_le_dossier_en_a_deja_une(): void
    {
        $dossier = Dossier::factory()->create();
        $this->login();
        $url = "/api/v1/dossiers/{$dossier->id}/documents";
        $proformaId = $this->postJson($url, $this->payload(['type' => 'proforma']))->json('data.id');
        $this->postJson($url, $this->payload())->assertCreated();

        $this->postJson("/api/v1/documents/{$proformaId}/convert")->assertUnprocessable()->assertJsonValidationErrors('type');
    }

    public function test_une_facture_ne_se_transforme_pas(): void
    {
        $dossier = Dossier::factory()->create();
        $this->login();
        $factureId = $this->postJson("/api/v1/dossiers/{$dossier->id}/documents", $this->payload())->json('data.id');

        $this->postJson("/api/v1/documents/{$factureId}/convert")->assertUnprocessable()->assertJsonValidationErrors('document');
    }

    public function test_une_facture_creee_depuis_une_proforma_transforme_celle_ci(): void
    {
        $dossier = Dossier::factory()->create();
        $this->login();
        $url = "/api/v1/dossiers/{$dossier->id}/documents";
        $proformaId = $this->postJson($url, $this->payload(['type' => 'proforma']))->json('data.id');

        $this->postJson($url, $this->payload(['from_proforma_id' => $proformaId]))
            ->assertCreated()
            ->assertJsonPath('data.source_document_id', $proformaId);

        $this->getJson("/api/v1/documents/{$proformaId}")->assertJsonPath('data.state', 'transforme');
    }

    public function test_une_proforma_d_un_autre_dossier_est_refusee(): void
    {
        $a = Dossier::factory()->create();
        $b = Dossier::factory()->create();
        $this->login();
        $proformaId = $this->postJson("/api/v1/dossiers/{$a->id}/documents", $this->payload(['type' => 'proforma']))->json('data.id');

        $this->postJson("/api/v1/dossiers/{$b->id}/documents", $this->payload(['from_proforma_id' => $proformaId]))
            ->assertUnprocessable()->assertJsonValidationErrors('from_proforma_id');
    }

    public function test_apercu_des_totaux_sans_rien_enregistrer(): void
    {
        $this->login();

        $this->postJson('/api/v1/documents/preview', ['lines' => $this->lines(), 'vat_enabled' => false])
            ->assertOk()
            ->assertJsonPath('data.total', 15_069_669)
            ->assertJsonPath('data.debours', 13_793_669)
            ->assertJsonPath('data.total_in_words', 'QUINZE MILLIONS SOIXANTE NEUF MILLE SIX CENT SOIXANTE NEUF FRANCS CFA');

        // L'aperçu signale une remise trop grande au lieu de la refuser
        $this->postJson('/api/v1/documents/preview', [
            'lines' => [['block' => 'P', 'label' => '', 'quantity' => 1, 'unit_price' => 1000, 'discount_kind' => 'pct', 'discount_value' => 200]],
        ])->assertOk()->assertJsonPath('data.lines.0.over_limit', true);

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_options_de_saisie(): void
    {
        $this->seed(LineTemplateSeeder::class);
        $this->login();

        $this->getJson('/api/v1/documents/options')
            ->assertOk()
            ->assertJsonPath('data.vat_rate', 18)
            ->assertJsonCount(6, 'data.templates.D')
            ->assertJsonCount(5, 'data.templates.P')
            ->assertJsonPath('data.currencies.0', 'XOF');
    }

    public function test_liste_et_consultation(): void
    {
        $dossier = Dossier::factory()->create();
        $this->login();
        $url = "/api/v1/dossiers/{$dossier->id}/documents";
        $this->postJson($url, $this->payload(['type' => 'proforma']))->assertCreated();
        $id = $this->postJson($url, $this->payload())->json('data.id');

        $this->getJson($url)->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('data.0.id', $id);
        $this->getJson("/api/v1/documents/{$id}")
            ->assertOk()
            ->assertJsonPath('data.dossier.number', $dossier->number)
            ->assertJsonPath('data.dossier.client.id', $dossier->client_id)
            ->assertJsonCount(8, 'data.lines');
    }

    public function test_un_visiteur_non_connecte_est_refuse(): void
    {
        $this->getJson('/api/v1/documents/options')->assertUnauthorized();
        $this->postJson('/api/v1/documents/preview', ['lines' => []])->assertUnauthorized();
    }

    public function test_liste_globale_avec_recherche_et_filtres(): void
    {
        $a = Dossier::factory()->create();
        $b = Dossier::factory()->create();
        $this->login();
        $this->postJson("/api/v1/dossiers/{$a->id}/documents", $this->payload())->assertCreated();
        $this->postJson("/api/v1/dossiers/{$b->id}/documents", $this->payload(['type' => 'proforma']))->assertCreated();

        $this->getJson('/api/v1/documents')->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('meta.total', 2);
        $this->getJson('/api/v1/documents?type=proforma')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/documents?state=annule')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/documents?q='.urlencode($a->number))->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_un_dossier_avec_documents_ne_se_supprime_pas(): void
    {
        $dossier = Dossier::factory()->create();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->postJson("/api/v1/dossiers/{$dossier->id}/documents", $this->payload())->assertCreated();

        $this->deleteJson("/api/v1/dossiers/{$dossier->id}")->assertStatus(409);
    }

    public function test_donnees_du_pdf_sur_la_facture_modele_du_client(): void
    {
        $dossier = Dossier::factory()->create();
        $this->login();
        $id = $this->postJson("/api/v1/dossiers/{$dossier->id}/documents", $this->payload())->json('data.id');

        $document = Document::with(['lines', 'dossier.client', 'dossier.tracking'])->findOrFail($id);
        $d = app(DocumentPdf::class)->data($document);

        $this->assertSame('FACTURE', $d['title']);
        $this->assertSame('A. DÉBOURS', $d['debours_title']);
        $this->assertCount(4, $d['debours']);
        $this->assertCount(4, $d['prestations']);
        $this->assertSame("13\u{00A0}793\u{00A0}669\u{00A0}XOF", $d['debours_total']);
        $this->assertSame("15\u{00A0}069\u{00A0}669\u{00A0}XOF", $d['grand']);
        $this->assertSame('QUINZE MILLIONS SOIXANTE NEUF MILLE SIX CENT SOIXANTE NEUF FRANCS CFA', $d['words']);
    }

    public function test_une_quotation_s_imprime_comme_quotation(): void
    {
        $dossier = Dossier::factory()->create();
        $this->login();
        $id = $this->postJson("/api/v1/dossiers/{$dossier->id}/documents", $this->payload(['type' => 'proforma']))->json('data.id');

        $document = Document::with(['lines', 'dossier.client', 'dossier.tracking'])->findOrFail($id);
        $d = app(DocumentPdf::class)->data($document);

        $this->assertSame('QUOTATION', $d['title']);
        $this->assertSame('MONTANT TOTAL DE LA QUOTATION (XOF)', $d['grand_label']);
        $this->assertStringStartsWith('QUO/', $d['number']);
        $this->assertSame('FCT-'.now()->year.'-000-001.pdf', str_replace('QUO', 'FCT', explode(' - ', DocumentPdf::filename($document))[0]).'.pdf');
    }

    public function test_le_pdf_est_envoye_avec_le_bon_nom_de_fichier(): void
    {
        $dossier = Dossier::factory()->create();
        $this->login();
        $id = $this->postJson("/api/v1/dossiers/{$dossier->id}/documents", $this->payload())->json('data.id');

        $response = $this->get("/api/v1/documents/{$id}/pdf");

        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertStringContainsString('FCT-'.now()->year.'-000-001', $response->headers->get('Content-Disposition'));
    }
}