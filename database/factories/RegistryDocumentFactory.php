<?php

namespace Database\Factories;

use App\Models\RegistryDocument;
use App\Models\System;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RegistryDocument> */
final class RegistryDocumentFactory extends Factory
{
    protected $model = RegistryDocument::class;

    public function definition(): array
    {
        return [
            'documentable_type' => System::class,
            'documentable_id' => System::factory(),
            'current_version_id' => null,
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'category' => 'other',
            'visibility' => 'internal',
            'status' => 'active',
            'valid_from' => null,
            'valid_until' => null,
            'contract_reference' => null,
            'tags' => [],
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
            'archived_at' => null,
        ];
    }
}
