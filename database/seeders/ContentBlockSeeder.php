<?php

namespace Database\Seeders;

use App\Models\ContentBlock;
use Illuminate\Database\Seeder;

/**
 * Every string on every public page, addressed as page.section.field.
 *
 * Copy is lifted verbatim from the design project — "DKGZ Homepage.dc.html",
 * "DKGZ Öffentliche Seiten.dc.html" and the client's website structure
 * document — so the seeded site reads exactly as designed and the admin edits
 * from there rather than from placeholder text.
 */
class ContentBlockSeeder extends Seeder
{
    public function run(): void
    {
        $order = 0;

        foreach ($this->blocks() as $block) {
            ContentBlock::updateOrCreate(
                [
                    'page_key' => $block['page_key'],
                    'section_key' => $block['section_key'],
                    'field_key' => $block['field_key'],
                ],
                [
                    'type' => $block['type'] ?? 'text',
                    'value' => $block['value'],
                    'label_de' => $block['label_de'],
                    'sort_order' => $order++,
                ]
            );
        }
    }

    /** @return list<array<string, string>> */
    private function blocks(): array
    {
        return array_merge(
            $this->homepage(),
            $this->requestPage(),
            $this->confirmationPage(),
            $this->partnerPage(),
            $this->processPage(),
            $this->aboutPage(),
            $this->contactPage(),
            $this->reviewPages(),
            $this->errorPages(),
        );
    }

