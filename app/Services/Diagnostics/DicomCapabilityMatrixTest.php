<?php

namespace App\Services\Diagnostics;

use App\Models\DicomNode;
use DateTimeImmutable;
use Illuminate\Support\Str;

final class DicomCapabilityMatrixTest
{
    /** @var array<string, array{label: string, uid: string}> */
    public const SOP_CLASSES = [
        'ct' => ['label' => 'CT Image Storage', 'uid' => '1.2.840.10008.5.1.4.1.1.2'],
        'mr' => ['label' => 'MR Image Storage', 'uid' => '1.2.840.10008.5.1.4.1.1.4'],
        'ultrasound' => ['label' => 'Ultrasound Image Storage', 'uid' => '1.2.840.10008.5.1.4.1.1.6.1'],
        'secondary_capture' => ['label' => 'Secondary Capture Image Storage', 'uid' => '1.2.840.10008.5.1.4.1.1.7'],
        'encapsulated_pdf' => ['label' => 'Encapsulated PDF Storage', 'uid' => '1.2.840.10008.5.1.4.1.1.104.1'],
        'basic_text_sr' => ['label' => 'Basic Text SR', 'uid' => '1.2.840.10008.5.1.4.1.1.88.11'],
        'radiation_dose_sr' => ['label' => 'Radiation Dose SR', 'uid' => '1.2.840.10008.5.1.4.1.1.88.67'],
    ];

    /** @var array<string, array{label: string, uid: string}> */
    public const TRANSFER_SYNTAXES = [
        'implicit_le' => ['label' => 'Implicit VR Little Endian', 'uid' => '1.2.840.10008.1.2'],
        'explicit_le' => ['label' => 'Explicit VR Little Endian', 'uid' => '1.2.840.10008.1.2.1'],
        'explicit_be' => ['label' => 'Explicit VR Big Endian', 'uid' => '1.2.840.10008.1.2.2'],
        'jpeg_lossless' => ['label' => 'JPEG Lossless', 'uid' => '1.2.840.10008.1.2.4.70'],
        'jpeg_baseline' => ['label' => 'JPEG Baseline', 'uid' => '1.2.840.10008.1.2.4.50'],
        'jpeg_2000_lossless' => ['label' => 'JPEG 2000 Lossless', 'uid' => '1.2.840.10008.1.2.4.90'],
        'rle_lossless' => ['label' => 'RLE Lossless', 'uid' => '1.2.840.10008.1.2.5'],
    ];

    public function __construct(private readonly ?CapabilityMatrixRunner $runner = null) {}

    public function run(DicomNode $node, string $callingAeTitle, string $calledAeTitle): DiagnosticTestResult
    {
        $started = new DateTimeImmutable;
        $contexts = [];
        $lookup = [];
        $id = 1;
        foreach (self::SOP_CLASSES as $sopKey => $sop) {
            foreach (self::TRANSFER_SYNTAXES as $syntaxKey => $syntax) {
                $contexts[] = ['id' => $id, 'sopClassUid' => $sop['uid'], 'transferSyntaxUid' => $syntax['uid']];
                $lookup[$id] = [$sopKey, $syntaxKey];
                $id += 2;
            }
        }
        $timer = hrtime(true);
        $negotiation = ($this->runner ?? new NativeCapabilityMatrixRunner)->run($node, $callingAeTitle, $calledAeTitle, $contexts);
        $duration = (int) round((hrtime(true) - $timer) / 1_000_000);
        $matrix = [];
        foreach ($lookup as $contextId => [$sopKey, $syntaxKey]) {
            $result = $negotiation->presentationContextResults[$contextId] ?? null;
            $matrix[] = ['sopClass' => $sopKey, 'transferSyntax' => $syntaxKey, 'status' => ! $negotiation->associationAccepted ? 'not_tested' : ($result === 0 ? 'accepted' : ($result === null ? 'not_tested' : ($result === 3 || $result === 4 ? 'unsupported' : 'rejected'))), 'verification' => 'presentation_context'];
        }
        $accepted = count(array_filter($matrix, static fn (array $cell): bool => $cell['status'] === 'accepted'));
        $status = $negotiation->associationAccepted ? DiagnosticTestStatus::Success : ($negotiation->timedOut ? DiagnosticTestStatus::Timeout : DiagnosticTestStatus::Failed);
        $summary = $negotiation->associationAccepted ? "Capability-Negotiation abgeschlossen: {$accepted} Presentation Contexts akzeptiert." : ($negotiation->error ?? 'Capability-Negotiation fehlgeschlagen.');

        return new DiagnosticTestResult((string) Str::uuid7(), 'dicom_capability_matrix', $status, $started, new DateTimeImmutable, $duration, $summary,
            new DiagnosticTarget($node->host, $node->port, $calledAeTitle, $callingAeTitle, $node->public_id, $node->system->public_id),
            [new DiagnosticTestStep('association_negotiation', 'DICOM Association Negotiation', $status, $duration, $summary)],
            ['matrix' => $matrix, 'sopClasses' => self::SOP_CLASSES, 'transferSyntaxes' => self::TRANSFER_SYNTAXES, 'verificationMode' => 'presentation_context', 'resultCount' => $accepted],
            ['Es wurden ausschließlich Presentation Contexts geprüft; es wurde kein C-STORE ausgeführt.'],
            $negotiation->associationAccepted ? [] : [$summary]);
    }
}
