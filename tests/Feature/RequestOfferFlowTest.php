<?php

use App\Actions\AcceptAssignmentAction;
use App\Actions\OfferRequestExternallyAction;
use App\Exceptions\RequestAlreadyAssignedException;
use App\Models\Assessor;
use App\Models\RequestOffer;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use App\Models\User;

function offerFixture(): array
{
    $type = ServiceType::factory()->create(['dkgz_fee_cents' => 7_900]);

    $request = ServiceRequest::factory()->create([
        'service_type_id' => $type->id,
        'status' => ServiceRequest::STATUS_NEW,
    ]);

    $admin = User::factory()->create();

    return [$request, $admin];
}

it('offers a request to somebody with no account', function () {
    [$request, $admin] = offerFixture();

    $offer = app(OfferRequestExternallyAction::class)
        ->execute($request, 'Neu@Beispiel.de', 'Büro Nord', 'Kurze Nachricht', $admin);

    expect($offer->email)->toBe('neu@beispiel.de')
        ->and($offer->token)->toHaveLength(64)
        ->and($offer->isOpen())->toBeTrue();
});

it('refuses a second open offer to the same address', function () {
    [$request, $admin] = offerFixture();

    app(OfferRequestExternallyAction::class)->execute($request, 'neu@beispiel.de', null, null, $admin);

    expect(fn () => app(OfferRequestExternallyAction::class)
        ->execute($request, 'neu@beispiel.de', null, null, $admin))
        ->toThrow(RuntimeException::class);
});

it('refuses to offer a request to somebody who already has an account', function () {
    [$request, $admin] = offerFixture();
    User::factory()->create(['email' => 'partner@beispiel.de']);

    expect(fn () => app(OfferRequestExternallyAction::class)
        ->execute($request, 'partner@beispiel.de', null, null, $admin))
        ->toThrow(RuntimeException::class);
});

it('shows the offer without any customer contact data', function () {
    [$request, $admin] = offerFixture();
    $request->update(['customer_name' => 'Martina Reinhardt', 'customer_phone' => '+49 211 3300124']);

    $offer = app(OfferRequestExternallyAction::class)->execute($request, 'neu@beispiel.de', null, null, $admin);

    $body = $this->get(route('offer.show', $offer->token))->getContent();

    expect($body)->not->toContain('Martina Reinhardt')
        ->and($body)->not->toContain('3300124')
        ->and($body)->toContain($request->reference);
});

it('holds the request once accepted, so nobody else can take it', function () {
    [$request, $admin] = offerFixture();
    $request->update(['status' => ServiceRequest::STATUS_MATCHED]);

    $offer = app(OfferRequestExternallyAction::class)->execute($request, 'neu@beispiel.de', null, null, $admin);

    $this->post(route('offer.accept', $offer->token))->assertRedirect(route('register'));

    $rival = Assessor::factory()->create();

    expect(fn () => app(AcceptAssignmentAction::class)->execute($request->fresh(), $rival))
        ->toThrow(RequestAlreadyAssignedException::class);
});

it('lets the invitee redeem their own hold', function () {
    [$request, $admin] = offerFixture();

    $offer = app(OfferRequestExternallyAction::class)->execute($request, 'neu@beispiel.de', null, null, $admin);
    $offer->update(['accepted_at' => now(), 'hold_until' => now()->addHours(48)]);

    $theirs = Assessor::factory()->create();

    $assignment = app(AcceptAssignmentAction::class)
        ->execute($request->fresh(), $theirs, $offer->fresh());

    expect($assignment->assessor_id)->toBe($theirs->id)
        ->and($request->fresh()->status)->toBe(ServiceRequest::STATUS_ASSIGNED);
});

it('releases the request when the hold runs out', function () {
    [$request, $admin] = offerFixture();
    $request->update(['status' => ServiceRequest::STATUS_MATCHED]);

    $offer = app(OfferRequestExternallyAction::class)->execute($request, 'neu@beispiel.de', null, null, $admin);
    $offer->update(['accepted_at' => now()->subDays(3), 'hold_until' => now()->subDay()]);

    $rival = Assessor::factory()->create();

    $assignment = app(AcceptAssignmentAction::class)->execute($request->fresh(), $rival);

    expect($assignment->assessor_id)->toBe($rival->id);
});
