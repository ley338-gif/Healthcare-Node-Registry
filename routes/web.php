<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DicomConnectionController;
use App\Http\Controllers\DicomNetworkMapController;
use App\Http\Controllers\DicomNodeController;
use App\Http\Controllers\NetworkDiagnosticController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\OrganizationStructureController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\TestWorkspaceController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/structure', OrganizationStructureController::class)->name('structure.index');

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

    Route::put('/dicom-connections/{dicomConnection}', [DicomConnectionController::class, 'update'])
        ->name('dicom-connections.update');

    Route::post('/dicom-connections/{dicomConnection}/archive', [DicomConnectionController::class, 'archive'])
        ->name('dicom-connections.archive');

    Route::get('/network', DicomNetworkMapController::class)
        ->name('network.index');

    Route::get('/tests', TestWorkspaceController::class)
        ->name('tests.index');

    Route::post('/tests/network/{dicomNode}', NetworkDiagnosticController::class)
        ->name('tests.network.run');
});
