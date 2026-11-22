<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * Creates fake users for tests. Default is an approved tenant.
 */
class UserFactory extends Factory
{
    /**
     * Default attributes of a fake user.
     */
    public function definition(): array
    {
        return [
            'nik' => fake()->unique()->numerify('################'),
            'name' => fake()->name(),
            'phone' => fake()->numerify('08##########'),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'role' => 'tenant',
            'status' => 'approved',
        ];
    }

    /**
     * A user with the admin role.
     */
    public function admin(): static
    {
        return $this->state(['role' => 'admin']);
    }

    /**
     * A tenant still waiting for admin approval.
     */
    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }
}
