<?php

namespace Database\Factories;

use App\Modules\Clients\Models\Client;
use App\Modules\Dossiers\Enums\DossierStatus;
use App\Modules\Dossiers\Models\Dossier;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Dossier> */
class DossierFactory extends Factory
{
    protected $model = Dossier::class;

    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'number' => now()->year.'-'.fake()->unique()->numerify('####'),
            'opened_on' => now()->toDateString(),
            'status' => DossierStatus::Nouvelle,
            'services' => ['transit'],
            'description' => fake()->sentence(),
            'notes' => null,
        ];
    }
}