    /** @return list<array<string, string>> */
    private function homepage(): array
    {
        return [
            ['page_key' => 'startseite', 'section_key' => 'meldeband', 'field_key' => 'text', 'value' => 'Bundesweites Netz geprüfter Kfz-Sachverständiger', 'label_de' => 'Meldeband über dem Kopfbereich'],

            ['page_key' => 'startseite', 'section_key' => 'hero', 'field_key' => 'eyebrow', 'value' => 'Kostenlose Vermittlung', 'label_de' => 'Hero · Übertitel'],
            ['page_key' => 'startseite', 'section_key' => 'hero', 'field_key' => 'ueberschrift', 'value' => 'Kfz-Sachverständigen in Ihrer Nähe finden', 'label_de' => 'Hero · Überschrift'],
            ['page_key' => 'startseite', 'section_key' => 'hero', 'field_key' => 'text', 'type' => 'richtext', 'value' => 'Wir vermitteln Ihre Anfrage schnell an einen passenden Kfz-Sachverständigen aus unserem bundesweiten Partnernetz.', 'label_de' => 'Hero · Fließtext'],
            ['page_key' => 'startseite', 'section_key' => 'hero', 'field_key' => 'plz_label', 'value' => 'Postleitzahl oder Ort', 'label_de' => 'Hero · Beschriftung des PLZ-Felds'],
            ['page_key' => 'startseite', 'section_key' => 'hero', 'field_key' => 'cta', 'value' => 'Gutachter finden', 'label_de' => 'Hero · Schaltfläche'],
            ['page_key' => 'startseite', 'section_key' => 'hero', 'field_key' => 'vorteil_1', 'value' => 'Anfrage kostenfrei', 'label_de' => 'Hero · Vorteil 1'],
            ['page_key' => 'startseite', 'section_key' => 'hero', 'field_key' => 'vorteil_2', 'value' => 'Bundesweites Netz', 'label_de' => 'Hero · Vorteil 2'],
            ['page_key' => 'startseite', 'section_key' => 'hero', 'field_key' => 'vorteil_3', 'value' => 'Schnelle Vermittlung', 'label_de' => 'Hero · Vorteil 3'],
            ['page_key' => 'startseite', 'section_key' => 'hero', 'field_key' => 'bild', 'type' => 'image', 'value' => '', 'label_de' => 'Hero · Bild'],

            ['page_key' => 'startseite', 'section_key' => 'leistungen', 'field_key' => 'ueberschrift', 'value' => 'Welches Gutachten benötigen Sie?', 'label_de' => 'Leistungen · Überschrift'],
            ['page_key' => 'startseite', 'section_key' => 'leistungen', 'field_key' => 'text', 'value' => 'Die häufigsten Anlässe. Weitere Leistungen finden Sie in der Übersicht.', 'label_de' => 'Leistungen · Fließtext'],
            ['page_key' => 'startseite', 'section_key' => 'leistungen', 'field_key' => 'cta', 'value' => 'Alle Leistungen ansehen', 'label_de' => 'Leistungen · Schaltfläche'],

            ['page_key' => 'startseite', 'section_key' => 'ablauf', 'field_key' => 'ueberschrift', 'value' => 'So einfach ist die Vermittlung', 'label_de' => 'Ablauf · Überschrift'],
            ['page_key' => 'startseite', 'section_key' => 'ablauf', 'field_key' => 'schritt_1_titel', 'value' => 'Kostenlose Anfrage stellen', 'label_de' => 'Ablauf · Schritt 1 Titel'],
            ['page_key' => 'startseite', 'section_key' => 'ablauf', 'field_key' => 'schritt_1_text', 'value' => 'Sie nennen uns Ihr Anliegen, den Standort des Fahrzeugs und Ihre Kontaktdaten.', 'label_de' => 'Ablauf · Schritt 1 Text'],
            ['page_key' => 'startseite', 'section_key' => 'ablauf', 'field_key' => 'schritt_2_titel', 'value' => 'Passenden Sachverständigen vermitteln', 'label_de' => 'Ablauf · Schritt 2 Titel'],
            ['page_key' => 'startseite', 'section_key' => 'ablauf', 'field_key' => 'schritt_2_text', 'value' => 'Die Anfrage geht automatisch an geeignete Sachverständige in Ihrer Region.', 'label_de' => 'Ablauf · Schritt 2 Text'],
            ['page_key' => 'startseite', 'section_key' => 'ablauf', 'field_key' => 'schritt_3_titel', 'value' => 'Sachverständiger nimmt an', 'label_de' => 'Ablauf · Schritt 3 Titel'],
            ['page_key' => 'startseite', 'section_key' => 'ablauf', 'field_key' => 'schritt_3_text', 'value' => 'Ein verfügbarer Sachverständiger übernimmt den Auftrag und meldet sich direkt bei Ihnen.', 'label_de' => 'Ablauf · Schritt 3 Text'],
            ['page_key' => 'startseite', 'section_key' => 'ablauf', 'field_key' => 'cta', 'value' => 'Jetzt Gutachter anfragen', 'label_de' => 'Ablauf · Schaltfläche'],
            ['page_key' => 'startseite', 'section_key' => 'ablauf', 'field_key' => 'hinweis', 'value' => 'Kostenlose Anfrage · Kein Kundenkonto · Kein Warten auf Angebote', 'label_de' => 'Ablauf · Hinweiszeile'],

            ['page_key' => 'startseite', 'section_key' => 'unfall', 'field_key' => 'ueberschrift', 'value' => 'Unfall gehabt?', 'label_de' => 'Unfall · Überschrift'],
            ['page_key' => 'startseite', 'section_key' => 'unfall', 'field_key' => 'text', 'value' => 'Wir vermitteln Ihnen schnell einen passenden Kfz-Sachverständigen in Ihrer Region.', 'label_de' => 'Unfall · Fließtext'],
            ['page_key' => 'startseite', 'section_key' => 'unfall', 'field_key' => 'cta', 'value' => 'Unfallgutachter finden', 'label_de' => 'Unfall · Schaltfläche'],
            ['page_key' => 'startseite', 'section_key' => 'unfall', 'field_key' => 'hinweis', 'value' => 'Die Anfrage und die Vermittlung über DKGZ sind für Sie kostenfrei.', 'label_de' => 'Unfall · Hinweis'],

            ['page_key' => 'startseite', 'section_key' => 'gebiete', 'field_key' => 'ueberschrift', 'value' => 'Bundesweites Netz. Regionale Vermittlung.', 'label_de' => 'Einsatzgebiete · Überschrift'],
            ['page_key' => 'startseite', 'section_key' => 'gebiete', 'field_key' => 'text', 'value' => 'Unsere Partner decken Einsatzgebiete in ganz Deutschland ab. Geben Sie Ihre Postleitzahl ein, und wir vermitteln in Ihrer Region.', 'label_de' => 'Einsatzgebiete · Fließtext'],

            ['page_key' => 'startseite', 'section_key' => 'warum', 'field_key' => 'ueberschrift', 'value' => 'Eine zentrale Stelle für die Gutachtersuche', 'label_de' => 'Warum DKGZ · Überschrift'],
            ['page_key' => 'startseite', 'section_key' => 'warum', 'field_key' => 'punkt_1_titel', 'value' => 'Kostenlose Anfrage', 'label_de' => 'Warum · Punkt 1 Titel'],
            ['page_key' => 'startseite', 'section_key' => 'warum', 'field_key' => 'punkt_1_text', 'value' => 'Die Anfrage und die Vermittlung sind für Sie kostenfrei.', 'label_de' => 'Warum · Punkt 1 Text'],
            ['page_key' => 'startseite', 'section_key' => 'warum', 'field_key' => 'punkt_2_titel', 'value' => 'Zentrale Vermittlung', 'label_de' => 'Warum · Punkt 2 Titel'],
            ['page_key' => 'startseite', 'section_key' => 'warum', 'field_key' => 'punkt_2_text', 'value' => 'Eine Anfrage statt vieler einzelner Anrufe bei Sachverständigen.', 'label_de' => 'Warum · Punkt 2 Text'],
            ['page_key' => 'startseite', 'section_key' => 'warum', 'field_key' => 'punkt_3_titel', 'value' => 'Regionales Netz', 'label_de' => 'Warum · Punkt 3 Titel'],
            ['page_key' => 'startseite', 'section_key' => 'warum', 'field_key' => 'punkt_3_text', 'value' => 'Anfragen werden nach dem Standort des Fahrzeugs vermittelt.', 'label_de' => 'Warum · Punkt 3 Text'],
            ['page_key' => 'startseite', 'section_key' => 'warum', 'field_key' => 'punkt_4_titel', 'value' => 'Verschiedene Leistungen', 'label_de' => 'Warum · Punkt 4 Titel'],
            ['page_key' => 'startseite', 'section_key' => 'warum', 'field_key' => 'punkt_4_text', 'value' => 'Von Unfallgutachten bis zur Fahrzeugbewertung.', 'label_de' => 'Warum · Punkt 4 Text'],
            ['page_key' => 'startseite', 'section_key' => 'warum', 'field_key' => 'punkt_5_titel', 'value' => 'Keine Registrierung', 'label_de' => 'Warum · Punkt 5 Titel'],
            ['page_key' => 'startseite', 'section_key' => 'warum', 'field_key' => 'punkt_5_text', 'value' => 'Für eine Anfrage benötigen Sie kein Kundenkonto.', 'label_de' => 'Warum · Punkt 5 Text'],
            ['page_key' => 'startseite', 'section_key' => 'warum', 'field_key' => 'punkt_6_titel', 'value' => 'Direkter Kontakt', 'label_de' => 'Warum · Punkt 6 Titel'],
            ['page_key' => 'startseite', 'section_key' => 'warum', 'field_key' => 'punkt_6_text', 'value' => 'Nach der Vermittlung klärt der Sachverständige alles Weitere direkt mit Ihnen.', 'label_de' => 'Warum · Punkt 6 Text'],

            ['page_key' => 'startseite', 'section_key' => 'vertrauen', 'field_key' => 'ueberschrift', 'value' => 'Bundesweites Partnernetz', 'label_de' => 'Vertrauen · Überschrift'],
            ['page_key' => 'startseite', 'section_key' => 'vertrauen', 'field_key' => 'text', 'value' => 'Unser Partnernetz wächst kontinuierlich. Sachverständige mit nachgewiesener Qualifikation decken Einsatzgebiete in ganz Deutschland ab.', 'label_de' => 'Vertrauen · Fließtext'],

            ['page_key' => 'startseite', 'section_key' => 'partner', 'field_key' => 'ueberschrift', 'value' => 'Sie sind Kfz-Sachverständiger?', 'label_de' => 'Für Partner · Überschrift'],
            ['page_key' => 'startseite', 'section_key' => 'partner', 'field_key' => 'text', 'value' => 'Werden Sie Teil des DKGZ-Partnernetzes und erhalten Sie passende Anfragen aus Ihrem Einsatzgebiet.', 'label_de' => 'Für Partner · Fließtext'],
            ['page_key' => 'startseite', 'section_key' => 'partner', 'field_key' => 'cta', 'value' => 'Partner werden', 'label_de' => 'Für Partner · Schaltfläche'],

            ['page_key' => 'startseite', 'section_key' => 'faq', 'field_key' => 'ueberschrift', 'value' => 'Häufige Fragen', 'label_de' => 'FAQ · Überschrift'],

            ['page_key' => 'startseite', 'section_key' => 'abschluss', 'field_key' => 'ueberschrift', 'value' => 'Kostenlos einen Kfz-Sachverständigen anfragen', 'label_de' => 'Abschluss-CTA · Überschrift'],
            ['page_key' => 'startseite', 'section_key' => 'abschluss', 'field_key' => 'text', 'value' => 'Nennen Sie uns Ihren Standort und Ihr Anliegen. Wir leiten Ihre Anfrage an einen passenden Sachverständigen aus unserem Netz weiter.', 'label_de' => 'Abschluss-CTA · Fließtext'],
            ['page_key' => 'startseite', 'section_key' => 'abschluss', 'field_key' => 'cta', 'value' => 'Gutachter finden', 'label_de' => 'Abschluss-CTA · Schaltfläche'],
            ['page_key' => 'startseite', 'section_key' => 'abschluss', 'field_key' => 'hinweis', 'value' => 'Kostenlose Anfrage · Kein Kundenkonto erforderlich', 'label_de' => 'Abschluss-CTA · Hinweis'],

            ['page_key' => 'startseite', 'section_key' => 'fuss', 'field_key' => 'beschreibung', 'value' => 'DKGZ vermittelt Anfragen an selbstständige Kfz-Sachverständige. Die Begutachtung erbringt der jeweils vermittelte Sachverständige.', 'label_de' => 'Fußbereich · Beschreibung'],
        ];
    }

