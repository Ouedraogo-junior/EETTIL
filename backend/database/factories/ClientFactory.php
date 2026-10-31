<?php

namespace Database\Factories;

use App\Modules\Clients\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Client> */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'phone' => '+226 70 '.fake()->numerify('## ## ##'),
            'email' => fake()->unique()->safeEmail(),
            'address' => fake()->streetName().', Ouagadougou',
            'ifu' => strtoupper(fake()->unique()->bothify('0000####?')),
            'rccm' => 'BF-OUA-01-'.fake()->numberBetween(2015, 2025).'-B'.fake()->numberBetween(10, 20).'-'.fake()->numerify('#####'),
            'notes' => null,
        ];
    }
}