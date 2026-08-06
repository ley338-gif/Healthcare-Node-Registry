<?php

namespace App\Services\Discovery;

use App\Models\DiscoveryAllowedNetwork;
use App\Models\DiscoveryRun;

/**
 * Verarbeitet und begrenzt IPv4-Zielbereiche für Discovery-Läufe
 * (CIDR oder Start-/End-IP), wendet Ausschlussadressen an und prüft
 * die Freigabe gegen die konfigurierten erlaubten Netzbereiche.
 *
 * Bewusst nur IPv4, bewusst hart auf max_range_size begrenzt, bewusst
 * ohne öffentliche Netze als Standard - siehe Abschnitt 4/5 des
 * Lastenhefts und docs/Security/discovery-scan-boundaries.md.
 */
final class NetworkRangeService
{
    private readonly int $maxRangeSize;

    private readonly int $largeRangeWarningThreshold;

    public function __construct(?int $maxRangeSize = null, ?int $largeRangeWarningThreshold = null)
    {
        $this->maxRangeSize = $maxRangeSize ?? (int) config('discovery.max_range_size', 1024);
        $this->largeRangeWarningThreshold = $largeRangeWarningThreshold ?? (int) config('discovery.large_range_warning_threshold', 256);
    }

    /**
     * Parst einen Zielbereich ("192.168.20.0/24" oder
     * "192.168.20.10-192.168.20.50") in Start-/End-Long und Anzahl.
     *
     * @return array{start:int,end:int,count:int}
     */
    public function parse(string $ipRange): array
    {
        $ipRange = trim($ipRange);

        if (str_contains($ipRange, '/')) {
            return $this->parseCidr($ipRange);
        }

        if (str_contains($ipRange, '-')) {
            return $this->parseStartEnd($ipRange);
        }

        $ip = $this->toLong($ipRange, 'Zielbereich');

        return ['start' => $ip, 'end' => $ip, 'count' => 1];
    }

    /**
     * Liefert alle IPv4-Adressen im Zielbereich, abzüglich Ausschlüsse,
     * begrenzt auf max_range_size.
     *
     * @param  list<string>  $excludeIps
     * @return list<string>
     */
    public function expand(string $ipRange, array $excludeIps = []): array
    {
        $bounds = $this->parse($ipRange);
        $excluded = array_flip(array_map(fn (string $ip): int => $this->toLong($ip, 'Ausschlussadresse'), $excludeIps));

        $addresses = [];
        for ($value = $bounds['start']; $value <= $bounds['end']; $value++) {
            if (isset($excluded[$value])) {
                continue;
            }
            $addresses[] = long2ip($value);
        }

        return $addresses;
    }

    /**
     * Validiert einen Zielbereich vollständig: gültige IPv4-Grenzen,
     * maximale Größe, keine widersprüchlichen Ausschlüsse außerhalb des
     * Bereichs und Freigabe gegen die erlaubten Netzbereiche.
     *
     * @param  list<string>  $excludeIps
     *
     * @throws NetworkRangeException
     */
    public function validate(string $ipRange, array $excludeIps = []): void
    {
        $bounds = $this->parse($ipRange);

        if ($bounds['end'] < $bounds['start']) {
            throw new NetworkRangeException('Die End-Adresse liegt vor der Start-Adresse.');
        }

        if ($bounds['count'] > $this->maxRangeSize) {
            throw new NetworkRangeException("Der Zielbereich umfasst {$bounds['count']} Adressen und überschreitet das konfigurierte Limit von {$this->maxRangeSize} Adressen.");
        }

        foreach ($excludeIps as $exclude) {
            $value = $this->toLong($exclude, 'Ausschlussadresse');
            if ($value < $bounds['start'] || $value > $bounds['end']) {
                throw new NetworkRangeException("Die Ausschlussadresse {$exclude} liegt außerhalb des Zielbereichs.");
            }
        }

        $this->assertAllowed($bounds['start'], $bounds['end']);
    }

    public function isLargeRange(string $ipRange): bool
    {
        $bounds = $this->parse($ipRange);

        return $bounds['count'] >= $this->largeRangeWarningThreshold;
    }