    /** @return list<array<string, string>> */
    private function requestPage(): array
    {
        return [
            ['page_key' => 'anfrage', 'section_key' => 'kopf', 'field_key' => 'eyebrow', 'value' => 'Kostenlose Anfrage', 'label_de' => 'Übertitel'],
            ['page_key' => 'anfrage', 'section_key' => 'kopf', 'field_key' => 'ueberschrift', 'value' => 'Gutachter anfragen', 'label_de' => 'Überschrift'],
            ['page_key' => 'anfrage', 'section_key' => 'kopf', 'field_key' => 'text', 'value' => 'Sieben Pflichtangaben. Ihre Kontaktdaten sieht ausschließlich der Sachverständige, der den Auftrag annimmt.', 'label_de' => 'Fließtext'],
            ['page_key' => 'anfrage', 'section_key' => 'formular', 'field_key' => 'abschnitt_anliegen', 'value' => 'Ihr Anliegen', 'label_de' => 'Abschnitt · Anliegen'],
            ['page_key' => 'anfrage', 'section_key' => 'formular', 'field_key' => 'abschnitt_kontakt', 'value' => 'Ihre Kontaktdaten', 'label_de' => 'Abschnitt · Kontaktdaten'],
            ['page_key' => 'anfrage', 'section_key' => 'formular', 'field_key' => 'abschnitt_optional', 'value' => 'Optional', 'label_de' => 'Abschnitt · Optional'],
            ['page_key' => 'anfrage', 'section_key' => 'formular', 'field_key' => 'cta', 'value' => 'Anfrage absenden', 'label_de' => 'Schaltfläche'],
            ['page_key' => 'anfrage', 'section_key' => 'formular', 'field_key' => 'datenschutzhinweis', 'value' => 'Ihre Daten werden ausschließlich an den Sachverständigen übermittelt, der den Auftrag annimmt. Es entstehen keine Kosten und keine Verpflichtung.', 'label_de' => 'Hinweis unter der Schaltfläche'],
            ['page_key' => 'anfrage', 'section_key' => 'seitenleiste', 'field_key' => 'punkt_1', 'value' => 'Anfrage und Vermittlung kostenfrei', 'label_de' => 'Seitenleiste · Punkt 1'],
            ['page_key' => 'anfrage', 'section_key' => 'seitenleiste', 'field_key' => 'punkt_2', 'value' => 'Keine Registrierung, kein Kundenkonto', 'label_de' => 'Seitenleiste · Punkt 2'],
            ['page_key' => 'anfrage', 'section_key' => 'seitenleiste', 'field_key' => 'punkt_3', 'value' => 'Geprüfte Partner in allen PLZ-Gebieten', 'label_de' => 'Seitenleiste · Punkt 3'],
            ['page_key' => 'anfrage', 'section_key' => 'seitenleiste', 'field_key' => 'telefon_titel', 'value' => 'Lieber telefonisch?', 'label_de' => 'Seitenleiste · Telefonblock Überschrift'],
        ];
    }

