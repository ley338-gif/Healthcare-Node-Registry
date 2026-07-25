<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\RbacBootstrapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

final class DoctorCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_passes_for_an_initialized_backend_environment(): void
    {
        $role = app(RbacBootstrapper::class)->ensureSystemAdministratorRole();

        $user = User::query()->create([
            'public_id' => (string) Str::uuid7(),
            'name' => 'Synthetic Admin',
            'email' => 'doctor-admin@example.test',
            'password' => Hash::make('Strong-Test-Password!42'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $user->roles()->attach($role->id);

        $this->artisan('registry:doctor', ['--skip-assets' => true])
            ->assertSuccessful();
    }
}
