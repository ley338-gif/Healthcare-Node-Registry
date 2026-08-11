<?php

namespace App\Http\Controllers\Discovery;

use App\Http\Controllers\Controller;
use App\Http\Requests\PreviewDiscoveryAeCandidateImportRequest;
use App\Services\Discovery\DiscoveryAeCandidateImportService;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

final class DiscoveryAeCandidateImportController extends Controller
{
    public function __invoke(
        PreviewDiscoveryAeCandidateImportRequest $request,
        DiscoveryAeCandidateImportService $service,
    ): RedirectResponse {
        try {
            $preview = $service->preview($request->file('ae_candidate_file'));
        } catch (RuntimeException $exception) {
            return back()->withErrors(['ae_candidate_file' => $exception->getMessage()]);
        }

        return back()->with('discovery_ae_import_preview', $preview);
    }
}
