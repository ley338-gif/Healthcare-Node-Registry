<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\DiagnosticTestRun;
use App\Models\DicomConnection;
use App\Models\DicomNode;
use App\Models\Organization;
use App\Models\RegistryDocument;
use App\Models\SecurityEvent;
use App\Models\Site;
use App\Models\System;
use App\Notifications\RegistryDocumentExpiryNotification;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $organizations = Organization::query()->active()->count();
        $sites = Site::query()->active()->count();
        $departments = Department::query()->active()->count();
        $systems = System::query()->active()->count();
        $dicomNodes = DicomNode::query()->active()->count();
        $connections = DicomConnection::query()->active()->count();

        $user = $request->user();
        $canViewDocuments = $user?->hasPermission('documents.view') ?? false;
        $expiringDocuments = null;
        if ($canViewDocuments && $user !== null) {
            $today = CarbonImmutable::today();
            $warningEnd = $today->addDays(max(0, (int) config('registry_documents.expiry_warning_days')));
            $expiryQuery = RegistryDocument::query()
                ->whereNull('archived_at')
                ->where('status', '!=', 'archived')
                ->whereNotNull('valid_until')
                ->whereDate('valid_until', '<=', $warningEnd);

            $notificationIds = [];
            foreach ($user->unreadNotifications()->where('type', RegistryDocumentExpiryNotification::class)->get() as $notification) {
                $documentPublicId = $notification->data['document_public_id'] ?? null;
                if (is_string($documentPublicId)) {
                    $notificationIds[$documentPublicId] = $notification->id;
                }
            }

            $expiringDocuments = [
                'total' => (clone $expiryQuery)->count(),
                'expired' => (clone $expiryQuery)->whereDate('valid_until', '<', $today)->count(),
                'expiringSoon' => (clone $expiryQuery)->whereDate('valid_until', '>=', $today)->count(),
                'warningDays' => max(0, (int) config('registry_documents.expiry_warning_days')),
                'items' => $expiryQuery
                    ->with('documentable')
                    ->orderByRaw('CASE WHEN valid_until < ? THEN 0 ELSE 1 END', [$today->toDateString()])
                    ->orderByRaw('CASE WHEN valid_until < ? THEN valid_until END DESC', [$today->toDateString()])
                    ->orderBy('valid_until')
                    ->limit(8)
                    ->get()
                    ->map(function (RegistryDocument $document) use ($notificationIds, $today): array {
                        $daysRemaining = (int) $today->diffInDays($document->valid_until, false);
                        $notificationId = $notificationIds[$document->public_id] ?? null;

                        return [
                            'publicId' => $document->public_id,
                            'title' => $document->title,
                            'categoryLabel' => $document->category->label(),
                            'contextName' => (string) ($document->documentable?->getAttribute('name') ?? ''),
                            'validUntil' => $document->valid_until?->toDateString(),
                            'daysRemaining' => $daysRemaining,
                            'status' => $daysRemaining < 0 ? 'expired' : 'expiring_soon',
                            'unread' => $notificationId !== null,
                            'href' => $notificationId !== null
                                ? route('notifications.show', ['notification' => $notificationId])
                                : route('documents.index', ['document' => $document->public_id]),
                        ];
                    })
                    ->values()
                    ->all(),
            ];
        }

        $failedDicomNodes = DicomNode::query()
            ->active()
            ->whereNotNull('last_verification_status')
            ->where('last_verification_status', '!=', 'success')
            ->count();

        $unverifiedDicomNodes = DicomNode::query()
            ->active()
            ->where('supports_echo', true)
            ->whereNull('last_verified_at')
            ->count();

        /** @var Collection<int, SecurityEvent> $events */
        $events = SecurityEvent::query()
            ->where('event_type', 'like', 'registry.%')
            ->latest('occurred_at')
            ->limit(8)
            ->get();

        $recentChanges = $events
            ->map(fn (SecurityEvent $event): array => [
                'event_type' => $event->event_type,
                'label' => $this->eventLabel($event->event_type),
                'subject_type' => class_basename($event->subject_type ?? ''),
                'subject_public_id' => $event->subject_public_id,
                'subject_label' => $this->subjectLabel($event),
                'occurred_at' => $event->occurred_at->toIso8601String(),
            ])
            ->values()
            ->all();

        $canViewDiagnostics = $request->user()?->hasPermission('registry.view')
            || $request->user()?->hasPermission('registry.manage');
        $diagnostics = null;
        if ($canViewDiagnostics) {
            $lastSuccessfulEcho = DiagnosticTestRun::query()
                ->where('test_type', 'dicom_echo')
                ->where('status', 'success')
                ->latest('finished_at')
                ->first();
            $diagnostics = [
                'failedTests' => DiagnosticTestRun::query()->whereIn('status', ['failed', 'timeout'])->count(),
                'averageDurationMilliseconds' => (int) round((float) (DiagnosticTestRun::query()->avg('duration_ms') ?? 0)),
                'lastSuccessfulEchoAt' => $lastSuccessfulEcho?->finished_at->toIso8601String(),
                'recentTests' => DiagnosticTestRun::query()
                    ->with('dicomNode:id,public_id,name')
                    ->latest('started_at')
                    ->limit(5)
                    ->get()
                    ->map(static fn (DiagnosticTestRun $run): array => [
                        'publicId' => $run->public_id,
                        'testType' => $run->test_type,
                        'status' => $run->status,
                        'durationMilliseconds' => $run->duration_ms,
                        'startedAt' => $run->started_at->toIso8601String(),
                        'dicomNode' => ['publicId' => $run->dicomNode->public_id, 'name' => $run->dicomNode->name],
                    ])
                    ->values()
                    ->all(),
            ];
        }

        return Inertia::render('Dashboard', [
            'summary' => [
                'organizations' => $organizations,
                'sites' => $sites,
                'departments' => $departments,
                'systems' => $systems,
                'dicomNodes' => $dicomNodes,
                'connections' => $connections,
                'failedDicomNodes' => $failedDicomNodes,
                'unverifiedDicomNodes' => $unverifiedDicomNodes,
            ],
            'recentChanges' => $recentChanges,
            'diagnostics' => $diagnostics,
            'expiringDocuments' => $expiringDocuments,
            'tasks' => [
                [
                    'label' => 'Mindestens ein System dokumentieren',
                    'completed' => $systems > 0,
                    'href' => '/systems',
                ],
                [
                    'label' => 'DICOM-Knoten erfassen',
                    'completed' => $dicomNodes > 0,
                    'href' => '/systems',
                ],
                [
                    'label' => 'DICOM-Verbindungen modellieren',
                    'completed' => $connections > 0,
                    'href' => '/systems',
                ],
                [
                    'label' => 'C-ECHO-Prüfungen durchführen',
                    'completed' => ($dicomNodes - $unverifiedDicomNodes) > 0,
                    'href' => '/systems',
                ],
            ],
        ]);
    }

    private function eventLabel(string $eventType): string
    {
        return match ($eventType) {
            'registry.organization.created' => 'Organisation angelegt',
            'registry.organization.updated' => 'Organisation geändert',
            'registry.organization.archived' => 'Organisation archiviert',
            'registry.site.created' => 'Standort angelegt',
            'registry.site.updated' => 'Standort geändert',
            'registry.site.archived' => 'Standort archiviert',
            'registry.department.created' => 'Abteilung angelegt',
            'registry.department.updated' => 'Abteilung geändert',
            'registry.department.archived' => 'Abteilung archiviert',
            'registry.system.created' => 'System angelegt',
            'registry.system.updated' => 'System geändert',
            'registry.system.archived' => 'System archiviert',
            'registry.dicom_node.created' => 'DICOM-Knoten angelegt',
            'registry.dicom_node.updated' => 'DICOM-Knoten geändert',
            'registry.dicom_node.archived' => 'DICOM-Knoten archiviert',
            'registry.dicom_node.verified' => 'DICOM-Knoten geprüft',
            'registry.dicom_connection.created' => 'DICOM-Verbindung angelegt',
            'registry.dicom_connection.updated' => 'DICOM-Verbindung geändert',
            'registry.dicom_connection.archived' => 'DICOM-Verbindung archiviert',
            default => 'Registry geändert',
        };
    }

    private function subjectLabel(SecurityEvent $event): ?string
    {
        if ($event->subject_public_id === null) {
            return null;
        }

        $model = match ($event->subject_type) {
            Organization::class => Organization::query()->where('public_id', $event->subject_public_id)->first(),
            Site::class => Site::query()->where('public_id', $event->subject_public_id)->first(),
            Department::class => Department::query()->where('public_id', $event->subject_public_id)->first(),
            System::class => System::query()->where('public_id', $event->subject_public_id)->first(),
            DicomNode::class => DicomNode::query()->where('public_id', $event->subject_public_id)->first(),
            DicomConnection::class => DicomConnection::query()->where('public_id', $event->subject_public_id)->first(),
            default => null,
        };

        if ($model === null) {
            return null;
        }

        return (string) ($model->getAttribute('name') ?? $event->subject_public_id);
    }
}
