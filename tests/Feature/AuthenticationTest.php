<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

final class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_active_user_can_authenticate(): void
    {
        $user = User::factory()->create([
            'public_id' => (string) Str::uuid7(),
            'password' => Hash::make('Correct-Test-Password!'),
            'is_active' => true,
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'Correct-Test-Password!',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    public function test_inactive_user_cannot_authenticate(): void
    {
        $user = User::factory()->create([
            'public_id' => (string) Str::uuid7(),
            'password' => Hash::make('Correct-Test-Password!'),
            'is_active' => false,
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'Correct-Test-Password!',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
