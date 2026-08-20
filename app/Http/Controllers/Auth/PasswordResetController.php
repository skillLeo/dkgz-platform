<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetController extends Controller
{
    public function request(): Response
    {
        return Inertia::render('Auth/PasswortVergessen', [
            'status' => session('status'),
        ]);
    }

    /**
     * Always reports the same outcome, whether or not the address exists —
     * otherwise the form becomes a way to enumerate accounts.
     */
    public function send(Request $request): RedirectResponse
    {
        $request->validate(
            ['email' => ['required', 'string', 'email', 'max:255']],
            [],
            ['email' => 'die E-Mail-Adresse']
        );

        Password::sendResetLink($request->only('email'));

        return back()->with('status', trans('passwords.sent'));
    }

    public function reset(Request $request, string $token): Response
    {
        return Inertia::render('Auth/PasswortZuruecksetzen', [
            'token' => $token,
            'email' => $request->query('email'),
            // Checked up front so an expired link shows the design's recovery
            // state rather than letting the visitor fill the form and fail.
            'expired' => $this->tokenHasExpired($request->query('email'), $token),
        ]);
    }

    /**
     * A token is only meaningful together with its address; without one we
     * cannot judge it, and the form's own validation will catch the rest.
     */
    private function tokenHasExpired(?string $email, string $token): bool
    {
        if (blank($email)) {
            return false;
        }

        $user = Password::getUser(['email' => $email]);

        if ($user === null) {
            return false;
        }

        return ! Password::tokenExists($user, $token);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'confirmed', $this->passwordRules()],
        ], [], [
            'email' => 'die E-Mail-Adresse',
            'password' => 'das Passwort',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => trans($status)]);
        }

        return redirect()->route('login')->with('status', trans('passwords.reset'));
    }

    /** Minimum 10, mixed case, a number, a symbol, and not in a known breach. */
    public static function passwordRules(): PasswordRule
    {
        return PasswordRule::min(10)->mixedCase()->numbers()->symbols()->uncompromised();
    }
}
