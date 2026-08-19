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
                'available_variables' => ['sv_nachname', 'referenz', 'gutachtenart', 'plz', 'ort', 'fahrzeug', 'schadenart', 'frist', 'cta_url'],
                'body_html' => '<p>Guten Tag {{ sv_nachname }},</p><p>für Ihr Einsatzgebiet liegt eine neue Anfrage vor. Sie können sie im Portal annehmen oder ablehnen — eine Ablehnung wirkt sich nicht auf die weitere Verteilung aus.</p><p>Die Anfrage wurde gleichzeitig an weitere Partner im Gebiet gesendet. Der erste verfügbare Partner übernimmt den Auftrag.</p>',
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
                'key' => 'auftrag-bestaetigt',
                'name_de' => 'Auftrag bestätigt',
                'subject_de' => 'Auftrag bestätigt — {{ referenz }}',
                'preheader_de' => 'Die Kontaktdaten der anfragenden Person sind jetzt freigegeben.',
                'available_variables' => ['sv_nachname', 'referenz', 'gutachtenart', 'kunde_name', 'kunde_telefon', 'kunde_email', 'plz', 'ort', 'fahrzeug', 'cta_url'],
                'body_html' => '<p>Guten Tag {{ sv_nachname }},</p><p>Sie haben den Auftrag {{ referenz }} übernommen. Die Kontaktdaten der anfragenden Person sind ab sofort für Sie sichtbar.</p><p>Bitte nehmen Sie zeitnah Kontakt auf und stimmen Sie den Termin direkt ab. Nach Abschluss hinterlegen Sie Gutachten und Rechnung im Portal.</p>',
            ],
            [
                'key' => 'auftrag-abgeschlossen',
                'name_de' => 'Auftrag abgeschlossen',
                'subject_de' => 'Ihr Gutachten liegt vor — {{ referenz }}',
                'preheader_de' => 'Gutachten und Rechnung sind hinterlegt. Der Vorgang ist damit abgeschlossen.',
                'available_variables' => ['anrede', 'nachname', 'referenz', 'sv_firma', 'gutachtenart', 'abschluss_zeit', 'honorar', 'cta_url'],
                'body_html' => '<p>Guten Tag {{ anrede }} {{ nachname }},</p><p>der Sachverständige hat die Begutachtung abgeschlossen und die Unterlagen hinterlegt. Gutachten und Rechnung finden Sie im Anhang dieser E-Mail.</p><p>Bei Rückfragen zum Inhalt des Gutachtens wenden Sie sich bitte direkt an den Sachverständigen.</p>',
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
                'preheader_de' => 'Begründung und weiteres Vorgehen.',
                'available_variables' => ['sv_nachname', 'firma', 'grund'],
                'body_html' => '<p>Guten Tag {{ sv_nachname }},</p><p>wir konnten Ihre Registrierung nicht freigeben. Begründung: {{ grund }}</p><p>Wenn Sie die genannten Punkte ergänzen können, melden Sie sich gern erneut bei uns. Wir prüfen Ihre Unterlagen dann noch einmal.</p>',
            ],
            [
                'key' => 'konto-gesperrt',
                'name_de' => 'Konto gesperrt',
                'subject_de' => 'Ihr Zugang wurde gesperrt',
                'preheader_de' => 'Begründung und Kontakt zur Klärung.',
                'available_variables' => ['sv_nachname', 'firma', 'grund'],
                'body_html' => '<p>Guten Tag {{ sv_nachname }},</p><p>Ihr Zugang zum Partnerportal wurde gesperrt. Begründung: {{ grund }}</p><p>Sie erhalten vorerst keine weiteren Anfragen. Zur Klärung wenden Sie sich bitte an die Administration.</p>',
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
                'key' => 'passwort-zuruecksetzen',
                'name_de' => 'Passwort zurücksetzen',
                'subject_de' => 'Passwort zurücksetzen',
                'preheader_de' => 'Der Link ist 60 Minuten gültig.',
                'available_variables' => ['nachname', 'cta_url', 'gueltigkeit_minuten'],
                'body_html' => '<p>Guten Tag {{ nachname }},</p><p>Sie haben angefordert, Ihr Passwort zurückzusetzen. Über die Schaltfläche unten vergeben Sie ein neues Passwort.</p><p>Der Link ist {{ gueltigkeit_minuten }} Minuten gültig. Wenn Sie die Anfrage nicht gestellt haben, ignorieren Sie diese E-Mail — Ihr Passwort bleibt dann unverändert.</p>',
            ],
            [
                'key' => 'email-bestaetigen',
                'name_de' => 'E-Mail bestätigen',
                'subject_de' => 'Bitte bestätigen Sie Ihre E-Mail-Adresse',
                'preheader_de' => 'Ein Klick genügt.',
                'available_variables' => ['nachname', 'cta_url'],
                'body_html' => '<p>Guten Tag {{ nachname }},</p><p>bitte bestätigen Sie Ihre E-Mail-Adresse, damit wir Ihnen Benachrichtigungen zu Anfragen und Aufträgen zustellen können.</p>',
            ],
            [
                'key' => 'provisionsabrechnung',
                'name_de' => 'Provisionsabrechnung',
                'subject_de' => 'Provisionsabrechnung {{ rechnungsnummer }}',
                'preheader_de' => 'Die Abrechnung liegt als PDF bei.',
                'available_variables' => ['sv_nachname', 'firma', 'rechnungsnummer', 'zeitraum', 'betrag', 'faellig_am', 'cta_url'],
                'body_html' => '<p>Guten Tag {{ sv_nachname }},</p><p>anbei erhalten Sie die Provisionsabrechnung {{ rechnungsnummer }} über {{ betrag }}.</p><p>Die Einzelpositionen finden Sie im angehängten PDF und jederzeit im Partnerportal unter Provisionen.</p>',
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
                'key' => 'kontaktanfrage',
                'name_de' => 'Kontaktanfrage',
                'subject_de' => 'Kontaktanfrage von {{ name }}',
                'preheader_de' => 'Über das Kontaktformular eingegangen.',
                'available_variables' => ['name', 'email', 'telefon', 'betreff', 'nachricht'],
                'body_html' => '<p>Guten Tag,</p><p>über das Kontaktformular ist eine Nachricht eingegangen.</p><p>{{ nachricht }}</p>',
            ],
            [
                'key' => 'testmail',
                'name_de' => 'Testmail',
                'subject_de' => 'Testmail der DKGZ-Plattform',
                'preheader_de' => 'Der Mailversand funktioniert.',
                'available_variables' => ['zeitpunkt', 'server'],
                'body_html' => '<p>Guten Tag,</p><p>diese Nachricht bestätigt, dass der Mailversand der DKGZ-Plattform korrekt eingerichtet ist.</p><p>Gesendet am {{ zeitpunkt }} über {{ server }}.</p>',
            ],
        ];
    }
}
