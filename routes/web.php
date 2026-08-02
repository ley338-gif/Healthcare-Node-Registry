<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CapabilityMatrixDiagnosticController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DiagnosticProfileExecutionController;
use App\Http\Controllers\DiagnosticResultExportController;
use App\Http\Controllers\DiagnosticTestProfileController;
use App\Http\Controllers\DicomConnectionController;
use App\Http\Controllers\DicomFileAnalysisController;
use App\Http\Controllers\DicomNetworkMapController;
use App\Http\Controllers\DicomNodeController;
use App\Http\Controllers\GetDiagnosticController;
use App\Http\Controllers\GlobalSearchController;
use App\Http\Controllers\MoveDiagnosticController;
use App\Http\Controllers\MppsDiagnosticController;
use App\Http\Controllers\NetworkDiagnosticController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\OrganizationStructureController;
use App\Http\Controllers\PacsQueryDiagnosticController;
use App\Http\Controllers\RegistryDocumentationController;
use App\Http\Controllers\RegistryDocumentController;
use App\Http\Controllers\RegistryDocumentIndexController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SettingsRoleController;
use App\Http\Controllers\SettingsUserController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\StorageCommitmentDiagnosticController;
use App\Http\Controllers\StorageDiagnosticController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\TestWorkspaceController;
use App\Http\Controllers\WorklistDiagnosticController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/search', GlobalSearchController::class)->middleware('throttle:60,1')->name('search');
    Route::get('/audit', [AuditController::class, 'index'])->name('audit.index');
    Route::get('/audit/export/{format}', [AuditController::class, 'export'])->whereIn('format', ['csv'])->name('audit.export');
    Route::get('/settings', SettingsController::class)->name('settings.index');
    Route::post('/settings/users', [SettingsUserController::class, 'store'])->name('settings.users.store');
    Route::put('/settings/users/{user}', [SettingsUserController::class, 'update'])->name('settings.users.update');
    Route::put('/settings/users/{user}/password', [SettingsUserController::class, 'resetPassword'])->name('settings.users.password');
    Route::post('/settings/roles', [SettingsRoleController::class, 'store'])->name('settings.roles.store');
    Route::put('/settings/roles/{role}', [SettingsRoleController::class, 'update'])->name('settings.roles.update');
    Route::delete('/settings/roles/{role}', [SettingsRoleController::class, 'destroy'])->name('settings.roles.destroy');

    Route::get('/structure', OrganizationStructureController::class)->name('structure.index');
    Route::get('/documents', RegistryDocumentIndexController::class)->name('documents.index');

    Route::get('/registry-documentation/{documentableType}/{documentable}', [RegistryDocumentationController::class, 'index'])
        ->whereIn('documentableType', ['organizations', 'sites', 'departments', 'systems'])
        ->name('registry-documentation.index');
    Route::post('/registry-documentation/{documentableType}/{documentable}', [RegistryDocumentationController::class, 'store'])
        ->whereIn('documentableType', ['organizations', 'sites', 'departments', 'systems'])
        ->name('registry-documentation.store');
    Route::put('/registry-documentation/{registryDocumentation}', [RegistryDocumentationController::class, 'update'])
        ->name('registry-documentation.update');
    Route::post('/registry-documents/{documentableType}/{documentable}', [RegistryDocumentController::class, 'store'])
        ->whereIn('documentableType', ['organizations', 'sites', 'departments', 'systems'])
        ->name('registry-documents.store');
    Route::post('/registry-documents/{registryDocument}/versions', [RegistryDocumentController::class, 'storeVersion'])
        ->name('registry-documents.versions.store');
    Route::put('/registry-documents/{registryDocument}', [RegistryDocumentController::class, 'update'])
        ->name('registry-documents.update');
    Route::post('/registry-documents/{registryDocument}/archive', [RegistryDocumentController::class, 'archive'])
        ->name('registry-documents.archive');
    Route::post('/registry-documents/{registryDocument}/restore', [RegistryDocumentController::class, 'restore'])
        ->name('registry-documents.restore');
    Route::get('/registry-document-versions/{registryDocumentVersion}/download', [RegistryDocumentController::class, 'downloadVersion'])
        ->name('registry-document-versions.download');
    Route::get('/registry-document-versions/{registryDocumentVersion}/preview', [RegistryDocumentController::class, 'previewVersion'])
        ->name('registry-document-versions.preview');

    Route::get('/organizations', [OrganizationController::class, 'index'])->name('organizations.index');
    Route::post('/organizations', [OrganizationController::class, 'store'])->name('organizations.store');
    Route::put('/organizations/{organization}', [OrganizationController::class, 'update'])->name('organizations.update');
    Route::post('/organizations/{organization}/archive', [OrganizationController::class, 'archive'])->name('organizations.archive');

    Route::get('/sites', [SiteController::class, 'index'])->name('sites.index');
    Route::post('/sites', [SiteController::class, 'store'])->name('sites.store');
    Route::put('/sites/{site}', [SiteController::class, 'update'])->name('sites.update');
    Route::post('/sites/{site}/archive', [SiteController::class, 'archive'])->name('sites.archive');

    Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
    Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
    Route::put('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
    Route::post('/departments/{department}/archive', [DepartmentController::class, 'archive'])->name('departments.archive');

    Route::post('/systems', [SystemController::class, 'store'])
        ->name('systems.store');

    Route::put('/systems/{system}', [SystemController::class, 'update'])
        ->name('systems.update');

    Route::post('/systems/{system}/archive', [SystemController::class, 'archive'])
        ->name('systems.archive');

    Route::get('/systems', [SystemController::class, 'index'])
        ->name('systems.index');

    Route::get('/systems/{system}', [SystemController::class, 'show'])
        ->name('systems.show');

    Route::post('/systems/{system}/dicom-nodes', [DicomNodeController::class, 'store'])
        ->name('dicom-nodes.store');

    Route::put('/dicom-nodes/{dicomNode}', [DicomNodeController::class, 'update'])
        ->name('dicom-nodes.update');

    Route::post('/dicom-nodes/{dicomNode}/archive', [DicomNodeController::class, 'archive'])
        ->name('dicom-nodes.archive');

    Route::post('/dicom-nodes/{dicomNode}/verify', [DicomNodeController::class, 'verify'])
        ->name('dicom-nodes.verify');

    Route::post('/dicom-connections', [DicomConnectionController::class, 'store'])
        ->name('dicom-connections.store');

    Route::get('/connections', [DicomConnectionController::class, 'index'])
        ->name('connections.index');

    Route::put('/dicom-connections/{dicomConnection}', [DicomConnectionController::class, 'update'])
        ->name('dicom-connections.update');

    Route::post('/dicom-connections/{dicomConnection}/archive', [DicomConnectionController::class, 'archive'])
        ->name('dicom-connections.archive');

    Route::post('/dicom-connections/{dicomConnection}/duplicate', [DicomConnectionController::class, 'duplicate'])
        ->name('dicom-connections.duplicate');

    Route::get('/network', DicomNetworkMapController::class)
        ->name('network.index');

    Route::get('/tests', TestWorkspaceController::class)
        ->name('tests.index');

    Route::post('/tests/network/{dicomNode}', NetworkDiagnosticController::class)
        ->name('tests.network.run');

    Route::post('/tests/worklist/{dicomNode}', WorklistDiagnosticController::class)
        ->name('tests.worklist.run');
    Route::post('/tests/mpps/{dicomNode}', MppsDiagnosticController::class)
        ->name('tests.mpps.run');

    Route::post('/tests/pacs-query/{dicomNode}', PacsQueryDiagnosticController::class)
        ->name('tests.pacs-query.run');
    Route::post('/tests/storage/{dicomNode}', StorageDiagnosticController::class)
        ->name('tests.storage.run');
    Route::post('/tests/storage-commitment/{dicomNode}', StorageCommitmentDiagnosticController::class)
        ->name('tests.storage-commitment.run');
    Route::post('/tests/move/{dicomConnection}', MoveDiagnosticController::class)
        ->name('tests.move.run');
    Route::post('/tests/get/{dicomConnection}', GetDiagnosticController::class)
        ->name('tests.get.run');
    Route::post('/tests/capabilities/{dicomNode}', CapabilityMatrixDiagnosticController::class)
        ->name('tests.capabilities.run');
    Route::post('/tests/dicom-file-analysis', DicomFileAnalysisController::class)
        ->name('tests.dicom-file-analysis.run');
    Route::get('/tests/history/{run}/export/{format}', DiagnosticResultExportController::class)
        ->whereIn('format', ['json', 'csv'])
        ->name('tests.history.export');

    Route::post('/tests/profiles', [DiagnosticTestProfileController::class, 'store'])
        ->name('tests.profiles.store');
    Route::put('/tests/profiles/{profile}', [DiagnosticTestProfileController::class, 'update'])
        ->name('tests.profiles.update');
    Route::post('/tests/profiles/{profile}/archive', [DiagnosticTestProfileController::class, 'archive'])
        ->name('tests.profiles.archive');
    Route::post('/tests/profiles/{profile}/execute', DiagnosticProfileExecutionController::class)
        ->name('tests.profiles.execute');
});
