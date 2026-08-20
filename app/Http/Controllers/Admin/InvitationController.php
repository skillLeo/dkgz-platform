<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendInvitationJob;
use App\Models\Invitation;
use App\Models\User;
use App\Support\InvitationImport;
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

    /**
     * Reads an uploaded list and shows what would happen, without sending.
     *
     * A bulk invite cannot be recalled, so the operator confirms a list they
     * can actually see rather than a file they uploaded blind.
     */
    public function preview(Request $request): RedirectResponse
    {
        $this->authorize('create', Invitation::class);

        $request->validate(
            ['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']],
            [],
            ['file' => 'die Datei']
        );

        $preview = InvitationImport::preview($request->file('file'));

        return back()->with('invitationPreview', $preview);
    }

    /**
     * Sends the invitations the operator confirmed.
     *
     * Addresses are re-checked here rather than trusted from the preview: the
     * two requests are minutes apart and somebody may have registered in
     * between. Sends are staggered because a few hundred at once is exactly
     * what makes a mail host start refusing them.
     */
    public function importSend(Request $request): RedirectResponse
    {
        $this->authorize('create', Invitation::class);

        abort_unless(Settings::bool('features.invitations', true), 403, 'Einladungen sind derzeit deaktiviert.');

        $data = $request->validate([
            'emails' => ['required', 'array', 'min:1', 'max:'.InvitationImport::MAX_ROWS],
            'emails.*' => ['required', 'email'],
            'message' => ['nullable', 'string', 'max:1000'],
        ], [], [
            'emails' => 'die Adressen',
            'message' => 'die Nachricht',
        ]);

        $addresses = collect($data['emails'])
            ->map(fn (string $email) => mb_strtolower(trim($email)))
            ->unique()
            ->values();

        $taken = User::whereIn('email', $addresses)->pluck('email')
            ->merge(Invitation::whereNull('accepted_at')->whereIn('email', $addresses)->pluck('email'))
            ->map(fn (string $e) => mb_strtolower($e))
            ->all();

        $sent = 0;
        $skipped = 0;

        foreach ($addresses as $position => $email) {
            if (in_array($email, $taken, true)) {
                $skipped++;

                continue;
            }

            $invitation = Invitation::create([
                'email' => $email,
                'role' => 'assessor',
                'token' => Invitation::generateToken(),
                'invited_by' => $request->user()->id,
                'message' => $data['message'] ?? null,
                'expires_at' => now()->addDays(14),
            ]);

            // Roughly twenty a minute: fast enough to finish a large list
            // within the hour, gentle enough that the host does not throttle.
            SendInvitationJob::dispatch($invitation->id)
                ->delay(now()->addSeconds((int) floor($position * 3)));

            $sent++;
        }

        activity()->withProperties(['sent' => $sent, 'skipped' => $skipped])
            ->log("Sammeleinladung: {$sent} versendet, {$skipped} übersprungen.");

        return back()->with(
            'success',
            $skipped === 0
                ? "{$sent} Einladung(en) wurden in die Warteschlange gestellt."
                : "{$sent} Einladung(en) versendet, {$skipped} übersprungen (Zugang oder Einladung besteht bereits)."
        );
    }
}