    /** @return list<array<string, string>> */
    private function confirmationPage(): array
    {
        return [
            ['page_key' => 'bestaetigung', 'section_key' => 'kopf', 'field_key' => 'ueberschrift', 'value' => 'Ihre Anfrage ist eingegangen.', 'label_de' => 'Überschrift'],
            ['page_key' => 'bestaetigung', 'section_key' => 'kopf', 'field_key' => 'text', 'value' => 'Wir leiten Ihre Anfrage jetzt an geeignete Sachverständige in Ihrer Region weiter. Sobald ein Partner den Auftrag annimmt, erhält er Ihre Kontaktdaten und meldet sich direkt bei Ihnen.', 'label_de' => 'Fließtext'],
            ['page_key' => 'bestaetigung', 'section_key' => 'referenz', 'field_key' => 'label', 'value' => 'Ihre Vorgangsnummer', 'label_de' => 'Beschriftung der Vorgangsnummer'],
            ['page_key' => 'bestaetigung', 'section_key' => 'referenz', 'field_key' => 'hinweis', 'value' => 'Bitte halten Sie diese Nummer bei Rückfragen bereit. Sie steht auch in der Bestätigungs-E-Mail.', 'label_de' => 'Hinweis zur Vorgangsnummer'],
            ['page_key' => 'bestaetigung', 'section_key' => 'ablauf', 'field_key' => 'ueberschrift', 'value' => 'Wie es weitergeht', 'label_de' => 'Ablauf · Überschrift'],
            ['page_key' => 'bestaetigung', 'section_key' => 'ablauf', 'field_key' => 'schritt_1_titel', 'value' => 'Vermittlung läuft', 'label_de' => 'Ablauf · Schritt 1 Titel'],
            ['page_key' => 'bestaetigung', 'section_key' => 'ablauf', 'field_key' => 'schritt_1_text', 'value' => 'Ihre Anfrage geht an alle passenden Sachverständigen, deren Einsatzgebiet Ihre Postleitzahl abdeckt.', 'label_de' => 'Ablauf · Schritt 1 Text'],
            ['page_key' => 'bestaetigung', 'section_key' => 'ablauf', 'field_key' => 'schritt_2_titel', 'value' => 'Annahme durch einen Partner', 'label_de' => 'Ablauf · Schritt 2 Titel'],
            ['page_key' => 'bestaetigung', 'section_key' => 'ablauf', 'field_key' => 'schritt_2_text', 'value' => 'Der erste verfügbare Sachverständige übernimmt. Sie erhalten eine E-Mail mit Name und Kontaktdaten.', 'label_de' => 'Ablauf · Schritt 2 Text'],
            ['page_key' => 'bestaetigung', 'section_key' => 'ablauf', 'field_key' => 'schritt_3_titel', 'value' => 'Direkte Terminabstimmung', 'label_de' => 'Ablauf · Schritt 3 Titel'],
            ['page_key' => 'bestaetigung', 'section_key' => 'ablauf', 'field_key' => 'schritt_3_text', 'value' => 'Alles Weitere klären Sie unmittelbar mit dem Sachverständigen. DKGZ tritt dabei nicht mehr dazwischen.', 'label_de' => 'Ablauf · Schritt 3 Text'],
        ];
    }

