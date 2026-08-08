<?php

declare(strict_types=1);

if ($argc < 2 || $argc > 3) {
    fwrite(STDERR, "Usage: php scripts/coverage-gate.php <clover.xml> [thresholds.json]\n");
    exit(2);
}

$reportPath = $argv[1];
if (! is_file($reportPath)) {
    fwrite(STDERR, "Coverage report not found: {$reportPath}\n");
    exit(2);
}

$xml = simplexml_load_file($reportPath);
if ($xml === false) {
    fwrite(STDERR, "Coverage report is not valid XML: {$reportPath}\n");
    exit(2);
}

$groups = [
    'overall' => static fn (string $path): bool => str_contains($path, '/app/'),
    'policies' => static fn (string $path): bool => str_contains($path, '/app/Policies/'),
    'document_security' => static fn (string $path): bool => in_array(basename($path), [
        'ClamAvMalwareScanner.php',
        'UnavailableMalwareScanner.php',
        'RegistryDocumentUploadService.php',
    ], true) && str_contains($path, '/app/Services/Documents/'),
    'discovery_services' => static fn (string $path): bool => str_contains($path, '/app/Services/Discovery/'),
    'registry_csv_import' => static fn (string $path): bool => str_ends_with($path, '/app/Services/Imports/RegistryCsvImportService.php'),
];

$metrics = array_fill_keys(array_keys($groups), ['covered' => 0, 'total' => 0, 'files' => 0]);

foreach ($xml->xpath('//file') ?: [] as $file) {
    $path = str_replace('\\', '/', (string) $file['name']);
    $fileMetrics = $file->metrics;
    if ($fileMetrics === null) {
        continue;
    }

    $covered = (int) $fileMetrics['coveredstatements'];
    $total = (int) $fileMetrics['statements'];
    foreach ($groups as $name => $matches) {
        if (! $matches($path)) {
            continue;
        }
        $metrics[$name]['covered'] += $covered;
        $metrics[$name]['total'] += $total;
        $metrics[$name]['files']++;
    }
}

$thresholds = [];
if ($argc === 3) {
    $thresholdPath = $argv[2];
    if (! is_file($thresholdPath)) {
        fwrite(STDERR, "Coverage thresholds not found: {$thresholdPath}\n");
        exit(2);
    }
    $decoded = json_decode((string) file_get_contents($thresholdPath), true, flags: JSON_THROW_ON_ERROR);
    if (! is_array($decoded)) {
        fwrite(STDERR, "Coverage thresholds must be a JSON object.\n");
        exit(2);
    }
    $thresholds = $decoded;
}

$missingThresholds = array_diff(array_keys($groups), array_keys($thresholds));
$unknownThresholds = array_diff(array_keys($thresholds), array_keys($groups));
if ($argc === 3 && ($missingThresholds !== [] || $unknownThresholds !== [])) {
    if ($missingThresholds !== []) {
        fwrite(STDERR, 'Missing coverage thresholds: '.implode(', ', $missingThresholds)."\n");
    }
    if ($unknownThresholds !== []) {
        fwrite(STDERR, 'Unknown coverage thresholds: '.implode(', ', $unknownThresholds)."\n");
    }
    exit(2);
}

printf("%-24s %10s %10s %10s %8s\n", 'Group', 'Covered', 'Total', 'Lines', 'Minimum');
$failed = false;
foreach ($metrics as $name => $groupMetrics) {
    if ($groupMetrics['files'] === 0 || $groupMetrics['total'] === 0) {
        fwrite(STDERR, "Coverage group has no executable lines: {$name}\n");
        $failed = true;

        continue;
    }

    $percentage = round(($groupMetrics['covered'] / $groupMetrics['total']) * 100, 2);
    $minimum = array_key_exists($name, $thresholds) ? (float) $thresholds[$name] : null;
    printf(
        "%-24s %10d %10d %9.2f%% %s\n",
        $name,
        $groupMetrics['covered'],
        $groupMetrics['total'],
        $percentage,
        $minimum === null ? '-' : number_format($minimum, 2).'%',
    );

    if ($minimum !== null && $percentage < $minimum) {
        fwrite(STDERR, "Coverage below threshold for {$name}: {$percentage}% < {$minimum}%\n");
        $failed = true;
    }
}

exit($failed ? 1 : 0);
