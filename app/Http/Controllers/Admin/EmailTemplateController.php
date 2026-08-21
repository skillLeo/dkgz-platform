<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TemplateMail;
use App\Models\EmailTemplate;
use App\Support\Mailer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class EmailTemplateController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', EmailTemplate::class);

        return Inertia::render('Admin/EmailVorlagen', [
            'templates' => EmailTemplate::orderBy('id')->get()
                ->map(fn (EmailTemplate $t) => [
                    'key' => $t->key,
                    'name' => $t->name_de,
                    'subject' => $t->subject_de,
                    'is_active' => $t->is_active,
                    'updated_at' => $t->updated_at,
                ]),
        ]);
    }

    public function edit(Request $request, EmailTemplate $emailTemplate): Response
    {
        $this->authorize('view', $emailTemplate);

        return Inertia::render('Admin/EmailVorlage', [
            'template' => [
                'key' => $emailTemplate->key,
                'name_de' => $emailTemplate->name_de,
                'subject_de' => $emailTemplate->subject_de,
                'preheader_de' => $emailTemplate->preheader_de,
                'body_html' => $emailTemplate->body_html,
                'note_de' => $emailTemplate->note_de,
                'is_active' => $emailTemplate->is_active,
            ],
            // The variable reference panel: what this template may use.
            'variables' => $emailTemplate->available_variables ?? [],
            'canEdit' => $request->user()->can('update', $emailTemplate),
            'canTest' => $request->user()->can('sendTest', EmailTemplate::class),
            'previewUrl' => route('admin.emails.preview', $emailTemplate),
        ]);
    }

    public function update(Request $request, EmailTemplate $emailTemplate): RedirectResponse
    {
        $this->authorize('update', $emailTemplate);

        $data = $request->validate([
            'name_de' => ['required', 'string', 'max:180'],
            // 60 characters is the point most clients truncate a subject line.
            'subject_de' => ['required', 'string', 'max:180'],
            'preheader_de' => ['nullable', 'string', 'max:200'],
            'body_html' => ['required', 'string', 'max:60000'],
            'note_de' => ['nullable', 'string', 'max:600'],
            'is_active' => ['boolean'],
        ], [], [
            'name_de' => 'der Name',
            'subject_de' => 'der Betreff',
            'preheader_de' => 'der Vorschautext',
            'body_html' => 'der Inhalt',
            'note_de' => 'der Schlusshinweis',
        ]);

        $emailTemplate->update($data);

        return back()->with('success', 'Die Vorlage wurde gespeichert.');
    }

    /** Renders the template with sample values, in the real mail layout. */
    public function preview(Request $request, EmailTemplate $emailTemplate): HttpResponse
    {
        $this->authorize('preview', $emailTemplate);

        $mailable = new TemplateMail($emailTemplate->key, $this->sampleData($emailTemplate));

        return response($mailable->render())->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function test(Request $request, EmailTemplate $emailTemplate): RedirectResponse
    {
        $this->authorize('sendTest', EmailTemplate::class);

        $data = $request->validate(
            ['email' => ['required', 'string', 'email']],
            [],
            ['email' => 'die E-Mail-Adresse']
        );

        Mailer::send($data['email'], $emailTemplate->key, $this->sampleData($emailTemplate));

        return back()->with('success', "Die Vorlage wurde an {$data['email']} versendet.");
    }

    /**
     * Plausible German sample values for every placeholder the template
     * declares, so a preview reads like a real message rather than a form.
     *
     * @return array<string, mixed>
     */
    private function sampleData(EmailTemplate $template): array
    {
        $samples = [
            'anrede' => 'Frau',
            'nachname' => 'Reinhardt',
            'kunde' => 'Martina Reinhardt',
            'sv_nachname' => 'Ohlsen',
            'sv_name' => 'Jan Ohlsen',
            'sv_firma' => 'Kfz-Sachverständigenbüro Ohlsen',
            'sv_telefon' => '+49 421 5580120',
            'sv_email' => 'j.ohlsen@sv-nordwest.de',
            'referenz' => 'DKGZ26084817',
            'partner_id' => 'DKGZ-SV-0163',
            'gutachtenart' => 'Unfallgutachten',
            'plz' => '40589',
            'ort' => 'Düsseldorf',
            'fahrzeug' => 'VW Passat B8 · 2019',
            'kennzeichen' => 'D-AB 1234',
            'schadenart' => 'Heckschaden, fahrbereit',
            'eingang_datum' => '17.08.2026, 08:42 Uhr',
            'annahme_zeit' => '17.08.2026, 09:07 Uhr',
            'abschluss_zeit' => '19.08.2026, 16:44 Uhr',
            'abschluss_datum' => '19.08.2026',
            'honorar' => '1.640,00 €',
            'betrag' => '246,00 €',
            'rechnungsnummer' => 'DKGZ-RE-2026-0042',
            'zeitraum' => 'August 2026',
            'faellig_am' => '02.09.2026',
            'provisionssatz' => '15 %',
            'bewertung' => '9',
            'kategorie' => 'Terminfindung',
            'anmerkung' => 'Alles hat reibungslos funktioniert.',
            'grund' => 'Der eingereichte Qualifikationsnachweis war nicht lesbar.',
            'firma' => 'Kfz-Sachverständigenbüro Ohlsen',
            'admin_nachricht' => 'Wir würden Sie gern als Partner für den Raum Bremen und Oldenburg aufnehmen.',
            'admin_name' => 'K. Ahrens',
            'admin_datum' => '14.08.2026',
            'ablauf_datum' => '28.08.2026',
            'gueltigkeit_tage' => '14',
            'gueltigkeit_minuten' => '60',
            'name' => 'Martina Reinhardt',
            'email' => 'm.reinhardt@web.de',
            'telefon' => '+49 179 4480169',
            'betreff' => 'Frage zu meinem Vorgang',
            'nachricht' => 'Guten Tag, ich hätte eine Rückfrage zum Termin.',
            'zeitpunkt' => '20.08.2026, 10:15 Uhr',
            'server' => 'smtp.beispiel.de',
            'datei_gutachten' => 'Gutachten_DKGZ26084817.pdf · 2,4 MB',
            'datei_rechnung' => 'Rechnung_04817.pdf · 318 KB',
            'bewertung_token' => 'a41f9c',
            'einladung_token' => '7f2a91c4',
        ];

        return array_merge($samples, [
            'eyebrow' => $template->name_de,
            'headline' => $template->name_de,
            'salutation' => 'Guten Tag Frau Reinhardt,',
            'dataTitle' => 'Beispieldaten',
            'rows' => [
                ['k' => 'Referenz', 'v' => 'DKGZ26084817', 'mono' => true],
                ['k' => 'Art des Gutachtens', 'v' => 'Unfallgutachten'],
                ['k' => 'Standort', 'v' => 'Düsseldorf · 40589'],
                ['k' => 'Fahrzeug', 'v' => 'VW Passat B8 · 2019'],
            ],
            'cta' => 'Vorgang ansehen',
            'cta_url' => url('/'),
            'note' => 'Dies ist eine Vorschau mit Beispieldaten. Es wurde nichts versendet.',
        ]);
    }
}
