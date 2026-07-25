<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Organization;
use App\Models\Site;
use Inertia\Inertia;
use Inertia\Response;

final class OrganizationStructureController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('OrganizationStructure/Index', [
            'summary' => [
                'organizations' => Organization::query()->active()->count(),
                'sites' => Site::query()->active()->count(),
                'departments' => Department::query()->active()->count(),
            ],
            'recentOrganizations' => Organization::query()
                ->active()
                ->latest('updated_at')
                ->limit(5)
                ->get(['public_id', 'name', 'short_name', 'updated_at']),
            'recentSites' => Site::query()
                ->active()
                ->with('organization:id,name')
                ->latest('updated_at')
                ->limit(5)
                ->get(['public_id', 'organization_id', 'name', 'city', 'updated_at']),
            'recentDepartments' => Department::query()
                ->active()
                ->with('site:id,name')
                ->latest('updated_at')
                ->limit(5)
                ->get(['public_id', 'site_id', 'name', 'specialty', 'updated_at']),
        ]);
    }
}
