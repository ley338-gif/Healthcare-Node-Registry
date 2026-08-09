<?php

namespace Tests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function actingAs(Authenticatable $user, $guard = null): static
    {
        parent::actingAs($user, $guard);

        return $this->withSession([
            'auth.mfa_verified_user_id' => $user->getAuthIdentifier(),
            'auth.mfa_verified_at' => now()->timestamp,
            'auth.mfa_test_bypass' => true,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $environment = app()->environment();
        $connection = (string) config('database.default');
        $database = config("database.connections.{$connection}.database");

        if ($environment !== 'testing') {
            throw new RuntimeException(
                "Unsafe test environment detected: {$environment}. Expected: testing.",
            );
        }

        if (! is_string($database) || ! str_ends_with($database, '_test')) {
            $databaseName = is_scalar($database) ? (string) $database : 'unknown';

            throw new RuntimeException(
                "Unsafe test database detected: {$databaseName}. Expected a database ending in _test.",
            );
        }
    }
}
