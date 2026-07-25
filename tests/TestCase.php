<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