    public function addressCount(string $ipRange): int
    {
        return $this->parse($ipRange)['count'];
    }

    /**
     * Findet aktive Discovery-Läufe, deren Zielbereich sich mit dem
     * angegebenen Bereich überschneidet (Hinweis, kein hartes Verbot).
     *
     * @return list<string>
     */
    public function overlappingActiveRunNames(string $ipRange, ?int $excludeRunId = null): array
    {
        $bounds = $this->parse($ipRange);

        return DiscoveryRun::query()
            ->whereIn('status', DiscoveryRun::ACTIVE_STATUSES)
            ->when($excludeRunId !== null, fn ($query) => $query->where('id', '!=', $excludeRunId))
            ->get(['id', 'name', 'ip_range'])
            ->filter(function (DiscoveryRun $run) use ($bounds): bool {
                try {
                    $otherBounds = $this->parse($run->ip_range);
                } catch (NetworkRangeException) {
                    return false;
                }

                return $bounds['start'] <= $otherBounds['end'] && $otherBounds['start'] <= $bounds['end'];
            })
            ->pluck('name')
            ->values()
            ->all();
    }

    /**
     * @throws NetworkRangeException
     */
    private function assertAllowed(int $start, int $end): void
    {
        $networks = DiscoveryAllowedNetwork::query()->active()->get(['cidr']);

        if ($networks->isEmpty()) {
            throw new NetworkRangeException('Es sind keine erlaubten Netzbereiche konfiguriert. Ein Administrator muss zunächst unter Einstellungen > Discovery einen Netzbereich freigeben.');
        }

        for ($value = $start; $value <= $end; $value++) {
            $covered = $networks->contains(fn ($network): bool => $this->longWithinCidr($value, $network->cidr));
            if (! $covered) {
                throw new NetworkRangeException('Der Zielbereich enthält Adressen außerhalb der freigegebenen Netzbereiche ('.long2ip($value).'). Scannen Sie ausschließlich Netzbereiche, für die Sie eine ausdrückliche Berechtigung besitzen.');
            }
        }
    }

    private function longWithinCidr(int $value, string $cidr): bool
    {
        try {
            $bounds = $this->parseCidr($cidr);
        } catch (NetworkRangeException) {
            return false;
        }

        return $value >= $bounds['start'] && $value <= $bounds['end'];
    }

    /**
     * @return array{start:int,end:int,count:int}
     */
    private function parseCidr(string $cidr): array
    {
        [$address, $prefix] = array_pad(explode('/', $cidr, 2), 2, null);

        if ($prefix === null || ! ctype_digit($prefix) || (int) $prefix < 0 || (int) $prefix > 32) {
            throw new NetworkRangeException("Ungültige CIDR-Notation: {$cidr}");
        }

        $base = $this->toLong((string) $address, 'Netzadresse');
        $prefixLength = (int) $prefix;
        $hostBits = 32 - $prefixLength;
        $mask = $hostBits === 32 ? 0 : (~0 << $hostBits) & 0xFFFFFFFF;
        $network = $base & $mask;
        $broadcast = $network | (~$mask & 0xFFFFFFFF);

        return ['start' => $network, 'end' => $broadcast, 'count' => $broadcast - $network + 1];
    }

    /**
     * @return array{start:int,end:int,count:int}
     */
    private function parseStartEnd(string $range): array
    {
        [$startIp, $endIp] = array_pad(explode('-', $range, 2), 2, null);

        if ($startIp === null || $endIp === null) {
            throw new NetworkRangeException("Ungültiger IP-Bereich: {$range}");
        }

        $start = $this->toLong(trim($startIp), 'Start-Adresse');
        $end = $this->toLong(trim($endIp), 'End-Adresse');

        return ['start' => $start, 'end' => $end, 'count' => max(0, $end - $start + 1)];
    }

    private function toLong(string $ip, string $label): int
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            throw new NetworkRangeException("{$label} ist keine gültige IPv4-Adresse: {$ip}");
        }

        $value = ip2long($ip);

        if ($value === false) {
            throw new NetworkRangeException("{$label} konnte nicht verarbeitet werden: {$ip}");
        }

        return $value;
    }
}
