<?php

namespace App\Modules\Suivi\Tests;

use App\Models\User;
use App\Modules\Dossiers\Models\Dossier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class SuiviExportTest extends TestCase
{
    use RefreshDatabase;

    /** Concatène le contenu texte de tous les fichiers du classeur .xlsx (qui est une archive zip). */
    private function xlsxText(TestResponse $response): string
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($path, $response->streamedContent());

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($path) === true, 'Le fichier exporté n’est pas un classeur .xlsx valide.');

        $text = '';
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $text .= $zip->getFromIndex($i);
        }
        $zip->close();
        unlink($path);

        return $text;
    }

    private function login(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'gerant']));
    }

    public function test_l_export_contient_les_colonnes_du_fichier_de_suivi(): void
    {
        $dossier = Dossier::factory()->create(['number' => '2026-0042']);
        $dossier->tracking()->create([
            'bl_lta' => 'MSKU4401877', 'dpi_number' => 'c8811', 'dpi_date' => '2023-11-29',
            'nature' => 'CIMENT EN SACS', 'packages' => 3, 'weight_kg' => 24000, 'date_ot' => '2026-09-01',
        ]);
        $this->login();

        $response = $this->get('/api/v1/suivi/export')->assertOk();

        $this->assertStringContainsString('spreadsheetml.sheet', $response->headers->get('content-type'));
        $this->assertStringContainsString('suivi-dossiers-', $response->headers->get('content-disposition'));

        $text = $this->xlsxText($response);
        foreach (['Facture(s) fournisseur', 'Ordre de transit', '2026-0042', 'MSKU4401877', 'C8811 DU 29/11/23', 'CIMENT EN SACS', '01/09/2026'] as $expected) {
            $this->assertStringContainsString($expected, $text);
        }
    }

    public function test_l_export_respecte_les_filtres_et_exclut_les_dossiers_annules(): void
    {
        $livre = Dossier::factory()->create(['number' => '2026-0101']);
        $livre->tracking()->create(['date_ot' => now()->subDays(5)->toDateString(), 'date_delivery' => now()->subDay()->toDateString()]);

        $enCours = Dossier::factory()->create(['number' => '2026-0102']);
        $enCours->tracking()->create(['date_ot' => now()->subDays(2)->toDateString()]);

        $annule = Dossier::factory()->create(['number' => '2026-0103', 'status' => 'annulee']);
        $annule->tracking()->create(['date_ot' => now()->subDays(3)->toDateString()]);
        $this->login();

        $tous = $this->xlsxText($this->get('/api/v1/suivi/export')->assertOk());
        $this->assertStringContainsString('2026-0101', $tous);
        $this->assertStringContainsString('2026-0102', $tous);
        $this->assertStringNotContainsString('2026-0103', $tous);

        $livres = $this->xlsxText($this->get('/api/v1/suivi/export?state=delivered')->assertOk());
        $this->assertStringContainsString('2026-0101', $livres);
        $this->assertStringNotContainsString('2026-0102', $livres);
    }

    public function test_un_visiteur_non_connecte_est_refuse(): void
    {
        $this->getJson('/api/v1/suivi/export')->assertUnauthorized();
    }
}