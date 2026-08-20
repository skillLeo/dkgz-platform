<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Assessor;
use App\Models\Invitation;
use App\Models\ServiceType;
use App\Models\User;
use App\Rules\ExistingPostalCode;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    public function show(string $token): Response|RedirectResponse
    {
        $invitation = Invitation::where('token', $token)->first();

        if ($invitation === null || ! $invitation->isPending()) {
            return Inertia::render('Auth/EinladungUngueltig', [
                'reason' => $invitation === null
                    ? 'Diese Einladung existiert nicht.'
                    : ($invitation->isAccepted()
                        ? 'Diese Einladung wurde bereits angenommen.'
                        : 'Diese Einladung ist abgelaufen.'),
                'sentAt' => $invitation?->created_at,
                'validDays' => $invitation === null
                    ? 14
                    : (int) $invitation->created_at->diffInDays($invitation->expires_at),
            ]);
        }

        return Inertia::render('Auth/Einladung', [
            'invitation' => [
                'email' => $invitation->email,
                'message' => $invitation->message,
                'expires_at' => $invitation->expires_at,
                'invited_by' => $invitation->invitedBy?->fullName(),
            ],
            'token' => $token,
            'commissionRate' => Settings::commissionRate(),
            'serviceTypes' => ServiceType::active()->ordered()->get(['id', 'name_de']),
        ]);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();

        abort_unless($invitation->isPending(), 410, 'Diese Einladung ist nicht mehr gültig.');

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:40'],
            'password' => ['required', 'confirmed', PasswordResetController::passwordRules()],
            'company_name' => ['required', 'string', 'max:180'],
            'street' => ['required', 'string', 'max:180'],
            'house_number' => ['required', 'string', 'max:20'],
            'postal_code' => ['required', new ExistingPostalCode],
            'city' => ['required', 'string', 'max:120'],
            'terms' => ['accepted'],
            'privacy' => ['accepted'],
        ], [], [
            'first_name' => 'der Vorname',
            'last_name' => 'der Nachname',
            'phone' => 'die Telefonnummer',
            'password' => 'das Passwort',
            'company_name' => 'der Firmenname',
            'postal_code' => 'die Postleitzahl',
        ]);

        $user = DB::transaction(function () use ($data, $invitation) {
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'name' => trim($data['first_name'].' '.$data['last_name']),
                'email' => $invitation->email,
                'password' => Hash::make($data['password']),
                'phone' => $data['phone'],
                'locale' => 'de',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            $user->assignRole($invitation->role);

            if ($invitation->role === 'assessor') {
                Assessor::create([
                    'user_id' => $user->id,
                    'company_name' => $data['company_name'],
                    'legal_form' => 'einzelunternehmen',
                    'street' => $data['street'],
                    'house_number' => $data['house_number'],
                    'postal_code' => $data['postal_code'],
                    'city' => $data['city'],
                    'country' => 'DE',
                    // An invited partner is vouched for, so they start approved
                    // and only need to complete their area and services.
                    'approval_status' => Assessor::STATUS_APPROVED,
                    'approved_at' => now(),
                    'approved_by' => $invitation->invited_by,
                    'is_available' => false,
                ]);
            }

            $invitation->update(['accepted_at' => now()]);

            return $user;
        });

        auth()->login($user);

        return redirect()->route('portal.service-areas')
            ->with('success', 'Ihr Zugang ist eingerichtet. Bitte hinterlegen Sie jetzt Ihr Einsatzgebiet.');
    }
}
