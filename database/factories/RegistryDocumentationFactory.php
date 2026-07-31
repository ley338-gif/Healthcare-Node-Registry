<?php

namespace Database\Factories;

use App\Models\RegistryDocumentation;
use App\Models\System;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RegistryDocumentation> */
final class RegistryDocumentationFactory extends Factory
{
    protected $model = RegistryDocumentation::class;

    public function definition(): array
    {
        $user = User::factory();

        return [
            'documentable_type' => System::class,
            'documentable_id' => System::factory(),
            'documentation_type' => 'operations',
            'section' => fake()->unique()->slug(2),
            'title' => fake()->sentence(3),
            'content' => fake()->paragraph(),
            'structured_data' => [],
            'visibility' => 'internal',
            'created_by' => $user,
            'updated_by' => $user,
        ];
    }
}