    /** @return list<array<string, string>> */
    private function partnerPage(): array
    {
        return [
            ['page_key' => 'partner', 'section_key' => 'hero', 'field_key' => 'eyebrow', 'value' => 'Partnernetz', 'label_de' => 'Hero · Übertitel'],
            ['page_key' => 'partner', 'section_key' => 'hero', 'field_key' => 'ueberschrift', 'value' => 'Aufträge aus Ihrer Region. Ohne Akquise.', 'label_de' => 'Hero · Überschrift'],
            ['page_key' => 'partner', 'section_key' => 'hero', 'field_key' => 'text', 'value' => 'Sie legen Einsatzgebiet und Leistungen fest, wir leiten passende Anfragen weiter. Sie entscheiden bei jeder Anfrage neu.', 'label_de' => 'Hero · Fließtext'],
            ['page_key' => 'partner', 'section_key' => 'hero', 'field_key' => 'cta_primaer', 'value' => 'Als Partner registrieren', 'label_de' => 'Hero · Hauptschaltfläche'],
            ['page_key' => 'partner', 'section_key' => 'hero', 'field_key' => 'cta_sekundaer', 'value' => 'Zum Portal anmelden', 'label_de' => 'Hero · Zweitschaltfläche'],
            ['page_key' => 'partner', 'section_key' => 'hero', 'field_key' => 'bild', 'type' => 'image', 'value' => '', 'label_de' => 'Hero · Bild'],
            ['page_key' => 'partner', 'section_key' => 'provision', 'field_key' => 'ueberschrift', 'value' => 'Das Provisionsmodell', 'label_de' => 'Provision · Überschrift'],
            ['page_key' => 'partner', 'section_key' => 'provision', 'field_key' => 'text', 'value' => 'Es gibt keine Grundgebühr und keine Kosten pro Anfrage. Die Vermittlungsprovision fällt ausschließlich auf abgeschlossene Aufträge an und wird auf das tatsächlich berechnete Netto-Honorar erhoben.', 'label_de' => 'Provision · Fließtext'],
            ['page_key' => 'partner', 'section_key' => 'voraussetzungen', 'field_key' => 'ueberschrift', 'value' => 'Voraussetzungen für die Aufnahme', 'label_de' => 'Voraussetzungen · Überschrift'],
            ['page_key' => 'partner', 'section_key' => 'voraussetzungen', 'field_key' => 'punkt_1', 'value' => 'Qualifikationsnachweis als Kfz-Sachverständiger', 'label_de' => 'Voraussetzung 1'],
            ['page_key' => 'partner', 'section_key' => 'voraussetzungen', 'field_key' => 'punkt_2', 'value' => 'Gewerbeanmeldung und USt-IdNr.', 'label_de' => 'Voraussetzung 2'],
            ['page_key' => 'partner', 'section_key' => 'voraussetzungen', 'field_key' => 'punkt_3', 'value' => 'Berufshaftpflichtversicherung', 'label_de' => 'Voraussetzung 3'],
            ['page_key' => 'partner', 'section_key' => 'voraussetzungen', 'field_key' => 'punkt_4', 'value' => 'Definiertes Einsatzgebiet nach PLZ', 'label_de' => 'Voraussetzung 4'],
            ['page_key' => 'partner', 'section_key' => 'voraussetzungen', 'field_key' => 'punkt_5', 'value' => 'Rückmeldung auf Anfragen innerhalb der Frist', 'label_de' => 'Voraussetzung 5'],
            ['page_key' => 'partner', 'section_key' => 'voraussetzungen', 'field_key' => 'punkt_6', 'value' => 'Gutachten und Rechnung im Portal hinterlegen', 'label_de' => 'Voraussetzung 6'],
            ['page_key' => 'partner', 'section_key' => 'aufnahme', 'field_key' => 'ueberschrift', 'value' => 'Aufnahme beantragen', 'label_de' => 'Aufnahme · Überschrift'],
            ['page_key' => 'partner', 'section_key' => 'aufnahme', 'field_key' => 'text', 'value' => 'Registrierung in etwa fünf Minuten. Die Freigabe erfolgt nach Prüfung der Nachweise durch DKGZ.', 'label_de' => 'Aufnahme · Fließtext'],
            ['page_key' => 'partner', 'section_key' => 'aufnahme', 'field_key' => 'cta', 'value' => 'Registrierung starten', 'label_de' => 'Aufnahme · Schaltfläche'],
            ['page_key' => 'partner', 'section_key' => 'aufnahme', 'field_key' => 'hinweis', 'value' => 'Keine Vertragslaufzeit. Sie können Ihr Profil jederzeit auf „Nicht verfügbar“ setzen.', 'label_de' => 'Aufnahme · Hinweis'],
        ];
    }

