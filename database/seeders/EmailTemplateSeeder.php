<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

/**
 * The 18 transactional templates. Bodies are the inner content only — the
 * navy header band, the footer with the legal line, and the 600px table shell
 * come from the `emails.layout` Blade view, exactly as the Baukasten section
 * of "DKGZ E-Mail-Vorlagen.dc.html" specifies ("Änderungen erfolgen am
 * Baustein, nie an einer einzelnen Vorlage").
 */
class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->templates() as $template) {
            EmailTemplate::updateOrCreate(
                ['key' => $template['key']],
                $template + ['is_active' => true]
            );
        }
    }

    /** @return list<array<string, mixed>> */
    private function templates(): array
    {
        return [
            [
                'key' => 'anfrage-eingegangen',
                'name_de' => 'Anfrage eingegangen',
                'subject_de' => 'Ihre Anfrage ist eingegangen — {{ referenz }}',
                'preheader_de' => 'Wir vermitteln Ihre Anfrage jetzt an geeignete Sachverständige in Ihrer Region.',
                'available_variables' => ['anrede', 'nachname', 'referenz', 'gutachtenart', 'plz', 'ort', 'fahrzeug', 'kennzeichen', 'eingang_datum', 'cta_url'],
                'body_html' => '<p>Guten Tag {{ anrede }} {{ nachname }},</p><p>vielen Dank für Ihre Anfrage. Wir leiten sie jetzt an geeignete Sachverständige weiter, deren Einsatzgebiet den Standort Ihres Fahrzeugs abdeckt.</p><p>Sobald ein Sachverständiger den Auftrag annimmt, erhält er Ihre Kontaktdaten und meldet sich direkt bei Ihnen — in der Regel innerhalb eines Werktages.</p>',
            ],
            [
                'key' => 'neue-anfrage-im-gebiet',
                'name_de' => 'Neue Anfrage in Ihrem Gebiet',
                'subject_de' => 'Neue Anfrage in Ihrem Gebiet — {{ gutachtenart }}, {{ plz }}',
                'preheader_de' => 'Annahme bis {{ frist }}. Der erste verfügbare Partner übernimmt.',
                'available_variables' => ['sv_nachname', 'referenz', 'gutachtenart', 'plz', 'ort', 'fahrzeug', 'kennzeichen', 'schadenart', 'dringlichkeit', 'wunschtermin', 'dkgz_gebuehr', 'eingang_datum', 'cta_url'],
                'body_html' => '<p>Guten Tag {{ sv_nachname }},</p><p>für Ihr Einsatzgebiet liegt eine neue Anfrage vor: <strong>{{ gutachtenart }}</strong> in {{ plz }} {{ ort }}, {{ fahrzeug }}. Alle Angaben finden Sie unten.</p><p>Die Anfrage ging gleichzeitig an weitere Partner im Gebiet — der erste, der annimmt, übernimmt den Auftrag. Eine Ablehnung wirkt sich nicht auf die weitere Verteilung aus.</p>',
                'note_de' => 'Die Kontaktdaten der anfragenden Person werden erst nach der Annahme freigegeben.',
            ],
            [
                'key' => 'auftrag-vergeben',
                'name_de' => 'Auftrag vergeben',
                'subject_de' => 'Anfrage {{ referenz }} wurde bereits vergeben',
                'preheader_de' => 'Ein anderer Partner hat den Auftrag übernommen.',
                'available_variables' => ['sv_nachname', 'referenz', 'gutachtenart', 'plz', 'ort', 'cta_url'],
                'body_html' => '<p>Guten Tag {{ sv_nachname }},</p><p>die Anfrage {{ referenz }} wurde inzwischen von einem anderen Sachverständigen übernommen. Sie müssen nichts weiter tun.</p><p>Ihre Verfügbarkeit bleibt unverändert. Die nächste passende Anfrage in Ihrem Gebiet erreicht Sie wie gewohnt.</p>',
            ],
            [
                // The customer used to receive 'auftrag-vergeben', which is
                // written for the partners who lost the race — they read that
                // their request "wurde inzwischen von einem anderen
                // Sachverständigen übernommen" and that they need do nothing.
                'key' => 'sachverstaendiger-steht-fest',
                'name_de' => 'Sachverständiger steht fest (Kunde)',
                'subject_de' => 'Ihr Sachverständiger steht fest — {{ referenz }}',
                'preheader_de' => 'Ihr Sachverständiger meldet sich in Kürze bei Ihnen.',
                'available_variables' => ['kunde', 'referenz', 'sv_firma', 'sv_name', 'sv_telefon', 'sv_email', 'gutachtenart', 'angenommen_am'],
                'body_html' => '<p>Guten Tag {{ kunde }},</p><p>für Ihre Anfrage {{ referenz }} steht der Sachverständige fest: {{ sv_firma }} übernimmt die Begutachtung.</p><p>Sie werden in Kürze direkt kontaktiert, um den Termin abzustimmen. Sie können sich aber auch selbst melden — die Kontaktdaten stehen unten.</p>',
                'note_de' => 'Der Sachverständige erbringt die Begutachtung in eigener Verantwortung und rechnet direkt mit Ihnen oder Ihrer Versicherung ab. DKGZ hat ausschließlich vermittelt.',
            ],
            [
                'key' => 'auftrag-bestaetigt',
                'name_de' => 'Auftrag bestätigt',
                'subject_de' => 'Auftrag bestätigt — {{ referenz }}',
                'preheader_de' => 'Die Kontaktdaten der anfragenden Person sind jetzt freigegeben.',
                'available_variables' => ['sv_nachname', 'referenz', 'gutachtenart', 'kunde_name', 'kunde_telefon', 'kunde_email', 'plz', 'ort', 'fahrzeug', 'cta_url'],
                'body_html' => '<p>Guten Tag {{ sv_nachname }},</p><p>Sie haben den Auftrag {{ referenz }} übernommen — {{ gutachtenart }} in {{ plz }} {{ ort }}.</p><p><strong>{{ kunde_name }}</strong><br>Telefon: {{ kunde_telefon }}<br>E-Mail: {{ kunde_email }}<br>Fahrzeug: {{ fahrzeug }}</p><p>Bitte nehmen Sie zeitnah Kontakt auf und stimmen Sie den Termin direkt ab. Sie müssen sich dafür nicht einloggen.</p>',
                'note_de' => 'Sobald der Auftrag zustande gekommen ist, bestätigen Sie das im Portal. Erst dann stellen wir die DKGZ-Gebühr in Rechnung.',
            ],
            [
                'key' => 'auftrag-abgeschlossen',
                'name_de' => 'Auftrag abgeschlossen',
                'subject_de' => 'Ihr Gutachten liegt vor — {{ referenz }}',
                'preheader_de' => 'Gutachten und Rechnung sind hinterlegt. Der Vorgang ist damit abgeschlossen.',
                'available_variables' => ['kunde', 'referenz', 'sv_firma', 'gutachtenart', 'abschluss_zeit', 'bewertung_url'],
                'body_html' => '<p>Guten Tag {{ kunde }},</p><p>der Sachverständige hat die Begutachtung zu Ihrem Vorgang {{ referenz }} abgeschlossen.</p><p>Das Gutachten und die Rechnung erhalten Sie direkt von {{ sv_firma }}. Bei Rückfragen zum Inhalt wenden Sie sich bitte ebenfalls dorthin.</p>',
                'note_de' => 'Die Rechnung stellt der Sachverständige. DKGZ ist nicht Vertragspartner der Begutachtung und stellt Ihnen keine Kosten in Rechnung.',
            ],
            [
                'key' => 'bewertungsanfrage',
                'name_de' => 'Bewertungsanfrage',
                'subject_de' => 'Wie zufrieden waren Sie? — {{ referenz }}',
                'preheader_de' => 'Eine Frage, zehn Sekunden. Ihre Bewertung wird nicht öffentlich angezeigt.',
                'available_variables' => ['anrede', 'nachname', 'referenz', 'gutachtenart', 'sv_firma', 'abschluss_datum', 'cta_url'],
                'body_html' => '<p>Guten Tag {{ anrede }} {{ nachname }},</p><p>Ihr Vorgang ist abgeschlossen. Wir möchten wissen, ob die Vermittlung für Sie funktioniert hat — die Antwort dauert weniger als zehn Sekunden.</p><p>Ihre Bewertung wird nicht öffentlich angezeigt. Sie hilft uns, den Standard im Partnernetz zu halten.</p>',
            ],
            [
                'key' => 'bewertung-erhalten',
                'name_de' => 'Bewertung erhalten',
                'subject_de' => 'Neue Bewertung: {{ bewertung }} von 10 — {{ referenz }}',
                'preheader_de' => 'Eine anfragende Person hat einen Vorgang bewertet.',
                'available_variables' => ['referenz', 'bewertung', 'sv_firma', 'kategorie', 'anmerkung', 'cta_url'],
                'body_html' => '<p>Guten Tag,</p><p>zum Vorgang {{ referenz }} liegt eine neue Bewertung vor: {{ bewertung }} von 10.</p><p>Die vollständige Rückmeldung finden Sie in der Administration.</p>',
            ],
            [
                'key' => 'registrierung-eingegangen',
                'name_de' => 'Registrierung eingegangen',
                'subject_de' => 'Ihre Registrierung ist eingegangen',
                'preheader_de' => 'Wir prüfen Ihre Nachweise und melden uns.',
                'available_variables' => ['sv_nachname', 'firma', 'cta_url'],
                'body_html' => '<p>Guten Tag {{ sv_nachname }},</p><p>vielen Dank für Ihre Registrierung als Partner der Deutschen KFZ-Gutachterzentrale. Wir prüfen Ihre Angaben und Nachweise.</p><p>Sobald Ihr Zugang freigegeben ist, erhalten Sie eine weitere E-Mail und können Anfragen aus Ihrem Einsatzgebiet annehmen.</p>',
            ],
            [
                'key' => 'neue-registrierung-zur-pruefung',
                'name_de' => 'Neue Registrierung zur Prüfung',
                'subject_de' => 'Neue Registrierung: {{ firma }}',
                'preheader_de' => 'Ein Sachverständiger wartet auf Freigabe.',
                'available_variables' => ['firma', 'sv_name', 'plz', 'ort', 'cta_url'],
                'body_html' => '<p>Guten Tag,</p><p>es liegt eine neue Registrierung zur Prüfung vor: {{ firma }} aus {{ plz }} {{ ort }}.</p><p>Bitte prüfen Sie die hinterlegten Nachweise und geben Sie den Zugang frei oder lehnen Sie ihn ab.</p>',
            ],
            [
                'key' => 'registrierung-freigegeben',
                'name_de' => 'Registrierung freigegeben',
                'subject_de' => 'Ihr Zugang ist freigegeben',
                'preheader_de' => 'Sie erhalten ab sofort Anfragen aus Ihrem Einsatzgebiet.',
                'available_variables' => ['sv_nachname', 'firma', 'cta_url'],
                'body_html' => '<p>Guten Tag {{ sv_nachname }},</p><p>Ihr Zugang zum Partnerportal ist freigegeben. Ab sofort erhalten Sie Anfragen, deren Standort in Ihrem Einsatzgebiet liegt und die zu Ihren Leistungen passen.</p><p>Bitte prüfen Sie im Portal, ob Einsatzgebiet und Leistungen vollständig hinterlegt sind.</p>',
            ],
            [
                'key' => 'registrierung-abgelehnt',
                'name_de' => 'Registrierung abgelehnt',
                'subject_de' => 'Ihre Registrierung wurde nicht freigegeben',
                'preheader_de' => 'Die Begründung und wie Sie sich erneut bewerben können.',
                'available_variables' => ['sv_nachname', 'firma', 'grund'],
                'body_html' => '<p>Guten Tag {{ sv_nachname }},</p><p>wir konnten Ihre Registrierung nicht freigeben. Begründung: {{ grund }}</p><p>Wenn Sie die genannten Punkte ergänzen können, melden Sie sich gern erneut bei uns. Wir prüfen Ihre Unterlagen dann noch einmal.</p>',
            ],
            [
                'key' => 'konto-gesperrt',
                'name_de' => 'Konto gesperrt',
                'subject_de' => 'Ihr Zugang wurde gesperrt',
                'preheader_de' => 'Der Grund der Sperre und wie Sie die Freigabe wiedererlangen.',
                'available_variables' => ['sv_nachname', 'firma', 'grund'],
                'body_html' => '<p>Guten Tag {{ sv_nachname }},</p><p>Ihr Zugang zum Partnerportal wurde gesperrt. Begründung: {{ grund }}</p><p>Sie erhalten vorerst keine weiteren Anfragen. Zur Klärung wenden Sie sich bitte an die Administration.</p>',
            ],
            [
                // For people who already work with Carspector: they are not
                // being asked to trust a stranger, so the message opens by
                // saying who we are to them rather than introducing itself.
                'key' => 'einladung-carspector',
                'name_de' => 'Einladung für bestehende Carspector-Partner',
                'subject_de' => 'Neu für Carspector-Partner: Aufträge über die DKGZ',
                'preheader_de' => 'Ihr Zugang ist vorbereitet. Die Einladung ist {{ gueltigkeit_tage }} Tage gültig.',
                'available_variables' => ['anrede', 'nachname', 'admin_nachricht', 'admin_name', 'admin_datum', 'provisionssatz', 'ablauf_datum', 'gueltigkeit_tage', 'cta_url'],
                'body_html' => '<p>Guten Tag {{ anrede }} {{ nachname }},</p><p>Sie arbeiten bereits mit Carspector zusammen — vielen Dank dafür.</p><p>Mit der Deutschen KFZ-Gutachterzentrale (DKGZ) haben wir einen zweiten Geschäftsbereich aufgebaut: eine bundesweite Vermittlung von Gutachtenaufträgen direkt an Sachverständige. Anfragen von Privatkunden und Versicherungen werden nach Postleitzahl und Leistungsart an die passenden Partner verteilt, der erste verfügbare Partner übernimmt.</p><p>Für Sie ändert sich an der bestehenden Zusammenarbeit nichts. DKGZ kommt als zusätzliche Auftragsquelle hinzu — ohne Grundgebühr, ohne Mindestabnahme, mit {{ provisionssatz }} je vermitteltem Auftrag.</p><p>Ihr Zugang ist vorbereitet. Sie müssen nur noch Einsatzgebiet und Leistungen ergänzen.</p>',
                'note_de' => 'Sie erhalten diese Einladung, weil Sie bereits Partner von Carspector sind.',
            ],
            [
                'key' => 'einladung-partnerschaft',
                'name_de' => 'Einladung zur Partnerschaft',
                'subject_de' => 'Einladung in das DKGZ-Partnernetz',
                'preheader_de' => 'Richten Sie Ihren Zugang ein. Die Einladung ist {{ gueltigkeit_tage }} Tage gültig.',
                'available_variables' => ['anrede', 'nachname', 'admin_nachricht', 'admin_name', 'admin_datum', 'provisionssatz', 'ablauf_datum', 'gueltigkeit_tage', 'cta_url'],
                'body_html' => '<p>Guten Tag {{ anrede }} {{ nachname }},</p><p>die Deutsche KFZ-Gutachterzentrale möchte Sie als Sachverständigen in ihr bundesweites Partnernetz aufnehmen. Richten Sie dazu Ihren Zugang ein und ergänzen Sie Einsatzgebiet und Leistungen.</p>',
            ],
            [
                'key' => 'anfrage-angebot',
                'name_de' => 'Anfrage an externen Sachverständigen',
                'subject_de' => 'Auftrag {{ vorgang }} in Ihrer Region',
                'preheader_de' => 'Ein vermittelter Auftrag der DKGZ. Annahme direkt möglich, Registrierung danach.',
                'available_variables' => ['nachricht', 'vorgang', 'cta_url', 'gueltigkeit_tage'],
                'body_html' => '<p>Guten Tag,</p><p>die Deutsche KFZ-Gutachterzentrale vermittelt Aufträge an geprüfte Sachverständige. Für den folgenden Auftrag suchen wir jemanden in Ihrer Region und möchten ihn Ihnen anbieten. Sie können ihn direkt annehmen; Ihre Registrierung als Partner schließen Sie danach an.</p>',
            ],
            [
                'key' => 'passwort-zuruecksetzen',
                'name_de' => 'Passwort zurücksetzen',
                'subject_de' => 'Passwort zurücksetzen',
                'preheader_de' => 'Der Link zum Zurücksetzen ist 60 Minuten lang gültig.',
                'available_variables' => ['nachname', 'cta_url', 'gueltigkeit_minuten'],
                'body_html' => '<p>Guten Tag {{ nachname }},</p><p>Sie haben angefordert, Ihr Passwort zurückzusetzen. Über die Schaltfläche unten vergeben Sie ein neues Passwort.</p><p>Der Link ist {{ gueltigkeit_minuten }} Minuten gültig. Wenn Sie die Anfrage nicht gestellt haben, ignorieren Sie diese E-Mail — Ihr Passwort bleibt dann unverändert.</p>',
            ],
            [
                'key' => 'email-bestaetigen',
                'name_de' => 'E-Mail bestätigen',
                'subject_de' => 'Bitte bestätigen Sie Ihre E-Mail-Adresse',
                'preheader_de' => 'Bestätigen Sie Ihre Adresse — ein Klick genügt, dann geht es weiter.',
                'available_variables' => ['nachname', 'cta_url'],
                'body_html' => '<p>Guten Tag {{ nachname }},</p><p>bitte bestätigen Sie Ihre E-Mail-Adresse, damit wir Ihnen Benachrichtigungen zu Anfragen und Aufträgen zustellen können.</p>',
            ],
            [
                'key' => 'provisionsabrechnung',
                'name_de' => 'Provisionsabrechnung',
                'subject_de' => 'Provisionsabrechnung {{ rechnungsnummer }}',
                'preheader_de' => 'Ihre Provisionsabrechnung des Monats liegt dieser Mail als PDF bei.',
                'available_variables' => ['sv_nachname', 'firma', 'rechnungsnummer', 'zeitraum', 'betrag', 'faellig_am', 'cta_url'],
                'body_html' => '<p>Guten Tag {{ sv_nachname }},</p><p>anbei erhalten Sie die Provisionsabrechnung {{ rechnungsnummer }} über {{ betrag }}.</p><p>Die Einzelpositionen finden Sie im angehängten PDF und jederzeit im Partnerportal unter Provisionen.</p>',
            ],
            [
                'key' => 'partner-rundmail',
                'name_de' => 'Rundmail an Partner',
                'subject_de' => '{{ betreff }}',
                'preheader_de' => 'Eine Mitteilung der DKGZ an alle Partner.',
                'available_variables' => ['betreff', 'nachricht', 'absender'],
                'body_html' => '<p>Guten Tag,</p><p>{{ nachricht }}</p>',
                'note_de' => 'Sie erhalten diese Nachricht als Partner der Deutschen KFZ-Gutachterzentrale.',
            ],
            [
                'key' => 'neue-anfrage-intern',
                'name_de' => 'Neue Anfrage (intern)',
                'subject_de' => 'Neue Anfrage {{ referenz }} — {{ gutachtenart }}, {{ plz }}',
                'preheader_de' => '{{ kunde_name }} aus {{ ort }} wartet auf einen Sachverständigen.',
                'available_variables' => ['referenz', 'gutachtenart', 'plz', 'ort', 'kunde_name', 'kunde_telefon', 'kunde_email', 'fahrzeug', 'eingang_datum', 'cta_url'],
                'body_html' => '<p>Guten Tag,</p><p>soeben ist eine neue Anfrage eingegangen: <strong>{{ gutachtenart }}</strong> in {{ plz }} {{ ort }}.</p><p>Die Vermittlung an passende Partner läuft automatisch. Diese Nachricht dient nur Ihrer Information.</p>',
                'note_de' => 'Sie erhalten diese Nachricht, weil in den Einstellungen eine Büroadresse hinterlegt ist.',
            ],
            [
                'key' => 'keine-sachverstaendigen-gefunden',
                'name_de' => 'Keine Sachverständigen gefunden',
                'subject_de' => 'Nicht vermittelt: {{ referenz }} ({{ plz }})',
                'preheader_de' => 'Für diese Anfrage gibt es keinen passenden Partner.',
                'available_variables' => ['referenz', 'gutachtenart', 'plz', 'ort', 'cta_url'],
                'body_html' => '<p>Guten Tag,</p><p>für die Anfrage {{ referenz }} ({{ gutachtenart }}, {{ plz }} {{ ort }}) konnte kein passender Sachverständiger ermittelt werden.</p><p>Die Anfrage steht in der Administration unter „Nicht vermittelt“ und wartet auf eine manuelle Zuordnung.</p>',
            ],
            [
                'key' => 'anfrage-storniert',
                'name_de' => 'Anfrage storniert',
                'subject_de' => 'Ihre Begutachtung findet nicht statt — {{ referenz }}',
                'preheader_de' => 'Der Sachverständige hat den Auftrag zurückgegeben. So geht es weiter.',
                'available_variables' => ['kunde', 'referenz', 'gutachtenart', 'telefon', 'email', 'oeffnungszeiten', 'cta_url'],
                'body_html' => '<p>Guten Tag {{ kunde }},</p><p>der für Ihre Anfrage {{ referenz }} vorgesehene Sachverständige hat den Auftrag leider zurückgegeben — die Begutachtung findet daher nicht statt.</p><p>Sie können jederzeit eine neue Anfrage stellen; wir vermitteln Ihnen dann einen anderen Sachverständigen. Wenn es eilt, erreichen Sie uns direkt unter {{ telefon }} ({{ oeffnungszeiten }}) oder per E-Mail an {{ email }}.</p>',
                'note_de' => 'Es sind Ihnen keine Kosten entstanden. Die Vermittlung ist für Sie kostenfrei.',
            ],
            [
                'key' => 'anfrage-keine-rueckmeldung',
                'name_de' => 'Anfrage ohne Rückmeldung (Kunde)',
                'subject_de' => 'Ihre Anfrage {{ referenz }} — unser Zwischenstand',
                'preheader_de' => 'Wir konnten Ihre Anfrage noch nicht vermitteln und kümmern uns persönlich darum.',
                'available_variables' => ['kunde', 'referenz', 'gutachtenart', 'plz', 'ort', 'telefon', 'email', 'oeffnungszeiten'],
                'body_html' => '<p>Guten Tag {{ kunde }},</p><p>zu Ihrer Anfrage {{ referenz }} haben wir bisher keine Zusage von einem Sachverständigen in {{ plz }} {{ ort }} erhalten. Das liegt nicht an Ihren Angaben — im Moment ist in diesem Gebiet schlicht keiner unserer Partner frei.</p><p>Wir geben Ihre Anfrage deshalb nicht auf, sondern kümmern uns ab jetzt persönlich darum und melden uns bei Ihnen.</p><p>Wenn es eilt, erreichen Sie uns direkt unter {{ telefon }} ({{ oeffnungszeiten }}) oder per E-Mail an {{ email }}. Nennen Sie dabei einfach Ihre Referenz {{ referenz }}.</p>',
            ],
            [
                'key' => 'haftpflicht-laeuft-ab',
                'name_de' => 'Haftpflichtnachweis läuft ab (Partner)',
                'subject_de' => 'Ihr Haftpflichtnachweis läuft am {{ ablaufdatum }} ab',
                'preheader_de' => 'Ohne gültigen Nachweis können wir Ihnen keine Aufträge mehr vermitteln.',
                'available_variables' => ['sv_nachname', 'ablaufdatum', 'resttage', 'partner_id', 'cta_url'],
                'body_html' => '<p>Guten Tag {{ sv_nachname }},</p><p>Ihr hinterlegter Nachweis der Berufshaftpflichtversicherung ist noch bis zum {{ ablaufdatum }} gültig.</p><p>Ohne gültigen Nachweis dürfen wir Ihnen keine Aufträge mehr vermitteln. Laufende Aufträge bleiben davon unberührt und können normal abgeschlossen werden.</p><p>Bitte hinterlegen Sie den aktuellen Nachweis rechtzeitig in Ihrem Portal.</p>',
            ],
            [
                'key' => 'kontaktanfrage',
                'name_de' => 'Kontaktanfrage',
                'subject_de' => 'Kontaktanfrage von {{ name }}',
                'preheader_de' => 'Eine neue Nachricht ist über das Kontaktformular eingegangen.',
                'available_variables' => ['name', 'email', 'telefon', 'betreff', 'nachricht'],
                'body_html' => '<p>Guten Tag,</p><p>über das Kontaktformular ist eine Nachricht eingegangen.</p><p>{{ nachricht }}</p>',
            ],
            [
                'key' => 'testmail',
                'name_de' => 'Testmail',
                'subject_de' => 'Testmail der DKGZ-Plattform',
                'preheader_de' => 'Diese Testmail bestätigt, dass der Versand korrekt eingerichtet ist.',
                'available_variables' => ['zeitpunkt', 'server'],
                'body_html' => '<p>Guten Tag,</p><p>diese Nachricht bestätigt, dass der Mailversand der DKGZ-Plattform korrekt eingerichtet ist.</p><p>Gesendet am {{ zeitpunkt }} über {{ server }}.</p>',
            ],
        ];
    }
}
