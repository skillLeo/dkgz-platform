<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The staff member's own account.
 *
 * Administrators previously had no way to change their own password except the
 * public reset flow — which meant an account set up by someone else could only
 * be secured by pretending to have forgotten it.
 */
class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('Admin/Profil', [
            'profile' => [
                'first_name' => $request->user()->first_name,
                'last_name' => $request->user()->last_name,
                'email' => $request->user()->email,
                'phone' => $request->user()->phone,
                'roles' => $request->user()->getRoleNames(),
            ],
            'mustChangePassword' => (bool) $request->user()->must_change_password,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:40'],
        ], [], [
            'first_name' => 'der Vorname',
            'last_name' => 'der Nachname',
            'phone' => 'die Telefonnummer',
        ]);

        $request->user()->update([
            ...$data,
            'name' => trim($data['first_name'].' '.$data['last_name']),
        ]);

        return back()->with('success', 'Ihre Angaben wurden gespeichert.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', PasswordResetController::passwordRules()],
        ], [], [
            'current_password' => 'das aktuelle Passwort',
            'password' => 'das neue Passwort',
        ]);

        $request->user()->update([
            'password' => Hash::make($data['password']),
            'must_change_password' => false,
        ]);

        return back()->with('success', 'Ihr Passwort wurde geändert.');
    }
}
