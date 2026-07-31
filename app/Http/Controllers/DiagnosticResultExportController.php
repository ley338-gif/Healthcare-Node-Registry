<?php

namespace App\Http\Controllers;

use App\Models\DiagnosticTestRun;
use App\Services\Diagnostics\DiagnosticResultExporter;
use App\Support\RegistryAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DiagnosticResultExportController extends Controller
{
    public function __invoke(Request $request, DiagnosticTestRun $run, string $format, DiagnosticResultExporter $exporter, RegistryAudit $audit): StreamedResponse
    {
        Gate::authorize('view', $run);
        abort_unless($request->user()?->hasPermission('tests.export'), 403);
        abort_unless(in_array($format, ['json', 'csv'], true), 404);
        $content = $format === 'json' ? $exporter->json($run) : $exporter->csv($run);
        $audit->record('diagnostics.result-exported', $run, $request->user(), ['format' => $format, 'test_id' => $run->public_id]);

        return response()->streamDownload(static function () use ($content): void {
            echo $content;
        }, "diagnostic-{$run->public_id}.{$format}", ['Content-Type' => $format === 'json' ? 'application/json; charset=UTF-8' : 'text/csv; charset=UTF-8']);
    }
}
