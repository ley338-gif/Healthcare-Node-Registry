<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Search\GlobalSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GlobalSearchController extends Controller
{
    public function __invoke(Request $request, GlobalSearchService $search): JsonResponse
    {
        $validated = $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']]);
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return response()->json(['results' => $search->search($user, trim($validated['q']))]);
    }
}
