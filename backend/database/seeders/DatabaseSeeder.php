<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Auth\Enums\Role;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(['login' => 'admin'], [
            'name' => 'Administrateur', 'role' => Role::Admin, 'active' => true, 'password' => 'admin1234',
        ]);
        User::updateOrCreate(['login' => 'gerant'], [
            'name' => 'Gérant EETTIL', 'role' => Role::Gerant, 'active' => true, 'password' => 'gerant1234',
        ]);

        $this->call(ClientSeeder::class);
    }
}