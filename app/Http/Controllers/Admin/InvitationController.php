<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendInvitationJob;
use App\Models\Invitation;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Invitation::class);

        return Inertia::render('Admin/Einladungen', [
            'invitations' => Invitation::with('invitedBy')->latest()->paginate(20)->withQueryString()
                ->through(fn (Invitation $i) => [
                    'id' => $i->id,
                    'email' => $i->email,
                    'role' => $i->role,
                    'status' => $i->isAccepted() ? 'accepted' : ($i->isExpired() ? 'expired' : 'pending'),
                    'status_label' => $i->statusLabel(),
                    'invited_by' => $i->invitedBy?->fullName(),
                    'expires_at' => $i->expires_at,
                    'accepted_at' => $i->accepted_at,
                    'created_at' => $i->created_at,
                ]),
            'can' => [
                'create' => $request->user()->can('create', Invitation::class),
            ],
            'enabled' => Settings::bool('features.invitations', true),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Invitation::class);

        abort_unless(Settings::bool('features.invitations', true), 403, 'Einladungen sind derzeit deaktiviert.');

        $data = $request->validate([
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email'),
                Rule::unique('invitations', 'email')->whereNull('accepted_at'),
            ],
            'role' => ['required', Rule::in(['assessor'])],
            'message' => ['nullable', 'string', 'max:1000'],
        ], [
            'email.unique' => 'Für diese Adresse besteht bereits ein Zugang oder eine offene Einladung.',
        ], [
            'email' => 'die E-Mail-Adresse',
            'message' => 'die Nachricht',
        ]);

        $invitation = Invitation::create([
            'email' => $data['email'],
            'role' => $data['role'],
            'token' => Invitation::generateToken(),
            'invited_by' => $request->user()->id,
            'message' => $data['message'] ?? null,
            'expires_at' => now()->addDays(14),
        ]);

        SendInvitationJob::dispatch($invitation->id);

        return back()->with('success', "Die Einladung an {$invitation->email} wurde versendet.");
    }

    public function resend(Request $request, Invitation $invitation): RedirectResponse
    {
        $this->authorize('resend', $invitation);

        // Resending issues a fresh token and clock, so an old link stops working.
        $invitation->update([
            'token' => Invitation::generateToken(),
            'expires_at' => now()->addDays(14),
        ]);

        SendInvitationJob::dispatch($invitation->id);

        return back()->with('success', 'Die Einladung wurde erneut versendet.');
    }

    public function revoke(Request $request, Invitation $invitation): RedirectResponse
    {
        $this->authorize('revoke', $invitation);

        $invitation->delete();

        return back()->with('success', 'Die Einladung wurde zurückgezogen.');
    }
}
