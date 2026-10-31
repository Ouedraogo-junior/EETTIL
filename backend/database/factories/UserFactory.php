<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Auth\Enums\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'login' => fake()->unique()->userName(),
            'email' => null,
            'role' => Role::Gerant,
            'active' => true,
            'password' => 'password',
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(['role' => Role::Admin]);
    }

    public function inactive(): static
    {
        return $this->state(['active' => false]);
    }
}