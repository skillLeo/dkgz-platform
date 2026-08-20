<?php

use App\Mail\TemplateMail;
use App\Models\Setting;
use App\Support\MailDomainCheck;
use App\Support\Settings;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\SettingsSeeder;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(EmailTemplateSeeder::class);
});

it('reads the sending domain out of the from address', function () {
    expect(MailDomainCheck::domainOf('no-reply@dkgz.de'))->toBe('dkgz.de')
        ->and(MailDomainCheck::domainOf('kaputt'))->toBeNull()
        ->and(MailDomainCheck::domainOf(null))->toBeNull();
});

it('says so plainly when there is no sender configured', function () {
    $result = MailDomainCheck::run('');

    expect($result['domain'])->toBeNull()
        ->and($result['available'])->toBeFalse();
});

it('names SPF, DKIM and DMARC for a real domain', function () {
    $result = MailDomainCheck::run('info@dkgz.de');

    if (! $result['available']) {
        $this->markTestSkipped('Auf diesem System sind keine DNS-Abfragen möglich.');
    }

    expect(collect($result['records'])->pluck('name')->all())->toBe(['SPF', 'DMARC', 'DKIM']);

    foreach ($result['records'] as $record) {
        expect($record['value'])->not->toBeEmpty()
            ->and($record['explanation'])->not->toBeEmpty()
            ->and($record['state'])->toBeIn(['ok', 'missing', 'problem']);
    }
});

it('puts List-Unsubscribe only on the commission statement', function () {
    Setting::where('key', 'email.unsubscribe_address')->update(['value' => 'abmeldung@dkgz.de']);
    Settings::flush();

    $bulk = (new TemplateMail('provisionsabrechnung', []))->headers();
    $transactional = (new TemplateMail('auftrag-bestaetigt', []))->headers();

    expect($bulk->text)->toHaveKey('List-Unsubscribe')
        ->and($bulk->text)->toHaveKey('List-Unsubscribe-Post')
        ->and($transactional->text)->not->toHaveKey('List-Unsubscribe');
});

it('sets the bounce address as Return-Path when one is configured', function () {
    Setting::where('key', 'email.bounce_address')->update(['value' => 'bounce@dkgz.de']);
    Settings::flush();

    expect((new TemplateMail('auftrag-bestaetigt', []))->headers()->text)
        ->toHaveKey('Return-Path', '<bounce@dkgz.de>');
});
