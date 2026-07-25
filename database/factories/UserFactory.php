<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'public_id' => (string) Str::uuid7(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('Synthetic-Test-Password!'),
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }
}
