<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Dashboard', [
            'summary' => [
                'systems' => 0,
                'endpoints' => 0,
                'connections' => 0,
                'documents' => 0,
            ],
            'foundationStatus' => [
                ['label' => 'Authentisierung', 'status' => 'bereit'],
                ['label' => 'PostgreSQL', 'status' => 'bereit'],
                ['label' => 'Registry Core', 'status' => 'geplant'],
                ['label' => 'Topologie', 'status' => 'geplant'],
            ],
        ]);
    }
}
