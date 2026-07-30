<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Organization;
use App\Models\Site;
use App\Models\System;
use Inertia\Inertia;
use Inertia\Response;

final class OrganizationStructureController extends Controller
{
    public function __invoke(): Response
    {
        $organizations = Organization::query()
            ->active()
            ->with([
                'sites' => fn ($query) => $query
                    ->active()
                    ->with([
                        'departments' => fn ($departmentQuery) => $departmentQuery
                            ->active()
                            ->orderBy('name'),
                    ])
                    ->orderBy('name'),
            ])
            ->orderBy('name')
            ->get([
                'id',
                'public_id',
                'name',
                'short_name',
                'description',
                'updated_at',
            ]);

        $systems = System::query()
            ->active()
            ->withCount('dicomNodes')
            ->orderBy('name')
            ->get([
                'public_id',
                'organization_id',
                'site_id',
                'department_id',
                'name',
                'system_type',
                'status',
                'hostname',
                'ip_address',
                'vendor',
                'product',
            ]);

        return Inertia::render('OrganizationStructure/Index', [
            'summary' => [
                'organizations' => Organization::query()->active()->count(),
                'sites' => Site::query()->active()->count(),
                'departments' => Department::query()->active()->count(),
            ],
            'organizations' => $organizations,
            'systems' => $systems,
        ]);
    }
}
