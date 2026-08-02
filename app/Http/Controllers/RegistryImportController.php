<?php

namespace App\Http\Controllers;

use App\Http\Requests\PreviewRegistryImportRequest;
use App\Models\User;
use App\Services\Imports\RegistryCsvImportService;
use App\Support\RegistryAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

final class RegistryImportController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->hasPermission('registry.manage'), 403);
        $token = (string) $request->query('token', '');

        return Inertia::render('Registry/Imports/Index', [
            'preview' => $token === '' ? null : $request->session()->get("registry_import.{$token}"),
            'token' => $token === '' ? null : $token,
        ]);
    }

    public function preview(PreviewRegistryImportRequest $request, RegistryCsvImportService $service): RedirectResponse
    {
        try {
            $preview = $service->preview((string) $request->validated('kind'), $request->file('csv_file'));
        } catch (RuntimeException $exception) {
            return back()->withErrors(['csv_file' => $exception->getMessage()]);
        }
        $token = (string) Str::uuid7();
        $request->session()->put("registry_import.{$token}", $preview);

        return redirect()->route('systems.import.index', ['token' => $token]);
    }

    public function store(Request $request, string $token, RegistryCsvImportService $service, RegistryAudit $audit): RedirectResponse
    {
        abort_unless($request->user()?->hasPermission('registry.manage'), 403);
        /** @var array{kind:string,rows:list<array<string,mixed>>}|null $preview */
        $preview = $request->session()->pull("registry_import.{$token}");
        abort_if($preview === null, 404);
        $result = $service->import($preview);
        /** @var User $user */
        $user = $request->user();
        $audit->record('registry.csv_import.completed', $user, $user, ['kind' => $preview['kind'], ...$result]);

        return redirect()->route('systems.index')->with('success', "Import abgeschlossen: {$result['imported']} importiert, {$result['skipped']} übersprungen, {$result['failed']} fehlerhaft.");
    }
}