    /** @return list<array<string, string>> */
    private function processPage(): array
    {
        return [
            ['page_key' => 'ablauf', 'section_key' => 'kopf', 'field_key' => 'ueberschrift', 'value' => 'So funktioniert die Vermittlung', 'label_de' => 'Überschrift'],
            ['page_key' => 'ablauf', 'section_key' => 'kopf', 'field_key' => 'text', 'value' => 'Von der Anfrage bis zum fertigen Gutachten — ohne Angebotsvergleich und ohne Kundenkonto.', 'label_de' => 'Fließtext'],
        ];
    }

    /** @return list<array<string, string>> */
    private function aboutPage(): array
    {
        return [
            ['page_key' => 'ueber-uns', 'section_key' => 'kopf', 'field_key' => 'ueberschrift', 'value' => 'Die Deutsche KFZ-Gutachterzentrale', 'label_de' => 'Überschrift'],
            ['page_key' => 'ueber-uns', 'section_key' => 'kopf', 'field_key' => 'text', 'type' => 'richtext', 'value' => 'DKGZ ist eine zentrale Anlaufstelle für die Vermittlung von Kfz-Sachverständigen. Wir erstellen keine Gutachten, sondern bringen Anfragende und geprüfte Sachverständige aus einem bundesweiten Partnernetz zusammen.', 'label_de' => 'Fließtext'],
        ];
    }

