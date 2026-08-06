<?php

namespace App\Http\Controllers\Discovery;

use App\Http\Controllers\Controller;
use App\Models\DicomNode;
use App\Models\DiscoveryAllowedNetwork;
use App\Models\DiscoveryRun;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class DiscoveryRunWizardController extends Controller
{
    private const DICOM_PORT_LABELS = [
        104 => 'DICOM Standard (Well-Known Port)',
        11112 => 'DICOM Standard (registriert)',
        11113 => 'DICOM Storage Commitment (Callback)',
        2761 => 'DICOM TLS',
        2762 => 'DICOM ISCL',
        4242 => 'DICOM (herstellerspezifisch verbreitet)',
        5678 => 'DICOM (herstellerspezifisch verbreitet)',
        7104 => 'DICOM (herstellerspezifisch verbreitet)',
        8000 => 'DICOM über HTTP-typischen Port',
        8042 => 'Orthanc REST/DICOMweb (verbreitet)',
        8104 => 'DICOM (herstellerspezifisch verbreitet)',
    ];

    private const INFRASTRUCTURE_PORT_LABELS = [
        80 => 'HTTP',
        443 => 'HTTPS',
        389 => 'LDAP',
        636 => 'LDAPS',
        1433 => 'Microsoft SQL Server',
        1521 => 'Oracle DB',
        3306 => 'MySQL/MariaDB',
        5432 => 'PostgreSQL',
    ];

    public function create(Request $request): Response
    {
        Gate::authorize('create', DiscoveryRun::class);

        /** @var list<int> $defaultDicomPortNumbers */
        $defaultDicomPortNumbers = config('discovery.default_dicom_ports', []);
        $defaultDicomPorts = collect($defaultDicomPortNumbers)
            ->map(fn (int $port): array => ['port' => $port, 'protocol' => 'tcp', 'label' => self::DICOM_PORT_LABELS[$port] ?? 'DICOM-Kandidatport', 'is_dicom_candidate' => true, 'enabled' => true])
            ->values();

        /** @var list<int> $optionalPortNumbers */
        $optionalPortNumbers = config('discovery.optional_infrastructure_ports', []);
        $optionalPorts = collect($optionalPortNumbers)
            ->map(fn (int $port): array => ['port' => $port, 'protocol' => 'tcp', 'label' => self::INFRASTRUCTURE_PORT_LABELS[$port] ?? 'Infrastruktur-Port', 'is_dicom_candidate' => false, 'enabled' => false])
            ->values();

        $registryAeTitles = DicomNode::query()->active()->distinct()->orderBy('ae_title')->pluck('ae_title')->values();

        return Inertia::render('Discovery/Wizard/Create', [
            'defaultDicomPorts' => $defaultDicomPorts,
            'optionalPorts' => $optionalPorts,
            'registryAeTitles' => $registryAeTitles,
            'defaultCallingAe' => config('discovery.default_calling_ae_title', 'HNR_DISCOVERY'),
            'maxRangeSize' => (int) config('discovery.max_range_size', 1024),
            'largeRangeWarningThreshold' => (int) config('discovery.large_range_warning_threshold', 256),
            'maxParallelHostsLimit' => (int) config('discovery.max_parallel_hosts', 16),
            'maxAeAttemptsPerPort' => (int) config('discovery.max_ae_attempts_per_port', 5),
            'allowedNetworks' => DiscoveryAllowedNetwork::query()->active()->orderBy('cidr')->get(['cidr', 'description']),
        ]);
    }
}
