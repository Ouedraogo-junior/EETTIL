<?php

namespace Database\Seeders;

use App\Modules\Facturation\Models\LineTemplate;
use Illuminate\Database\Seeder;

class LineTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            'D' => [
                'Déclaration Préalable d’Importation', 'Certificat d’Assurance Faculté', 'Remise documentaire',
                'Passage magasin douane', 'CBC', 'Droits & Taxes de Douane',
            ],
            'P' => [
                'Ouverture dossier', 'Honoraires des agréés en douane', 'Manutention, transport, livraison',
                'Commission sur transit', 'Conseil logistique',
            ],
        ];

        foreach ($templates as $block => $labels) {
            foreach ($labels as $position => $label) {
                LineTemplate::firstOrCreate(['block' => $block, 'label' => $label], ['position' => $position]);
            }
        }
    }
}