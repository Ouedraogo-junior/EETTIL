<?php

namespace Database\Seeders;

use App\Modules\Clients\Models\Client;
use App\Modules\Dossiers\Actions\CreateDossier;
use App\Modules\Dossiers\Enums\DossierStatus;
use App\Modules\Dossiers\Models\Dossier;
use Illuminate\Database\Seeder;

class DossierSeeder extends Seeder
{
    public function run(): void
    {
        if (Dossier::query()->exists()) {
            return;
        }
        $clients = Client::orderBy('id')->get();
        if ($clients->count() < 6) {
            return;
        }

        $create = app(CreateDossier::class);
        $ago = fn (int $days) => now()->subDays($days);
        $day = fn (int $days) => now()->subDays($days)->toDateString();

        // 1. Transit terminé et livré
        $d = $create->handle(['client_id' => $clients[2]->id, 'services' => ['transit', 'dedouanement'],
            'description' => 'Importation de ciment, conteneur 20 pieds'], $ago(45));
        $d->tracking->update(['supplier_invoices' => '70002201', 'dpi_number' => 'C7710', 'dpi_date' => $day(44),
            'bl_lta' => 'MSKU4401877', 'transit_order_number' => 'OT 214A26', 'regime' => 'IM4',
            'nature' => 'CIMENT EN SACS', 'packages' => 1, 'weight_kg' => 24000,
            'date_ot' => $day(44), 'date_arrival' => $day(38), 'date_docs' => $day(37),
            'date_declaration' => $day(34), 'date_bae' => $day(32), 'date_delivery' => $day(30),
            'comments' => 'Livré au dépôt, sans réserve.']);
        $d->update(['status' => DossierStatus::Terminee]);

        // 2. Transit en cours (avion)
        $d = $create->handle(['client_id' => $clients[0]->id, 'services' => ['transit', 'dedouanement', 'transport'],
            'description' => 'Matériel informatique par avion, Paris - Ouagadougou'], $ago(12));
        $d->tracking->update(['supplier_invoices' => 'FA-2291 ; FA-2292', 'bl_lta' => '176-12345678',
            'regime' => 'IM4', 'nature' => 'MATERIEL INFORMATIQUE', 'packages' => 14, 'weight_kg' => 620.5,
            'date_ot' => $day(11), 'date_arrival' => $day(8), 'date_docs' => $day(6)]);
        $d->update(['status' => DossierStatus::EnCours]);

        // 3. Manutention seule : pas de suivi de transit
        $create->handle(['client_id' => $clients[3]->id, 'services' => ['manutention'],
            'description' => 'Déchargement et stockage de marchandises', 'track' => false], $ago(5));

        // 4. Conseil logistique : pas de suivi de transit
        $d = $create->handle(['client_id' => $clients[4]->id, 'services' => ['conseil'],
            'description' => 'Audit de la chaîne d’approvisionnement', 'track' => false], $ago(20));
        $d->update(['status' => DossierStatus::EnCours]);

        // 5. Suivi activé mais pas encore commencé
        $create->handle(['client_id' => $clients[5]->id, 'services' => ['transit', 'dedouanement'],
            'description' => 'Importation de produits pharmaceutiques'], $ago(1));
    }
}