    /** @return list<array<string, string>> */
    private function contactPage(): array
    {
        return [
            ['page_key' => 'kontakt', 'section_key' => 'kopf', 'field_key' => 'ueberschrift', 'value' => 'Kontakt', 'label_de' => 'Überschrift'],
            ['page_key' => 'kontakt', 'section_key' => 'kopf', 'field_key' => 'text', 'value' => 'Für Rückfragen zu einem laufenden Vorgang halten Sie bitte Ihre Vorgangsnummer bereit.', 'label_de' => 'Fließtext'],
            ['page_key' => 'kontakt', 'section_key' => 'formular', 'field_key' => 'cta', 'value' => 'Nachricht senden', 'label_de' => 'Schaltfläche'],
        ];
    }

    /** @return list<array<string, string>> */
    private function reviewPages(): array
    {
        return [
            ['page_key' => 'bewertung', 'section_key' => 'kopf', 'field_key' => 'ueberschrift', 'value' => 'Wie zufrieden waren Sie mit dem vermittelten Sachverständigen?', 'label_de' => 'Bewertung · Überschrift'],
            ['page_key' => 'bewertung', 'section_key' => 'kopf', 'field_key' => 'text', 'value' => '1 bedeutet sehr unzufrieden, 10 bedeutet sehr zufrieden. Ihre Bewertung wird nicht öffentlich angezeigt.', 'label_de' => 'Bewertung · Fließtext'],
            ['page_key' => 'bewertung', 'section_key' => 'skala', 'field_key' => 'min_label', 'value' => 'Sehr unzufrieden', 'label_de' => 'Skala · linke Beschriftung'],
            ['page_key' => 'bewertung', 'section_key' => 'skala', 'field_key' => 'max_label', 'value' => 'Sehr zufrieden', 'label_de' => 'Skala · rechte Beschriftung'],
            ['page_key' => 'bewertung', 'section_key' => 'skala', 'field_key' => 'cta', 'value' => 'Bewertung absenden', 'label_de' => 'Bewertung · Schaltfläche'],
            ['page_key' => 'bewertung', 'section_key' => 'feedback', 'field_key' => 'ueberschrift', 'value' => 'Was hätte besser laufen sollen?', 'label_de' => 'Feedback · Überschrift'],
            ['page_key' => 'bewertung', 'section_key' => 'feedback', 'field_key' => 'text', 'value' => 'Diese Angaben sieht ausschließlich die DKGZ-Qualitätssicherung. Sie werden nicht an den Sachverständigen weitergegeben.', 'label_de' => 'Feedback · Fließtext'],
            ['page_key' => 'bewertung', 'section_key' => 'feedback', 'field_key' => 'cta', 'value' => 'Rückmeldung senden', 'label_de' => 'Feedback · Schaltfläche'],
            ['page_key' => 'bewertung', 'section_key' => 'danke', 'field_key' => 'ueberschrift', 'value' => 'Vielen Dank für Ihre Rückmeldung.', 'label_de' => 'Danke · Überschrift'],
            ['page_key' => 'bewertung', 'section_key' => 'danke', 'field_key' => 'text', 'value' => 'Ihre Bewertung hilft uns, den Standard im Partnernetz zu halten.', 'label_de' => 'Danke · Fließtext'],
        ];
    }

