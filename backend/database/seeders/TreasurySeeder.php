<?php

namespace Database\Seeders;

use App\Modules\Tresorerie\Models\Account;
use App\Modules\Tresorerie\Models\MovementCategory;
use Illuminate\Database\Seeder;

/** Comptes et catégories de départ. Sans risque à relancer : rien n'est écrasé ni dupliqué. */
class TreasurySeeder extends Seeder
{
    public function run(): void
    {
        Account::firstOrCreate(['name' => 'Caisse principale'], ['type' => 'caisse', 'initial_balance' => 0, 'position' => 1]);
        Account::firstOrCreate(['name' => 'Banque – compte courant'], ['type' => 'banque', 'initial_balance' => 0, 'position' => 2]);

        MovementCategory::payment();

        $categories = [
            'in' => ['Autre entrée'],
            'out' => [
                'Débours', 'Transport sous-traité', 'Carburant', 'Main-d’œuvre manutention', 'Frais de port et de magasinage',
                'Imprévu dossier', 'Salaires', 'Loyer et charges', 'Frais bancaires', 'Autre sortie',
            ],
        ];

        foreach ($categories as $direction => $names) {
            foreach ($names as $position => $name) {
                MovementCategory::firstOrCreate(
                    ['direction' => $direction, 'name' => $name],
                    ['code' => $name === 'Débours' ? MovementCategory::DEBOURS : null, 'is_system' => false, 'position' => $position + 1],
                );
            }
        }
    }
}