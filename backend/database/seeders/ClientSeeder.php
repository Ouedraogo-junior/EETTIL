<?php

namespace Database\Seeders;

use App\Modules\Clients\Models\Client;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $clients = [
            ['Sahel Négoce SARL', '+226 70 00 00 01', 'achats@sahel-negoce.example', 'Zone industrielle, Ouagadougou', '00000001A', 'BF-OUA-01-2019-B12-00001', 'Importe du matériel et des équipements.'],
            ['Burkina Agro Import', '+226 70 00 00 02', 'logistique@agro-import.example', 'Secteur 15, Ouagadougou', '00000002B', 'BF-OUA-01-2020-B13-00002', null],
            ['Faso Matériaux SA', '+226 70 00 00 03', 'direction@faso-materiaux.example', 'Route de Bobo, Ouagadougou', '00000003C', 'BF-OUA-01-2018-B14-00003', 'Paie par virement.'],
            ['Établissements Ouédraogo & Fils', '+226 70 00 00 04', null, 'Marché de Sankaryaré, Ouagadougou', null, null, 'Client occasionnel, sans IFU renseigné.'],
            ['Savane Équipements', '+226 70 00 00 05', 'compta@savane-equipements.example', 'Bobo-Dioulasso', '00000005E', 'BF-BBD-01-2021-B10-00005', null],
            ['Kadiogo Pharma', '+226 70 00 00 06', 'import@kadiogo-pharma.example', 'Zone du Bois, Ouagadougou', '00000006F', 'BF-OUA-01-2017-B15-00006', 'Produits sensibles : suivi renforcé.'],
        ];

        foreach ($clients as [$name, $phone, $email, $address, $ifu, $rccm, $notes]) {
            Client::firstOrCreate(['name' => $name], compact('phone', 'email', 'address', 'ifu', 'rccm', 'notes'));
        }
    }
}