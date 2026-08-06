<?php

namespace Tests\Unit;

use App\Services\Discovery\NetworkRangeException;
use App\Services\Discovery\NetworkRangeService;
use PHPUnit\Framework\TestCase;

final class NetworkRangeServiceTest extends TestCase
{
    public function test_it_expands_a_cidr_range_into_ip_addresses(): void
    {
        $service = new NetworkRangeService(maxRangeSize: 1024, largeRangeWarningThreshold: 256);

        $addresses = $service->expand('192.168.20.0/29');

        self::assertSame(
            ['192.168.20.0', '192.168.20.1', '192.168.20.2', '192.168.20.3', '192.168.20.4', '192.168.20.5', '192.168.20.6', '192.168.20.7'],
            $addresses,
        );
    }

    public function test_it_expands_a_start_end_range(): void
    {
        $service = new NetworkRangeService(maxRangeSize: 1024, largeRangeWarningThreshold: 256);

        $addresses = $service->expand('192.168.20.10-192.168.20.12');

        self::assertSame(['192.168.20.10', '192.168.20.11', '192.168.20.12'], $addresses);
    }

    public function test_it_applies_exclusions(): void
    {
        $service = new NetworkRangeService(maxRangeSize: 1024, largeRangeWarningThreshold: 256);

        $addresses = $service->expand('192.168.20.0/29', ['192.168.20.1', '192.168.20.4']);

        self::assertSame(
            ['192.168.20.0', '192.168.20.2', '192.168.20.3', '192.168.20.5', '192.168.20.6', '192.168.20.7'],
            $addresses,
        );
    }

    public function test_it_rejects_a_range_larger_than_the_configured_maximum(): void
    {
        $service = new NetworkRangeService(maxRangeSize: 10, largeRangeWarningThreshold: 256);

        $this->expectException(NetworkRangeException::class);

        $service->validate('192.168.0.0/16');
    }

    public function test_it_rejects_an_end_address_before_the_start_address(): void
    {
        $service = new NetworkRangeService(maxRangeSize: 1024, largeRangeWarningThreshold: 256);

        $this->expectException(NetworkRangeException::class);

        $service->validate('192.168.20.50-192.168.20.10');
    }

    public function test_it_rejects_an_exclusion_outside_the_target_range(): void
    {
        $service = new NetworkRangeService(maxRangeSize: 1024, largeRangeWarningThreshold: 256);

        $this->expectException(NetworkRangeException::class);

        $service->validate('192.168.20.0/29', ['10.0.0.5']);
    }

    public function test_it_rejects_an_invalid_ipv4_address(): void
    {
        $service = new NetworkRangeService(maxRangeSize: 1024, largeRangeWarningThreshold: 256);

        $this->expectException(NetworkRangeException::class);

        $service->parse('999.999.999.999');
    }

    public function test_address_count_reflects_the_range_size(): void
    {
        $service = new NetworkRangeService(maxRangeSize: 1024, largeRangeWarningThreshold: 256);

        self::assertSame(256, $service->addressCount('192.168.20.0/24'));
        self::assertSame(1, $service->addressCount('192.168.20.5'));
    }

    public function test_is_large_range_uses_the_configured_warning_threshold(): void
    {
        $service = new NetworkRangeService(maxRangeSize: 1024, largeRangeWarningThreshold: 256);

        self::assertTrue($service->isLargeRange('192.168.0.0/23')); // 512 Adressen > Default-Schwelle 256
        self::assertFalse($service->isLargeRange('192.168.20.0/29')); // 8 Adressen
    }
}
