<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Support\RegistryAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function store(LoginRequest $request, RegistryAudit $audit): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();
        $user = $request->user();
        if ($user instanceof User) {
            $audit->record('auth.login', $user, $user, ['ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]);
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function destroy(Request $request, RegistryAudit $audit): RedirectResponse
    {
        $user = $request->user();
        if ($user instanceof User) {
            $audit->record('auth.logout', $user, $user, ['ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]);
        }
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
