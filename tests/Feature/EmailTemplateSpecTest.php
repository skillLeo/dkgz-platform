<?php

use App\Models\EmailTemplate;
use Database\Seeders\EmailTemplateSeeder;

/**
 * The technical limits DKGZ E-Mail-Vorlagen sets. A subject over 60 characters
 * is cut off in most clients; a preheader under 40 leaves the client free to
 * pad the preview with whatever body text follows, which is how a careful mail
 * ends up previewing as "Guten Tag Herr, wir haben".
 */
beforeEach(fn () => $this->seed(EmailTemplateSeeder::class));

it('keeps every subject within 60 characters', function () {
    $tooLong = EmailTemplate::all()
        ->filter(fn (EmailTemplate $t) => mb_strlen((string) $t->subject_de) > 60)
        ->map(fn (EmailTemplate $t) => "{$t->key} (".mb_strlen((string) $t->subject_de).')')
        ->values()
        ->all();

    expect($tooLong)->toBe([]);
});

it('keeps every preheader between 40 and 90 characters', function () {
    $outside = EmailTemplate::all()
        ->filter(function (EmailTemplate $t) {
            $length = mb_strlen((string) $t->preheader_de);

            return $length < 40 || $length > 90;
        })
        ->map(fn (EmailTemplate $t) => "{$t->key} (".mb_strlen((string) $t->preheader_de).')')
        ->values()
        ->all();

    expect($outside)->toBe([]);
});

it('addresses the reader formally in every template', function () {
    $informal = EmailTemplate::all()
        ->filter(fn (EmailTemplate $t) => preg_match(
            '/\b(du|dich|dir|dein|deine|deinem|deiner)\b/i',
            (string) $t->body_de.' '.(string) $t->subject_de
        ))
        ->pluck('key')
        ->all();

    expect($informal)->toBe([]);
});
