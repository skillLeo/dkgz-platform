<?php

namespace App\Http\Controllers;

use App\Models\RequestOffer;
use App\Support\Formatter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The invitee's side of a hand-sent offer.
 *
 * Everything here runs for somebody with no account, so the page shows the job
 * and nothing about the customer. Contact data appears only after they have
 * registered and the assignment exists — the same boundary every other partner
 * is held to, enforced by simply never sending it here.
 */
class RequestOfferController extends Controller
{
    /** Where the token is kept between accepting and finishing registration. */
    public const SESSION_KEY = 'dkgz.request_offer_token';

    public function show(string $token): Response|RedirectResponse
    {
        $offer = RequestOffer::with('serviceRequest.serviceType')->where('token', $token)->first();

        if ($offer === null) {
            return Inertia::render('Public/AngebotUngueltig', ['reason' => 'unbekannt']);
        }

        // Stamped once, so the admin trail shows when they first looked rather
        // than when they last refreshed.
        if ($offer->viewed_at === null) {
            $offer->update(['viewed_at' => now()]);
        }

        $request = $offer->serviceRequest;

        return Inertia::render('Public/Angebot', [
            'offer' => [
                'token' => $offer->token,
                'name' => $offer->name,
                'email' => $offer->email,
                'message' => $offer->message,
                'expires_at_label' => Formatter::date($offer->expires_at),
                'hold_hours' => RequestOffer::HOLD_HOURS,
                'state' => $this->stateOf($offer),
            ],
            'request' => [
                'reference' => $request->reference,
                'service_type' => $request->serviceType?->name_de,
                'location' => $request->locationLabel(),
                'vehicle' => $request->vehicleLabel(),
                'description' => $request->description,
                'urgency' => $request->urgency,
                'created_at_label' => Formatter::dateTime($request->created_at),
                'dkgz_fee_label' => Formatter::money($request->serviceType?->dkgz_fee_cents ?? 0),
            ],
        ]);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $offer = RequestOffer::with('serviceRequest')->where('token', $token)->firstOrFail();

        if ($this->stateOf($offer) !== 'offen') {
            return back()->withErrors(['offer' => 'Dieser Auftrag steht nicht mehr zur Verfügung.']);
        }

        $offer->update([
            'accepted_at' => now(),
            'hold_until' => now()->addHours(RequestOffer::HOLD_HOURS),
        ]);

        // Carried in the session so the registration form can finish the job
        // without asking them to paste a token they never saw.
        $request->session()->put(self::SESSION_KEY, $offer->token);

        return redirect()->route('register')->with(
            'success',
            'Der Auftrag ist '.RequestOffer::HOLD_HOURS.' Stunden für Sie reserviert. '
            .'Schließen Sie jetzt Ihre Registrierung ab.'
        );
    }

    public function decline(Request $request, string $token): RedirectResponse
    {
        $offer = RequestOffer::where('token', $token)->firstOrFail();

        if ($this->stateOf($offer) !== 'offen') {
            return back()->withErrors(['offer' => 'Dieser Auftrag steht nicht mehr zur Verfügung.']);
        }

        $data = $request->validate([
            'decline_reason' => ['nullable', 'string', 'max:200'],
        ]);

        $offer->update([
            'declined_at' => now(),
            'decline_reason' => $data['decline_reason'] ?? null,
        ]);

        return back()->with('success', 'Vielen Dank für Ihre Rückmeldung.');
    }

    /** One word for what the invitee can still do, decided in one place. */
    private function stateOf(RequestOffer $offer): string
    {
        return match (true) {
            $offer->assessor_id !== null => 'uebernommen',
            $offer->declined_at !== null => 'abgelehnt',
            $offer->holdsRequest() => 'reserviert',
            $offer->accepted_at !== null => 'frist_abgelaufen',
            $offer->isExpired() => 'abgelaufen',
            ! $offer->serviceRequest->isOpen() => 'vergeben',
            default => 'offen',
        };
    }
}
