<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function show(Request $request): Response
    {
        return Inertia::render('Auth/Anmelden', [
            'admin' => false,
            'canRegister' => Settings::bool('features.self_registration', true),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function showAdmin(Request $request): Response
    {
        return Inertia::render('Auth/Anmelden', [
            'admin' => true,
            'canRegister' => false,
            'status' => $request->session()->get('status'),
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->saveQuietly();

        return redirect()->intended($this->destinationFor($user));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    /**
     * Assessors land in the portal, staff in the admin. The approval gate on
     * the portal routes redirects further if the partner is not yet cleared.
     */
    private function destinationFor($user): string
    {
        return $user->hasRole('assessor')
            ? route('portal.dashboard')
            : route('admin.dashboard');
    }
}
