<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailVerificationController extends Controller
{
    public function notice(Request $request): Response|RedirectResponse
    {
        if ($request->user()?->hasVerifiedEmail()) {
            return redirect()->route('portal.dashboard');
        }

        return Inertia::render('Auth/EmailBestaetigen', [
            'email' => $request->user()?->email,
            'status' => session('status'),
        ]);
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('portal.dashboard');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return redirect()->route('portal.dashboard')
            ->with('success', 'Ihre E-Mail-Adresse ist bestätigt.');
    }

    public function resend(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('portal.dashboard');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'Wir haben Ihnen einen neuen Bestätigungslink geschickt.');
    }
}
