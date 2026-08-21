<?php

namespace App\Actions;

use App\Jobs\SendRequestOfferJob;
use App\Models\RequestOffer;
use App\Models\ServiceRequest;
use App\Models\User;
use RuntimeException;

/**
 * Offers one request to an assessor who is not on the platform yet.
 *
 * Refuses on a request that is already placed, and refuses a second open offer
 * to the same address — an operator pressing send twice should not put two
 * live links to the same request in one inbox.
 */
class OfferRequestExternallyAction
{
    public function execute(
        ServiceRequest $request,
        string $email,
        ?string $name,
        ?string $message,
        User $invitedBy,
    ): RequestOffer {
        $email = mb_strtolower(trim($email));

        if (! $request->isOpen()) {
            throw new RuntimeException('Diese Anfrage ist bereits vergeben oder abgeschlossen.');
        }

        if (User::where('email', $email)->exists()) {
            throw new RuntimeException(
                'Für diese Adresse besteht bereits ein Zugang. Bitte vermitteln Sie die Anfrage über die Zuordnung.'
            );
        }

        $open = RequestOffer::where('service_request_id', $request->id)
            ->where('email', $email)
            ->whereNull('declined_at')
            ->where('expires_at', '>', now())
            ->first();

        if ($open !== null) {
            throw new RuntimeException('An diese Adresse wurde für diese Anfrage bereits eine Einladung gesendet.');
        }

        $offer = RequestOffer::create([
            'service_request_id' => $request->id,
            'email' => $email,
            'name' => blank($name) ? null : trim($name),
            'token' => RequestOffer::generateToken(),
            'invited_by' => $invitedBy->id,
            'message' => blank($message) ? null : trim($message),
            'sent_at' => now(),
            'expires_at' => now()->addDays(RequestOffer::VALID_DAYS),
        ]);

        SendRequestOfferJob::dispatch($offer->id);

        return $offer;
    }
}
