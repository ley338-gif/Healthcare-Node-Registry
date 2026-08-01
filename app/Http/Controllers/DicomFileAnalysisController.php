<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnalyzeDicomFileRequest;
use App\Services\Diagnostics\DicomFileAnalyzer;
use App\Support\RegistryAudit;
use Illuminate\Http\RedirectResponse;

final class DicomFileAnalysisController extends Controller
{
    public function __invoke(AnalyzeDicomFileRequest $request, DicomFileAnalyzer $analyzer, RegistryAudit $audit): RedirectResponse
    {
        $result = $analyzer->analyze($request->file('dicom_file'));
        $audit->record('diagnostics.file-analysis.completed', $request->user(), $request->user(), ['successful' => $result->successful, 'file_size' => $result->summary['fileSize'] ?? null]);
        $response = to_route('tests.index')->with('dicomFileAnalysis', $result->toArray());

        return $result->successful ? $response->with('success', 'DICOM-Datei analysiert.') : $response->with('error', $result->errors[0] ?? 'DICOM-Analyse fehlgeschlagen.');
    }
}
