<?php

use App\Models\Assessor;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

function settingsPartner(): User
{
    $user = User::factory()->create(['is_active' => true, 'phone' => '+49 211 4470012']);
    $user->assignRole('assessor');

    Assessor::factory()->create([
        'user_id' => $user->id,
        'approval_status' => Assessor::STATUS_APPROVED,
    ]);

    return $user->fresh('assessor');
}

describe('the Bankverbindung panel', function () {
    it('stores an IBAN with the spaces stripped and upper-cased', function () {
        $user = settingsPartner();

        $this->actingAs($user)->post('/portal/einstellungen/bankverbindung', [
            'bank_account_holder' => 'Kfz-Sachverständigenbüro Reinhardt',
            'bank_iban' => 'de89 3704 0044 0532 0130 00',
            'bank_bic' => 'cobadeffxxx',
        ])->assertSessionHasNoErrors();

        expect($user->assessor->fresh())
            ->bank_iban->toBe('DE89370400440532013000')
            ->bank_bic->toBe('COBADEFFXXX');
    });

    it('rejects an IBAN whose check digits do not add up', function () {
        $user = settingsPartner();

        $this->actingAs($user)->post('/portal/einstellungen/bankverbindung', [
            'bank_account_holder' => 'Martina Reinhardt',
            'bank_iban' => 'DE89370400440532013001',
        ])->assertSessionHasErrors('bank_iban');

        expect($user->assessor->fresh()->bank_iban)->toBeNull();
    });

    it('rejects a BIC of the wrong length', function () {
        $user = settingsPartner();

        $this->actingAs($user)->post('/portal/einstellungen/bankverbindung', [
            'bank_account_holder' => 'Martina Reinhardt',
            'bank_iban' => 'DE89370400440532013000',
            'bank_bic' => 'COBADEF',
        ])->assertSessionHasErrors('bank_bic');
    });
});

describe('the Benachrichtigungen panel', function () {
    it('stores every switch', function () {
        $user = settingsPartner();

        $this->actingAs($user)->post('/portal/einstellungen/benachrichtigungen', [
            'notify_new_request' => false,
            'notify_deadline_reminder' => true,
            'notify_commission_statement' => false,
        ])->assertSessionHasNoErrors();

        expect($user->assessor->fresh())
            ->notify_new_request->toBeFalse()
            ->notify_deadline_reminder->toBeTrue()
            ->notify_commission_statement->toBeFalse();
    });

    it('defaults every switch on for a new partner', function () {
        expect(settingsPartner()->assessor)
            ->notify_new_request->toBeTrue()
            ->notify_deadline_reminder->toBeTrue()
            ->notify_commission_statement->toBeTrue();
    });
});

describe('the Firma panel', function () {
    it('saves the company name and phone', function () {
        $user = settingsPartner();

        $this->actingAs($user)->post('/portal/einstellungen/firma', [
            'company_name' => 'Kfz-Sachverständigenbüro Reinhardt',
            'vat_id' => 'DE123456788',
            'phone' => '+49 211 4470013',
        ])->assertSessionHasNoErrors();

        expect($user->fresh()->phone)->toBe('+49 211 4470013')
            ->and($user->assessor->fresh()->company_name)->toBe('Kfz-Sachverständigenbüro Reinhardt');
    });

    it('rejects a malformed VAT id', function () {
        $user = settingsPartner();

        $this->actingAs($user)->post('/portal/einstellungen/firma', [
            'company_name' => 'Büro Reinhardt',
            'vat_id' => 'DE1',
            'phone' => '+49 211 4470012',
        ])->assertSessionHasErrors('vat_id');
    });
});