    /** @return list<array<string, string>> */
    private function errorPages(): array
    {
        return [
            ['page_key' => 'fehler', 'section_key' => '404', 'field_key' => 'ueberschrift', 'value' => 'Diese Seite existiert nicht.', 'label_de' => '404 · Überschrift'],
            ['page_key' => 'fehler', 'section_key' => '404', 'field_key' => 'text', 'value' => 'Möglicherweise wurde die Adresse geändert oder falsch eingegeben. Über die Startseite gelangen Sie direkt zur Anfrage.', 'label_de' => '404 · Fließtext'],
            ['page_key' => 'fehler', 'section_key' => '419', 'field_key' => 'ueberschrift', 'value' => 'Ihre Sitzung ist abgelaufen.', 'label_de' => '419 · Überschrift'],
            ['page_key' => 'fehler', 'section_key' => '419', 'field_key' => 'text', 'value' => 'Aus Sicherheitsgründen wurde die Sitzung beendet. Bitte laden Sie die Seite neu und senden Sie das Formular erneut ab.', 'label_de' => '419 · Fließtext'],
            ['page_key' => 'fehler', 'section_key' => '500', 'field_key' => 'ueberschrift', 'value' => 'Es ist ein Fehler aufgetreten.', 'label_de' => '500 · Überschrift'],
            ['page_key' => 'fehler', 'section_key' => '500', 'field_key' => 'text', 'value' => 'Der Fehler wurde protokolliert. Bitte versuchen Sie es in Kürze erneut oder rufen Sie uns an.', 'label_de' => '500 · Fließtext'],
            ['page_key' => 'fehler', 'section_key' => '503', 'field_key' => 'ueberschrift', 'value' => 'Wartungsarbeiten', 'label_de' => '503 · Überschrift'],
            ['page_key' => 'fehler', 'section_key' => '503', 'field_key' => 'text', 'value' => 'Die Seite wird derzeit gewartet. Bitte versuchen Sie es in Kürze erneut.', 'label_de' => '503 · Fließtext'],
        ];
    }
}
