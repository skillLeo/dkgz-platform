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
    /**
     * Adds blocks that are missing and refreshes what belongs to the developer,
     * never what belongs to the operator.
     *
     * This used to overwrite `value` on every run, so each deployment silently
     * reset the site to the seeded copy — the hero photograph somebody uploaded
     * reverted to the placeholder, and the only clue was that it kept coming
     * back. Labels, help text and ordering are ours to change; the words and
     * pictures on the site are theirs.
     */
    public function run(): void
    {
        $order = 0;

        foreach ($this->blocks() as $block) {
            $content = ContentBlock::firstOrNew([
                'page_key' => $block['page_key'],
                'section_key' => $block['section_key'],
                'field_key' => $block['field_key'],
            ]);

            $content->fill([
                'type' => $block['type'] ?? 'text',
                'label_de' => $block['label_de'],
                'help_de' => $block['help_de'] ?? $content->help_de,
                'sort_order' => $order++,
            ]);

            // Only ever the seeded default, and only for a block nobody has
            // seen yet.
            if (! $content->exists) {
                $content->value = $block['value'];
            }

            $content->save();
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
            $this->servicesPage(),
            $this->invoiceDocument(),
            $this->cookieBanner(),
            $this->cityPages(),
            $this->faqPage(),
            $this->aboutPage(),
            $this->contactPage(),
            $this->reviewPages(),
            $this->errorPages(),
        );
    }

    /**
     * Copy taken verbatim from "DKGZ Homepage.dc.html". Section keys follow the
     * anchors the design uses, so the admin editor lists them in the order a
     * visitor reads them.
     *
     * @return list<array<string, string>>
     */
    private function homepage(): array
    {
        return [
            ['page_key' => 'startseite', 'section_key' => 'meldeband', 'field_key' => 'text', 'value' => 'Bundesweites Netz geprüfter Kfz-Sachverständiger', 'label_de' => 'Meldeband über dem Kopfbereich'],

            ['page_key' => 'startseite', 'section_key' => 'hero', 'field_key' => 'eyebrow', 'value' => 'Deutsche Kfz-Gutachterzentrale', 'label_de' => 'Hero · Übertitel'],
            ['page_key' => 'startseite', 'section_key' => 'hero', 'field_key' => 'zeile_1', 'value' => 'Kfz-Gutachter finden.', 'label_de' => 'Hero · Überschrift, Zeile 1'],
            ['page_key' => 'startseite', 'section_key' => 'hero', 'field_key' => 'zeile_2', 'value' => 'Bundesweit koordiniert.', 'label_de' => 'Hero · Überschrift, Zeile 2'],
            ['page_key' => 'startseite', 'section_key' => 'hero', 'field_key' => 'zeile_3', 'value' => 'Ohne Umwege.', 'label_de' => 'Hero · Überschrift, Zeile 3'],
            ['page_key' => 'startseite', 'section_key' => 'hero', 'field_key' => 'text', 'type' => 'richtext', 'value' => 'Sie stellen eine Anfrage. Wir vermitteln sie automatisch an einen qualifizierten Sachverständigen in Ihrer Region. Kein Vergleichen, kein Warten auf mehrere Angebote.', 'label_de' => 'Hero · Fließtext'],
            ['page_key' => 'startseite', 'section_key' => 'hero', 'field_key' => 'frage_leistung', 'value' => 'Welche Gutachtenart benötigen Sie?', 'label_de' => 'Hero · Frage nach der Leistung'],
            ['page_key' => 'startseite', 'section_key' => 'hero', 'field_key' => 'frage_hinweis', 'value' => 'Wählen Sie die passende Leistung aus, damit wir den richtigen Sachverständigen für Sie finden.', 'label_de' => 'Hero · Hinweis unter der Frage', 'help_de' => 'Wird durch die Beschreibung der Leistung ersetzt, sobald eine gewählt ist.'],
            ['page_key' => 'startseite', 'section_key' => 'hero', 'field_key' => 'frage_plz', 'value' => 'Postleitzahl des Fahrzeugstandorts', 'label_de' => 'Hero · Beschriftung des PLZ-Felds'],
            ['page_key' => 'startseite', 'section_key' => 'hero', 'field_key' => 'cta', 'value' => 'Jetzt Gutachter anfragen', 'label_de' => 'Hero · Überschrift des Anfragefelds'],
            ['page_key' => 'startseite', 'section_key' => 'hero', 'field_key' => 'cta_button', 'value' => 'Weiter', 'label_de' => 'Hero · Schaltfläche'],
            ['page_key' => 'startseite', 'section_key' => 'hero', 'field_key' => 'telefon_titel', 'value' => 'Lieber telefonisch?', 'label_de' => 'Hero · Einleitung der Telefonzeile'],
            ['page_key' => 'startseite', 'section_key' => 'hero', 'field_key' => 'cta_hinweis', 'value' => 'Kostenlos und unverbindlich · Antwort in der Regel innerhalb von 24 Stunden', 'label_de' => 'Hero · Hinweis unter der Schaltfläche', 'help_de' => 'Leer lassen, um die Zeile wegzulassen.'],
            ['page_key' => 'startseite', 'section_key' => 'hero', 'field_key' => 'hinweis', 'value' => 'Kostenlos und unverbindlich · Keine Registrierung erforderlich', 'label_de' => 'Hero · Hinweis unter dem Feld'],
            ['page_key' => 'startseite', 'section_key' => 'hero', 'field_key' => 'bild', 'type' => 'image', 'value' => '/images/hero-institutionell.svg', 'label_de' => 'Hero · Bild', 'help_de' => 'Platzhaltergrafik im DKGZ-Stil. Ersetzen Sie sie durch ein echtes Foto im Hochformat 4:5.'],
            ['page_key' => 'startseite', 'section_key' => 'hero', 'field_key' => 'bild_groesse', 'value' => '100', 'label_de' => 'Hero · Bildgröße in Prozent', 'help_de' => 'Zwischen 60 und 140. 100 ist die Standardgröße; 130 macht das Bild größer, 80 kleiner. Leer lassen für die Standardgröße.'],
            ['page_key' => 'startseite', 'section_key' => 'hero', 'field_key' => 'siegel_titel', 'value' => 'Geprüfte Partner', 'label_de' => 'Hero · Siegel, Überschrift'],
            ['page_key' => 'startseite', 'section_key' => 'hero', 'field_key' => 'siegel_text', 'value' => 'Nach bundesweit einheitlichem Standard', 'label_de' => 'Hero · Siegel, Zusatz'],

            ['page_key' => 'startseite', 'section_key' => 'kennzahlen', 'field_key' => 'wert_1', 'value' => 'Bundesweit', 'label_de' => 'Kennzahl 1 · Wert'],
            ['page_key' => 'startseite', 'section_key' => 'kennzahlen', 'field_key' => 'text_1', 'value' => 'Alle PLZ-Gebiete abgedeckt', 'label_de' => 'Kennzahl 1 · Beschriftung'],
            ['page_key' => 'startseite', 'section_key' => 'kennzahlen', 'field_key' => 'wert_2', 'value' => '24 Std.', 'label_de' => 'Kennzahl 2 · Wert'],
            ['page_key' => 'startseite', 'section_key' => 'kennzahlen', 'field_key' => 'text_2', 'value' => 'Durchschnittliche Rückmeldung', 'label_de' => 'Kennzahl 2 · Beschriftung'],
            ['page_key' => 'startseite', 'section_key' => 'kennzahlen', 'field_key' => 'wert_3', 'value' => '0 €', 'label_de' => 'Kennzahl 3 · Wert'],
            ['page_key' => 'startseite', 'section_key' => 'kennzahlen', 'field_key' => 'text_3', 'value' => 'Kosten für die Vermittlung', 'label_de' => 'Kennzahl 3 · Beschriftung'],
            ['page_key' => 'startseite', 'section_key' => 'kennzahlen', 'field_key' => 'wert_4', 'value' => '1 Anfrage', 'label_de' => 'Kennzahl 4 · Wert'],
            ['page_key' => 'startseite', 'section_key' => 'kennzahlen', 'field_key' => 'text_4', 'value' => 'Statt zehn Einzelanfragen', 'label_de' => 'Kennzahl 4 · Beschriftung'],

            ['page_key' => 'startseite', 'section_key' => 'ablauf', 'field_key' => 'ueberschrift', 'value' => 'So funktioniert es', 'label_de' => 'Ablauf · Überschrift'],
            ['page_key' => 'startseite', 'section_key' => 'ablauf', 'field_key' => 'text', 'value' => 'Drei Schritte, keine Zwischenstellen. Die Koordination läuft im Hintergrund — Sie haben genau einen Ansprechpartner.', 'label_de' => 'Ablauf · Fließtext'],
            ['page_key' => 'startseite', 'section_key' => 'ablauf', 'field_key' => 'schritt_1_titel', 'value' => 'Anfrage stellen', 'label_de' => 'Ablauf · Schritt 1 Titel'],
            ['page_key' => 'startseite', 'section_key' => 'ablauf', 'field_key' => 'schritt_1_text', 'value' => 'Ein kurzes Formular, weniger als zwei Minuten.', 'label_de' => 'Ablauf · Schritt 1 Text'],
            ['page_key' => 'startseite', 'section_key' => 'ablauf', 'field_key' => 'schritt_2_titel', 'value' => 'Automatische Vermittlung', 'label_de' => 'Ablauf · Schritt 2 Titel'],
            ['page_key' => 'startseite', 'section_key' => 'ablauf', 'field_key' => 'schritt_2_text', 'value' => 'Ihre Anfrage geht sofort an passende Sachverständige in Ihrer Region.', 'label_de' => 'Ablauf · Schritt 2 Text'],
            ['page_key' => 'startseite', 'section_key' => 'ablauf', 'field_key' => 'schritt_3_titel', 'value' => 'Sachverständiger übernimmt', 'label_de' => 'Ablauf · Schritt 3 Titel'],
            ['page_key' => 'startseite', 'section_key' => 'ablauf', 'field_key' => 'schritt_3_text', 'value' => 'Der erste verfügbare Partner nimmt den Auftrag an und meldet sich direkt bei Ihnen. Alle anderen sind damit informiert.', 'label_de' => 'Ablauf · Schritt 3 Text'],

            ['page_key' => 'startseite', 'section_key' => 'leistungen', 'field_key' => 'ueberschrift', 'value' => 'Leistungen', 'label_de' => 'Leistungen · Überschrift'],
            ['page_key' => 'startseite', 'section_key' => 'leistungen', 'field_key' => 'text', 'value' => 'Wir vermitteln für alle gängigen Gutachtenarten. Welche Leistung Sie benötigen, geben Sie in der Anfrage an — die Zuordnung übernimmt DKGZ.', 'label_de' => 'Leistungen · Fließtext'],
            ['page_key' => 'ueber-uns', 'section_key' => 'haus', 'field_key' => 'bild', 'type' => 'image', 'value' => '/images/dkgz-haus.svg', 'label_de' => 'Über uns · Gebäude', 'help_de' => 'Platzhaltergrafik. Ersetzen Sie sie durch ein Foto des Büros, Querformat 3:2.'],
            ['page_key' => 'ueber-uns', 'section_key' => 'kopf', 'field_key' => 'eyebrow', 'value' => 'Über DKGZ', 'label_de' => 'Übertitel'],
            ['page_key' => 'ueber-uns', 'section_key' => 'punkte', 'field_key' => 'titel_1', 'value' => 'Kein Vergleichsportal', 'label_de' => 'Punkt 1 · Überschrift'],
            ['page_key' => 'ueber-uns', 'section_key' => 'punkte', 'field_key' => 'text_1', 'type' => 'richtext', 'value' => 'Wir stellen keine Angebote gegenüber. Der erste verfügbare Sachverständige übernimmt, damit Sie nicht warten müssen.', 'label_de' => 'Punkt 1 · Text'],
            ['page_key' => 'ueber-uns', 'section_key' => 'punkte', 'field_key' => 'titel_2', 'value' => 'Kein eigener Gutachter', 'label_de' => 'Punkt 2 · Überschrift'],
            ['page_key' => 'ueber-uns', 'section_key' => 'punkte', 'field_key' => 'text_2', 'type' => 'richtext', 'value' => 'DKGZ erstellt keine Gutachten. Die Begutachtung erbringt der vermittelte Sachverständige in eigener Verantwortung.', 'label_de' => 'Punkt 2 · Text'],
            ['page_key' => 'ueber-uns', 'section_key' => 'punkte', 'field_key' => 'titel_3', 'value' => 'Kostenfrei für Anfragende', 'label_de' => 'Punkt 3 · Überschrift'],
            ['page_key' => 'ueber-uns', 'section_key' => 'punkte', 'field_key' => 'text_3', 'type' => 'richtext', 'value' => 'Die Vermittlung ist für Sie kostenlos. Die Kosten der Begutachtung rechnet der Sachverständige direkt ab.', 'label_de' => 'Punkt 3 · Text', 'help_de' => 'Überschrift und Text leer lassen, um den Punkt wegzulassen.'],

            ['page_key' => 'ueber-uns', 'section_key' => 'partner', 'field_key' => 'ueberschrift', 'value' => 'Sie sind Kfz-Sachverständiger?', 'label_de' => 'Partnerblock · Überschrift', 'help_de' => 'Leer lassen, um den gesamten Block wegzulassen.'],
            ['page_key' => 'ueber-uns', 'section_key' => 'partner', 'field_key' => 'text', 'type' => 'richtext', 'value' => 'Werden Sie Teil des Partnernetzes und erhalten Sie passende Anfragen aus Ihrem Einsatzgebiet — ohne Grundgebühr und ohne Vertragslaufzeit.', 'label_de' => 'Partnerblock · Text'],
            ['page_key' => 'ueber-uns', 'section_key' => 'partner', 'field_key' => 'button', 'value' => 'Zum Partnerbereich', 'label_de' => 'Partnerblock · Schaltfläche'],
            ['page_key' => 'ueber-uns', 'section_key' => 'haus', 'field_key' => 'bildunterschrift', 'value' => 'Die Geschäftsstelle der Deutschen KFZ-Gutachterzentrale', 'label_de' => 'Über uns · Bildunterschrift'],
            ['page_key' => 'ueber-uns', 'section_key' => 'profil', 'field_key' => 'ueberschrift', 'value' => 'Wer hinter DKGZ steht', 'label_de' => 'Über uns · Profil Überschrift'],
            ['page_key' => 'ueber-uns', 'section_key' => 'profil', 'field_key' => 'absatz_1', 'value' => 'Die Deutsche KFZ-Gutachterzentrale koordiniert bundesweit die Vermittlung von Kfz-Sachverständigen. Wir erstellen selbst keine Gutachten. Unsere Aufgabe ist es, eine Anfrage ohne Umwege an einen Sachverständigen zu bringen, der das Fahrzeug tatsächlich begutachten kann — regional zuständig, für die passende Gutachtenart qualifiziert und im Moment der Anfrage verfügbar.', 'label_de' => 'Über uns · Absatz 1'],
            ['page_key' => 'ueber-uns', 'section_key' => 'profil', 'field_key' => 'absatz_2', 'value' => 'Wir sind bewusst kein Vergleichsportal. Wer bei uns anfragt, erhält keine Liste von Angeboten und muss nicht auswählen. Der erste geeignete und verfügbare Partner übernimmt den Auftrag, und ab diesem Moment läuft die Abstimmung direkt zwischen Auftraggeber und Sachverständigem.', 'label_de' => 'Über uns · Absatz 2'],
            ['page_key' => 'ueber-uns', 'section_key' => 'profil', 'field_key' => 'absatz_3', 'value' => 'Jeder Partner in unserem Netz wird vor der Freigabe geprüft: Qualifikation, Berufshaftpflicht und Einsatzgebiet werden hinterlegt und regelmäßig überprüft. Läuft ein Nachweis aus, erhält der Partner keine neuen Anfragen mehr, bis er ihn erneuert hat.', 'label_de' => 'Über uns · Absatz 3'],
            ['page_key' => 'ueber-uns', 'section_key' => 'profil', 'field_key' => 'absatz_4', 'value' => 'Für Auftraggeber ist die Vermittlung kostenfrei und unverbindlich. Es entsteht kein Vertrag mit DKGZ, sondern ausschließlich mit dem Sachverständigen, der den Auftrag annimmt. Über die Plattform werden zu keinem Zeitpunkt Zahlungen abgewickelt.', 'label_de' => 'Über uns · Absatz 4'],
            ['page_key' => 'startseite', 'section_key' => 'abdeckung', 'field_key' => 'ueberschrift', 'value' => 'Wo wir vermitteln', 'label_de' => 'Abdeckung · Überschrift'],
            ['page_key' => 'ueber-uns', 'section_key' => 'haus', 'field_key' => 'bild', 'type' => 'image', 'value' => '/images/dkgz-haus.svg', 'label_de' => 'Über uns · Gebäude', 'help_de' => 'Platzhaltergrafik. Ersetzen Sie sie durch ein Foto des Büros, Querformat 3:2.'],
            ['page_key' => 'ueber-uns', 'section_key' => 'haus', 'field_key' => 'bildunterschrift', 'value' => 'Die Geschäftsstelle der Deutschen KFZ-Gutachterzentrale', 'label_de' => 'Über uns · Bildunterschrift'],
            ['page_key' => 'ueber-uns', 'section_key' => 'profil', 'field_key' => 'ueberschrift', 'value' => 'Wer hinter DKGZ steht', 'label_de' => 'Über uns · Profil Überschrift'],
            ['page_key' => 'ueber-uns', 'section_key' => 'profil', 'field_key' => 'absatz_1', 'value' => 'Die Deutsche KFZ-Gutachterzentrale koordiniert bundesweit die Vermittlung von Kfz-Sachverständigen. Wir erstellen selbst keine Gutachten. Unsere Aufgabe ist es, eine Anfrage ohne Umwege an einen Sachverständigen zu bringen, der das Fahrzeug tatsächlich begutachten kann — regional zuständig, für die passende Gutachtenart qualifiziert und im Moment der Anfrage verfügbar.', 'label_de' => 'Über uns · Absatz 1'],
            ['page_key' => 'ueber-uns', 'section_key' => 'profil', 'field_key' => 'absatz_2', 'value' => 'Wir sind bewusst kein Vergleichsportal. Wer bei uns anfragt, erhält keine Liste von Angeboten und muss nicht auswählen. Der erste geeignete und verfügbare Partner übernimmt den Auftrag, und ab diesem Moment läuft die Abstimmung direkt zwischen Auftraggeber und Sachverständigem.', 'label_de' => 'Über uns · Absatz 2'],
            ['page_key' => 'ueber-uns', 'section_key' => 'profil', 'field_key' => 'absatz_3', 'value' => 'Jeder Partner in unserem Netz wird vor der Freigabe geprüft: Qualifikation, Berufshaftpflicht und Einsatzgebiet werden hinterlegt und regelmäßig überprüft. Läuft ein Nachweis aus, erhält der Partner keine neuen Anfragen mehr, bis er ihn erneuert hat.', 'label_de' => 'Über uns · Absatz 3'],
            ['page_key' => 'ueber-uns', 'section_key' => 'profil', 'field_key' => 'absatz_4', 'value' => 'Für Auftraggeber ist die Vermittlung kostenfrei und unverbindlich. Es entsteht kein Vertrag mit DKGZ, sondern ausschließlich mit dem Sachverständigen, der den Auftrag annimmt. Über die Plattform werden zu keinem Zeitpunkt Zahlungen abgewickelt.', 'label_de' => 'Über uns · Absatz 4'],
            ['page_key' => 'startseite', 'section_key' => 'abdeckung', 'field_key' => 'text', 'value' => 'Die Karte bildet ab, in welchen Postleitzahlregionen derzeit freigegebene Sachverständige zur Verfügung stehen. Sie entsteht aus den Einsatzgebieten unserer Partner und wächst mit dem Netz.', 'label_de' => 'Abdeckung · Text'],
            ['page_key' => 'startseite', 'section_key' => 'leistungen', 'field_key' => 'cta', 'value' => 'Alle Leistungen ansehen', 'label_de' => 'Leistungen · Schaltfläche'],

            ['page_key' => 'startseite', 'section_key' => 'ueber', 'field_key' => 'ueberschrift', 'value' => 'Eine zentrale Stelle, kein Vergleichsportal', 'label_de' => 'Über DKGZ · Überschrift'],
            ['page_key' => 'startseite', 'section_key' => 'ueber', 'field_key' => 'punkt_1_titel', 'value' => 'Ein Ansprechpartner', 'label_de' => 'Über · Punkt 1 Titel'],
            ['page_key' => 'startseite', 'section_key' => 'ueber', 'field_key' => 'punkt_1_text', 'value' => 'Sie stellen eine Anfrage an DKGZ und nicht an zehn Büros. Die Verteilung an geeignete Sachverständige übernimmt die Zentrale.', 'label_de' => 'Über · Punkt 1 Text'],
            ['page_key' => 'startseite', 'section_key' => 'ueber', 'field_key' => 'punkt_2_titel', 'value' => 'Keine Angebotsvergleiche', 'label_de' => 'Über · Punkt 2 Titel'],
            ['page_key' => 'startseite', 'section_key' => 'ueber', 'field_key' => 'punkt_2_text', 'value' => 'Es gibt keine Ausschreibung und keinen Preiswettbewerb. Der erste verfügbare Partner mit passender Qualifikation übernimmt.', 'label_de' => 'Über · Punkt 2 Text'],
            ['page_key' => 'startseite', 'section_key' => 'ueber', 'field_key' => 'punkt_3_titel', 'value' => 'Einheitlicher Standard', 'label_de' => 'Über · Punkt 3 Titel'],
            ['page_key' => 'startseite', 'section_key' => 'ueber', 'field_key' => 'punkt_3_text', 'value' => 'Alle Partner werden vor der Aufnahme geprüft und arbeiten nach denselben Anforderungen — in jedem PLZ-Gebiet gleich.', 'label_de' => 'Über · Punkt 3 Text'],
            ['page_key' => 'startseite', 'section_key' => 'ueber', 'field_key' => 'hinweis', 'value' => 'DKGZ vermittelt Sachverständige und erstellt selbst keine Gutachten. Die Begutachtung erbringt der vermittelte Sachverständige in eigener Verantwortung.', 'label_de' => 'Über · Rechtshinweis'],

            ['page_key' => 'startseite', 'section_key' => 'partner', 'field_key' => 'ueberschrift', 'value' => 'Sie sind Kfz-Sachverständiger?', 'label_de' => 'Für Partner · Überschrift'],
            ['page_key' => 'startseite', 'section_key' => 'partner', 'field_key' => 'punkt_1', 'value' => 'Einsatzgebiet und Leistungen legen Sie selbst fest.', 'label_de' => 'Für Partner · Punkt 1'],
            ['page_key' => 'startseite', 'section_key' => 'partner', 'field_key' => 'punkt_2', 'value' => 'Passende Anfragen erhalten Sie im Portal und per E-Mail.', 'label_de' => 'Für Partner · Punkt 2'],
            ['page_key' => 'startseite', 'section_key' => 'partner', 'field_key' => 'punkt_3', 'value' => 'Jede Anfrage können Sie annehmen oder ablehnen — ohne Begründung.', 'label_de' => 'Für Partner · Punkt 3'],
            ['page_key' => 'startseite', 'section_key' => 'partner', 'field_key' => 'karte_eyebrow', 'value' => 'Partnernetz', 'label_de' => 'Für Partner · Karte Übertitel'],
            ['page_key' => 'startseite', 'section_key' => 'partner', 'field_key' => 'karte_text', 'value' => 'DKGZ berechnet je vermitteltem Auftrag eine feste Gebühr, die von der Gutachtenart abhängt und Ihnen vor der Annahme angezeigt wird. Keine Grundgebühr, keine Kosten für abgelehnte Anfragen.', 'label_de' => 'Für Partner · Karte Text'],
            ['page_key' => 'startseite', 'section_key' => 'partner', 'field_key' => 'cta', 'value' => 'Partner werden', 'label_de' => 'Für Partner · Schaltfläche'],

            ['page_key' => 'startseite', 'section_key' => 'faq', 'field_key' => 'ueberschrift', 'value' => 'Häufige Fragen', 'label_de' => 'FAQ · Überschrift'],
            ['page_key' => 'startseite', 'section_key' => 'faq', 'field_key' => 'text', 'value' => 'Weitere Fragen beantworten wir telefonisch, Mo–Fr von 08:00 bis 18:00 Uhr.', 'label_de' => 'FAQ · Fließtext'],

            ['page_key' => 'startseite', 'section_key' => 'fuss', 'field_key' => 'beschreibung', 'value' => 'Bundesweite Vermittlung von Kfz-Sachverständigen. Eine Anfrage, ein Ansprechpartner, geprüfte Partner in allen PLZ-Gebieten.', 'label_de' => 'Fußbereich · Beschreibung'],
            ['page_key' => 'startseite', 'section_key' => 'fuss', 'field_key' => 'rechtshinweis', 'value' => 'DKGZ ist eine Vermittlungsstelle. Das Gutachten erstellt der vermittelte Sachverständige.', 'label_de' => 'Fußbereich · Rechtshinweis'],
        ];
    }

    /** @return list<array<string, string>> */
    private function requestPage(): array
    {
        return [
            ['page_key' => 'anfrage', 'section_key' => 'kopf', 'field_key' => 'eyebrow', 'value' => 'Kostenlose Anfrage', 'label_de' => 'Übertitel'],
            ['page_key' => 'anfrage', 'section_key' => 'kopf', 'field_key' => 'ueberschrift', 'value' => 'Gutachter anfragen', 'label_de' => 'Überschrift'],
            ['page_key' => 'anfrage', 'section_key' => 'kopf', 'field_key' => 'text', 'value' => 'Zwei Schritte. Ihre Kontaktdaten sieht ausschließlich der Sachverständige, der den Auftrag annimmt.', 'label_de' => 'Fließtext'],
            ['page_key' => 'anfrage', 'section_key' => 'formular', 'field_key' => 'cta_schritt_1', 'value' => 'Jetzt Gutachter anfragen', 'label_de' => 'Schritt 1 · Überschrift des Anfragefelds'],
            ['page_key' => 'anfrage', 'section_key' => 'formular', 'field_key' => 'frage_leistung', 'value' => 'Welche Gutachtenart benötigen Sie?', 'label_de' => 'Schritt 1 · Frage nach der Leistung'],
            ['page_key' => 'anfrage', 'section_key' => 'formular', 'field_key' => 'frage_hinweis', 'value' => 'Wählen Sie die passende Leistung aus, damit wir den richtigen Sachverständigen für Sie finden.', 'label_de' => 'Schritt 1 · Hinweis unter der Frage', 'help_de' => 'Wird durch die Beschreibung der Leistung ersetzt, sobald eine gewählt ist.'],
            ['page_key' => 'anfrage', 'section_key' => 'formular', 'field_key' => 'weiter', 'value' => 'Weiter', 'label_de' => 'Schritt 1 · Schaltfläche'],
            ['page_key' => 'anfrage', 'section_key' => 'formular', 'field_key' => 'hinweis_schritt_1', 'value' => 'Kostenfrei & unverbindlich · Keine Registrierung', 'label_de' => 'Schritt 1 · Hinweis unter der Schaltfläche', 'help_de' => 'Leer lassen, um die Zeile wegzulassen.'],
            ['page_key' => 'anfrage', 'section_key' => 'formular', 'field_key' => 'frage_plz', 'value' => 'Postleitzahl des Fahrzeugstandorts', 'label_de' => 'Schritt 2 · Beschriftung des PLZ-Felds'],
            ['page_key' => 'anfrage', 'section_key' => 'formular', 'field_key' => 'schritt_2_titel', 'value' => 'Fast geschafft!', 'label_de' => 'Schritt 2 · Überschrift'],
            ['page_key' => 'anfrage', 'section_key' => 'formular', 'field_key' => 'schritt_2_text', 'type' => 'richtext', 'value' => 'Bitte vervollständigen Sie Ihre Daten, damit wir einen passenden Gutachter für Sie vermitteln können.', 'label_de' => 'Schritt 2 · Beschreibung'],
            ['page_key' => 'anfrage', 'section_key' => 'formular', 'field_key' => 'cta', 'value' => 'Kostenfrei anfragen', 'label_de' => 'Schritt 2 · Schaltfläche'],
            ['page_key' => 'anfrage', 'section_key' => 'formular', 'field_key' => 'datenschutzhinweis', 'value' => 'Mit dem Absenden willigen Sie ein, dass DKGZ Ihre Angaben zur Vermittlung an geeignete Sachverständige verarbeitet. Ihre Daten gehen ausschließlich an den Sachverständigen, der den Auftrag annimmt.', 'label_de' => 'Einwilligung unter der Schaltfläche', 'help_de' => 'Der Link zur Datenschutzerklärung wird automatisch angehängt.'],
            ['page_key' => 'anfrage', 'section_key' => 'formular', 'field_key' => 'kurzhinweis', 'value' => 'Ihre Anfrage ist kostenfrei und unverbindlich. Es entstehen für Sie keine Kosten.', 'label_de' => 'Zusicherung unter der Schaltfläche', 'help_de' => 'Leer lassen, um die Zeile wegzulassen.'],
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
            ['page_key' => 'bestaetigung', 'section_key' => 'ablauf', 'field_key' => 'schritt_1_text', 'value' => 'Ihre Anfrage geht an alle passenden Sachverständigen, deren Einsatzgebiet :plz abdeckt.', 'label_de' => 'Ablauf · Schritt 1 Text', 'help_de' => ':plz wird durch die Postleitzahl der Anfrage ersetzt.'],
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
            ['page_key' => 'partner', 'section_key' => 'hero', 'field_key' => 'bild', 'type' => 'image', 'value' => '/images/hero-institutionell.svg', 'label_de' => 'Hero · Bild', 'help_de' => 'Platzhaltergrafik im DKGZ-Stil. Ersetzen Sie sie durch ein echtes Foto.'],
            ['page_key' => 'partner', 'section_key' => 'provision', 'field_key' => 'ueberschrift', 'value' => 'Das Gebührenmodell', 'label_de' => 'Provision · Überschrift'],
            ['page_key' => 'partner', 'section_key' => 'provision', 'field_key' => 'text', 'value' => 'Es gibt keine Grundgebühr und keine Kosten pro Anfrage. Je vermitteltem Auftrag berechnet DKGZ einen festen Betrag, der von der Art des Gutachtens abhängt. Der genaue Betrag steht bei jeder Anfrage, bevor Sie sie annehmen.', 'label_de' => 'Provision · Fließtext'],
            ['page_key' => 'partner', 'section_key' => 'provision', 'field_key' => 'punkt_1_titel', 'value' => 'Feste Gebühr je Gutachtenart', 'label_de' => 'Gebühren · Punkt 1 — Titel'],
            ['page_key' => 'partner', 'section_key' => 'provision', 'field_key' => 'punkt_1_text', 'type' => 'richtext', 'value' => 'DKGZ berechnet keinen Prozentsatz Ihres Honorars, sondern einen festen Betrag je vermitteltem Auftrag. Wie hoch er ist, hängt von der Art des Gutachtens ab.', 'label_de' => 'Gebühren · Punkt 1 — Text', 'help_de' => 'Titel und Text leer lassen, um den Punkt wegzulassen.'],
            ['page_key' => 'partner', 'section_key' => 'provision', 'field_key' => 'punkt_2_titel', 'value' => 'Vor Ihrer Entscheidung sichtbar', 'label_de' => 'Gebühren · Punkt 2 — Titel'],
            ['page_key' => 'partner', 'section_key' => 'provision', 'field_key' => 'punkt_2_text', 'type' => 'richtext', 'value' => 'Der genaue Betrag steht bei jeder Anfrage, die Sie erreicht — bevor Sie annehmen. Nach der Annahme ändert er sich für diesen Auftrag nicht mehr.', 'label_de' => 'Gebühren · Punkt 2 — Text', 'help_de' => 'Titel und Text leer lassen, um den Punkt wegzulassen.'],
            ['page_key' => 'partner', 'section_key' => 'provision', 'field_key' => 'punkt_3_titel', 'value' => 'Nur bei Abschluss', 'label_de' => 'Gebühren · Punkt 3 — Titel'],
            ['page_key' => 'partner', 'section_key' => 'provision', 'field_key' => 'punkt_3_text', 'type' => 'richtext', 'value' => 'Keine Grundgebühr, keine Kosten pro Anfrage und keine Berechnung für Anfragen, die Sie ablehnen. Eine Ablehnung wirkt sich nicht auf die weitere Verteilung aus.', 'label_de' => 'Gebühren · Punkt 3 — Text', 'help_de' => 'Titel und Text leer lassen, um den Punkt wegzulassen.'],
            ['page_key' => 'partner', 'section_key' => 'provision', 'field_key' => 'punkt_4_titel', 'value' => 'Abrechnung', 'label_de' => 'Gebühren · Punkt 4 — Titel'],
            ['page_key' => 'partner', 'section_key' => 'provision', 'field_key' => 'punkt_4_text', 'type' => 'richtext', 'value' => 'Monatlich als Sammelrechnung über die im Berichtsmonat abgeschlossenen Aufträge.', 'label_de' => 'Gebühren · Punkt 4 — Text', 'help_de' => 'Titel und Text leer lassen, um den Punkt wegzulassen.'],
            ['page_key' => 'partner', 'section_key' => 'voraussetzungen', 'field_key' => 'ueberschrift', 'value' => 'Voraussetzungen für die Aufnahme', 'label_de' => 'Voraussetzungen · Überschrift'],
            ['page_key' => 'partner', 'section_key' => 'voraussetzungen', 'field_key' => 'punkt_1', 'value' => 'Qualifikationsnachweis als Kfz-Sachverständiger', 'label_de' => 'Voraussetzung 1'],
            ['page_key' => 'partner', 'section_key' => 'voraussetzungen', 'field_key' => 'punkt_2', 'value' => 'Gewerbeanmeldung und USt-IdNr.', 'label_de' => 'Voraussetzung 2'],
            ['page_key' => 'partner', 'section_key' => 'voraussetzungen', 'field_key' => 'punkt_3', 'value' => 'Berufshaftpflichtversicherung', 'label_de' => 'Voraussetzung 3'],
            ['page_key' => 'partner', 'section_key' => 'voraussetzungen', 'field_key' => 'punkt_4', 'value' => 'Definiertes Einsatzgebiet nach PLZ', 'label_de' => 'Voraussetzung 4'],
            ['page_key' => 'partner', 'section_key' => 'voraussetzungen', 'field_key' => 'punkt_5', 'value' => 'Zeitnahe Rückmeldung auf vermittelte Anfragen', 'label_de' => 'Voraussetzung 5'],
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
            ['page_key' => 'ablauf', 'section_key' => 'danach', 'field_key' => 'ueberschrift', 'value' => 'Was danach passiert', 'label_de' => 'Abschlussblock — Überschrift'],
            ['page_key' => 'ablauf', 'section_key' => 'danach', 'field_key' => 'text', 'type' => 'richtext', 'value' => 'Ab der Annahme läuft die Abstimmung unmittelbar zwischen Ihnen und dem Sachverständigen. Er nimmt den Schaden auf, erstellt das Gutachten und rechnet direkt mit Ihnen oder Ihrer Versicherung ab. DKGZ tritt dabei nicht mehr dazwischen.', 'label_de' => 'Abschlussblock — Fließtext'],
            ['page_key' => 'ablauf', 'section_key' => 'danach', 'field_key' => 'button', 'value' => 'Anfrage starten', 'label_de' => 'Abschlussblock — Schaltfläche'],
        ];
    }

    /**
     * The FAQ page, which the header now points at.
     *
     * @return list<array<string, string>>
     */
    private function faqPage(): array
    {
        return [
            ['page_key' => 'faq', 'section_key' => 'kopf', 'field_key' => 'eyebrow', 'value' => 'Fragen und Antworten', 'label_de' => 'Übertitel'],
            ['page_key' => 'faq', 'section_key' => 'kopf', 'field_key' => 'ueberschrift', 'value' => 'Häufige Fragen', 'label_de' => 'Überschrift'],
            ['page_key' => 'faq', 'section_key' => 'kopf', 'field_key' => 'text', 'type' => 'richtext', 'value' => 'Wenn Ihre Frage hier nicht beantwortet wird, rufen Sie uns gern an — wir helfen Ihnen weiter.', 'label_de' => 'Einleitung'],
            ['page_key' => 'faq', 'section_key' => 'kopf', 'field_key' => 'leer', 'value' => 'Es sind noch keine Fragen hinterlegt.', 'label_de' => 'Text ohne Fragen'],
            ['page_key' => 'faq', 'section_key' => 'kopf', 'field_key' => 'meta_titel', 'value' => 'Häufige Fragen — DKGZ', 'label_de' => 'Seitentitel für Google'],
            ['page_key' => 'faq', 'section_key' => 'kopf', 'field_key' => 'meta_text', 'type' => 'richtext', 'value' => 'Antworten auf die häufigsten Fragen zur Vermittlung von Kfz-Sachverständigen durch DKGZ.', 'label_de' => 'Beschreibung für Google'],
            ['page_key' => 'faq', 'section_key' => 'abschluss', 'field_key' => 'ueberschrift', 'value' => 'Frage nicht dabei?', 'label_de' => 'Abschlussblock — Überschrift'],
            ['page_key' => 'faq', 'section_key' => 'abschluss', 'field_key' => 'text', 'type' => 'richtext', 'value' => 'Stellen Sie einfach Ihre Anfrage — der vermittelte Sachverständige klärt alles Weitere direkt mit Ihnen.', 'label_de' => 'Abschlussblock — Text'],
            ['page_key' => 'faq', 'section_key' => 'abschluss', 'field_key' => 'button', 'value' => 'Anfrage starten', 'label_de' => 'Abschlussblock — Schaltfläche'],
        ];
    }

    /**
     * Copy shared by every city page.
     *
     * The parts that do not change from city to city — the steps, the button,
     * the wording on the overview. Anything specific to a place lives on the
     * city itself.
     *
     * @return list<array<string, string>>
     */
    private function cityPages(): array
    {
        return [
            ['page_key' => 'staedte', 'section_key' => 'uebersicht', 'field_key' => 'eyebrow', 'value' => 'Standorte', 'label_de' => 'Übersicht · Übertitel'],
            ['page_key' => 'staedte', 'section_key' => 'uebersicht', 'field_key' => 'ueberschrift', 'value' => 'Kfz-Gutachter in Ihrer Stadt', 'label_de' => 'Übersicht · Überschrift'],
            ['page_key' => 'staedte', 'section_key' => 'uebersicht', 'field_key' => 'text', 'type' => 'richtext', 'value' => 'Wählen Sie Ihren Standort. Wir vermitteln in ganz Deutschland — auch dort, wo noch keine eigene Seite besteht.', 'label_de' => 'Übersicht · Text'],
            ['page_key' => 'staedte', 'section_key' => 'uebersicht', 'field_key' => 'leer', 'type' => 'richtext', 'value' => 'Es sind noch keine Standorte hinterlegt. Eine Anfrage können Sie trotzdem jederzeit stellen — wir vermitteln bundesweit.', 'label_de' => 'Übersicht · Text ohne Städte'],
            ['page_key' => 'staedte', 'section_key' => 'uebersicht', 'field_key' => 'meta_titel', 'value' => 'Kfz-Gutachter nach Stadt — Standorte | DKGZ', 'label_de' => 'Übersicht · Seitentitel für Google'],
            ['page_key' => 'staedte', 'section_key' => 'uebersicht', 'field_key' => 'meta_text', 'type' => 'richtext', 'value' => 'Geprüfte Kfz-Sachverständige in Ihrer Stadt finden. DKGZ vermittelt bundesweit, kostenlos und unverbindlich.', 'label_de' => 'Übersicht · Beschreibung für Google'],

            ['page_key' => 'staedte', 'section_key' => 'stadt', 'field_key' => 'ueberschrift', 'value' => 'Kfz-Gutachter in {stadt}', 'label_de' => 'Stadtseite · Überschrift (H1)', 'help_de' => '{stadt} wird durch den Namen der Stadt ersetzt. Gilt für Städte ohne eigene Überschrift.'],
            ['page_key' => 'staedte', 'section_key' => 'stadt', 'field_key' => 'text', 'type' => 'richtext', 'value' => 'Sie brauchen ein Gutachten in {stadt}? Wir vermitteln Ihnen einen geprüften Kfz-Sachverständigen aus {stadt} und Umgebung — kostenlos und unverbindlich.', 'label_de' => 'Stadtseite · Einleitung', 'help_de' => 'Gilt für Städte ohne eigenen Einleitungstext. {stadt} wird ersetzt.'],
            ['page_key' => 'staedte', 'section_key' => 'stadt', 'field_key' => 'leistungen_ueberschrift', 'value' => 'Gutachten in {stadt}', 'label_de' => 'Stadtseite · Überschrift Leistungen'],
            ['page_key' => 'staedte', 'section_key' => 'stadt', 'field_key' => 'einleitung_ueberschrift', 'value' => 'Kfz-Gutachten in {stadt} — worauf es ankommt', 'label_de' => 'Stadtseite · Überschrift Einleitung'],
            ['page_key' => 'staedte', 'section_key' => 'stadt', 'field_key' => 'einleitung_text', 'type' => 'richtext', 'value' => 'Nach einem Unfall in {stadt} haben Sie das Recht, einen eigenen Kfz-Sachverständigen zu beauftragen. Er dokumentiert den Schaden, ermittelt Reparaturkosten und Wertminderung und erstellt ein Gutachten, das gegenüber der Versicherung Bestand hat. Bei einem unverschuldeten Unfall trägt in der Regel die gegnerische Haftpflichtversicherung die Kosten.', 'label_de' => 'Stadtseite · Einleitungstext', 'help_de' => 'Gilt für alle Städte ohne eigenen Text. {stadt} wird ersetzt.'],
            ['page_key' => 'staedte', 'section_key' => 'stadt', 'field_key' => 'ablauf_ueberschrift', 'value' => 'So kommen Sie in {stadt} zu Ihrem Gutachten', 'label_de' => 'Stadtseite · Überschrift Ablauf'],
            ['page_key' => 'staedte', 'section_key' => 'stadt', 'field_key' => 'schritt_1', 'type' => 'richtext', 'value' => 'Sie stellen eine kostenlose Anfrage mit der Art des Gutachtens und dem Standort des Fahrzeugs in {stadt}.', 'label_de' => 'Stadtseite · Schritt 1'],
            ['page_key' => 'staedte', 'section_key' => 'stadt', 'field_key' => 'schritt_2', 'type' => 'richtext', 'value' => 'Wir leiten sie an geprüfte Sachverständige weiter, deren Einsatzgebiet {stadt} abdeckt.', 'label_de' => 'Stadtseite · Schritt 2'],
            ['page_key' => 'staedte', 'section_key' => 'stadt', 'field_key' => 'schritt_3', 'type' => 'richtext', 'value' => 'Der erste verfügbare Sachverständige übernimmt und meldet sich direkt bei Ihnen — in der Regel noch am selben Werktag.', 'label_de' => 'Stadtseite · Schritt 3'],
            ['page_key' => 'staedte', 'section_key' => 'stadt', 'field_key' => 'hinweise_ueberschrift', 'value' => 'Gut zu wissen', 'label_de' => 'Stadtseite · Überschrift Hinweise'],
            ['page_key' => 'staedte', 'section_key' => 'stadt', 'field_key' => 'hinweis_1_titel', 'value' => 'Freie Gutachterwahl', 'label_de' => 'Stadtseite · Hinweis 1 Titel'],
            ['page_key' => 'staedte', 'section_key' => 'stadt', 'field_key' => 'hinweis_1_text', 'type' => 'richtext', 'value' => 'Sie müssen nicht den Sachverständigen nehmen, den die Versicherung vorschlägt. Die Wahl liegt bei Ihnen.', 'label_de' => 'Stadtseite · Hinweis 1 Text'],
            ['page_key' => 'staedte', 'section_key' => 'stadt', 'field_key' => 'hinweis_2_titel', 'value' => 'Vor der Reparatur begutachten lassen', 'label_de' => 'Stadtseite · Hinweis 2 Titel'],
            ['page_key' => 'staedte', 'section_key' => 'stadt', 'field_key' => 'hinweis_2_text', 'type' => 'richtext', 'value' => 'Ist der Schaden erst repariert, lässt er sich nicht mehr dokumentieren. Beauftragen Sie das Gutachten daher vor der Werkstatt.', 'label_de' => 'Stadtseite · Hinweis 2 Text'],
            ['page_key' => 'staedte', 'section_key' => 'stadt', 'field_key' => 'hinweis_3_titel', 'value' => 'Bagatellgrenze beachten', 'label_de' => 'Stadtseite · Hinweis 3 Titel'],
            ['page_key' => 'staedte', 'section_key' => 'stadt', 'field_key' => 'hinweis_3_text', 'type' => 'richtext', 'value' => 'Unterhalb von rund 750 Euro Schadenhöhe genügt meist ein Kostenvoranschlag. Darüber ist ein Gutachten der sichere Weg.', 'label_de' => 'Stadtseite · Hinweis 3 Text', 'help_de' => 'Titel und Text leer lassen, um den Hinweis wegzulassen.'],
            ['page_key' => 'staedte', 'section_key' => 'stadt', 'field_key' => 'faq_ueberschrift', 'value' => 'Häufige Fragen zum Kfz-Gutachten in {stadt}', 'label_de' => 'Stadtseite · Überschrift Fragen'],
            ['page_key' => 'staedte', 'section_key' => 'stadt', 'field_key' => 'faq_1_frage', 'value' => 'Was kostet ein Kfz-Gutachten in {stadt}?', 'label_de' => 'Stadtseite · Frage 1'],
            ['page_key' => 'staedte', 'section_key' => 'stadt', 'field_key' => 'faq_1_antwort', 'type' => 'richtext', 'value' => 'Die Vermittlung über DKGZ ist für Sie kostenfrei. Die Kosten des Gutachtens richten sich nach der Schadenhöhe und werden bei einem unverschuldeten Unfall in der Regel von der gegnerischen Haftpflichtversicherung getragen.', 'label_de' => 'Stadtseite · Antwort 1'],
            ['page_key' => 'staedte', 'section_key' => 'stadt', 'field_key' => 'faq_2_frage', 'value' => 'Wie schnell bekomme ich in {stadt} einen Termin?', 'label_de' => 'Stadtseite · Frage 2'],
            ['page_key' => 'staedte', 'section_key' => 'stadt', 'field_key' => 'faq_2_antwort', 'type' => 'richtext', 'value' => 'Sobald ein Sachverständiger die Anfrage annimmt, meldet er sich direkt bei Ihnen — meist innerhalb eines Werktages. Den Termin stimmen Sie unmittelbar mit ihm ab.', 'label_de' => 'Stadtseite · Antwort 2'],
            ['page_key' => 'staedte', 'section_key' => 'stadt', 'field_key' => 'faq_3_frage', 'value' => 'Kommt der Sachverständige zu mir?', 'label_de' => 'Stadtseite · Frage 3'],
            ['page_key' => 'staedte', 'section_key' => 'stadt', 'field_key' => 'faq_3_antwort', 'type' => 'richtext', 'value' => 'In der Regel ja. Die Begutachtung findet dort statt, wo das Fahrzeug steht — zu Hause, in der Werkstatt oder am Unfallort in {stadt} und Umgebung.', 'label_de' => 'Stadtseite · Antwort 3', 'help_de' => 'Frage und Antwort leer lassen, um sie wegzulassen.'],

            ['page_key' => 'staedte', 'section_key' => 'stadt', 'field_key' => 'cta', 'value' => 'Jetzt Gutachter anfragen', 'label_de' => 'Stadtseite · Schaltfläche'],
            ['page_key' => 'staedte', 'section_key' => 'stadt', 'field_key' => 'meta_titel', 'value' => 'Kfz-Gutachter {stadt} — Sachverständigen finden | DKGZ', 'label_de' => 'Stadtseite · Seitentitel für Google', 'help_de' => 'Gilt für Städte ohne eigenen Meta-Titel.'],
            ['page_key' => 'staedte', 'section_key' => 'stadt', 'field_key' => 'meta_text', 'type' => 'richtext', 'value' => 'Kfz-Gutachter in {stadt} gesucht? DKGZ vermittelt Ihnen einen geprüften Sachverständigen in {stadt} und Umgebung. Kostenlos und unverbindlich.', 'label_de' => 'Stadtseite · Beschreibung für Google'],

            ['page_key' => 'verzeichnis', 'section_key' => 'kopf', 'field_key' => 'eyebrow', 'value' => 'Partnernetz', 'label_de' => 'Übertitel'],
            ['page_key' => 'verzeichnis', 'section_key' => 'kopf', 'field_key' => 'ueberschrift', 'value' => 'Geprüfte Kfz-Sachverständige', 'label_de' => 'Überschrift (H1)'],
            ['page_key' => 'verzeichnis', 'section_key' => 'kopf', 'field_key' => 'text', 'type' => 'richtext', 'value' => 'Jeder Partner in diesem Verzeichnis wurde vor der Freigabe geprüft. Die Anfrage läuft über DKGZ und ist für Sie kostenfrei.', 'label_de' => 'Fließtext'],
            ['page_key' => 'verzeichnis', 'section_key' => 'kopf', 'field_key' => 'leer', 'value' => 'Für diese Auswahl ist derzeit kein Sachverständiger gelistet.', 'label_de' => 'Text, wenn die Auswahl leer ist'],
            ['page_key' => 'verzeichnis', 'section_key' => 'kopf', 'field_key' => 'meta_titel', 'value' => 'Kfz-Sachverständige im DKGZ-Netz | DKGZ', 'label_de' => 'Seitentitel für Google'],
            ['page_key' => 'verzeichnis', 'section_key' => 'kopf', 'field_key' => 'meta_text', 'type' => 'richtext', 'value' => 'Geprüfte Kfz-Sachverständige aus ganz Deutschland. Anfrage direkt über DKGZ — kostenfrei und unverbindlich.', 'label_de' => 'Beschreibung für Google'],
            ['page_key' => 'verzeichnis', 'section_key' => 'profil', 'field_key' => 'ueberschrift', 'value' => 'Über diesen Sachverständigen', 'label_de' => 'Profil · Überschrift Vorstellung'],
            ['page_key' => 'verzeichnis', 'section_key' => 'profil', 'field_key' => 'leistungen', 'value' => 'Angebotene Leistungen', 'label_de' => 'Profil · Überschrift Leistungen'],
            ['page_key' => 'verzeichnis', 'section_key' => 'profil', 'field_key' => 'qualifikation', 'value' => 'Qualifikation', 'label_de' => 'Profil · Überschrift Qualifikation'],
            ['page_key' => 'verzeichnis', 'section_key' => 'profil', 'field_key' => 'cta_titel', 'value' => 'Diesen Sachverständigen anfragen', 'label_de' => 'Profil · Überschrift Anfragebox'],
            ['page_key' => 'verzeichnis', 'section_key' => 'profil', 'field_key' => 'cta_text', 'type' => 'richtext', 'value' => 'Ihre Anfrage geht ausschließlich an diesen Sachverständigen — nicht an andere Partner.', 'label_de' => 'Profil · Text der Anfragebox'],
            ['page_key' => 'verzeichnis', 'section_key' => 'profil', 'field_key' => 'cta', 'value' => 'Gutachter anfragen', 'label_de' => 'Profil · Schaltfläche'],
            ['page_key' => 'verzeichnis', 'section_key' => 'profil', 'field_key' => 'formular_titel', 'value' => 'Anfrage an', 'label_de' => 'Profil · Überschrift des Formulars', 'help_de' => 'Der Name des Sachverständigen wird angehängt.'],
            ['page_key' => 'verzeichnis', 'section_key' => 'profil', 'field_key' => 'absenden', 'value' => 'Kostenfrei anfragen', 'label_de' => 'Profil · Schaltfläche im Formular'],
            ['page_key' => 'verzeichnis', 'section_key' => 'profil', 'field_key' => 'datenschutzhinweis', 'type' => 'richtext', 'value' => 'Mit dem Absenden willigen Sie ein, dass DKGZ Ihre Angaben an diesen Sachverständigen weitergibt.', 'label_de' => 'Profil · Einwilligung unter der Schaltfläche'],

            ['page_key' => 'ratgeber', 'section_key' => 'kopf', 'field_key' => 'eyebrow', 'value' => 'Ratgeber', 'label_de' => 'Übertitel'],
            ['page_key' => 'ratgeber', 'section_key' => 'kopf', 'field_key' => 'ueberschrift', 'value' => 'Gutachten verstehen', 'label_de' => 'Überschrift (H1)'],
            ['page_key' => 'ratgeber', 'section_key' => 'kopf', 'field_key' => 'text', 'type' => 'richtext', 'value' => 'Was nach einem Unfall zu tun ist, wer das Gutachten bezahlt und worauf es dabei ankommt — verständlich erklärt.', 'label_de' => 'Fließtext'],
            ['page_key' => 'ratgeber', 'section_key' => 'kopf', 'field_key' => 'leer', 'value' => 'Hier erscheinen in Kürze die ersten Beiträge.', 'label_de' => 'Text, solange nichts veröffentlicht ist'],
            ['page_key' => 'ratgeber', 'section_key' => 'kopf', 'field_key' => 'meta_titel', 'value' => 'Ratgeber — Kfz-Gutachten verstehen | DKGZ', 'label_de' => 'Seitentitel für Google'],
            ['page_key' => 'ratgeber', 'section_key' => 'kopf', 'field_key' => 'meta_text', 'type' => 'richtext', 'value' => 'Antworten auf die Fragen, die sich nach einem Unfall zuerst stellen — von der Deutschen Kfz-Gutachterzentrale.', 'label_de' => 'Beschreibung für Google'],
            ['page_key' => 'ratgeber', 'section_key' => 'cta', 'field_key' => 'ueberschrift', 'value' => 'Sie brauchen ein Gutachten?', 'label_de' => 'Abschlussblock — Überschrift'],
            ['page_key' => 'ratgeber', 'section_key' => 'cta', 'field_key' => 'text', 'type' => 'richtext', 'value' => 'Wir vermitteln Ihnen einen geprüften Kfz-Sachverständigen in Ihrer Region. Kostenfrei und unverbindlich.', 'label_de' => 'Abschlussblock — Fließtext'],
            ['page_key' => 'ratgeber', 'section_key' => 'cta', 'field_key' => 'button', 'value' => 'Jetzt Gutachter anfragen', 'label_de' => 'Abschlussblock — Schaltfläche'],
            ['page_key' => 'ratgeber', 'section_key' => 'weiter', 'field_key' => 'ueberschrift', 'value' => 'Weitere Beiträge', 'label_de' => 'Beitragsseite — Überschrift weitere Beiträge'],

            ['page_key' => 'staedte', 'section_key' => 'leistung', 'field_key' => 'ueberschrift', 'value' => '{leistung} in {stadt}', 'label_de' => 'Leistungsseite · Überschrift (H1)', 'help_de' => '{leistung} und {stadt} werden ersetzt. Artikel mit in die Klammer nehmen, dann wird er an die Leistung angepasst: {zum leistung} wird zu „zum Unfallgutachten“ und zu „zur Beweissicherung“. In der Klammer immer die männliche Form schreiben — {den leistung}, {dem leistung}, {einen leistung}.'],
            ['page_key' => 'staedte', 'section_key' => 'leistung', 'field_key' => 'einleitung', 'type' => 'richtext', 'value' => 'Sie benötigen {einen leistung} in {stadt}? Wir vermitteln Ihnen einen geprüften Kfz-Sachverständigen aus der Region — in der Regel noch am selben Werktag.', 'label_de' => 'Leistungsseite · Einleitung', 'help_de' => 'Gilt für Städte ohne eigenen Einleitungstext.'],
            ['page_key' => 'staedte', 'section_key' => 'leistung', 'field_key' => 'cta', 'value' => 'Jetzt Gutachter anfragen', 'label_de' => 'Leistungsseite · Schaltfläche'],
            ['page_key' => 'staedte', 'section_key' => 'leistung', 'field_key' => 'seitenleiste_titel', 'value' => '{leistung} in {stadt} anfragen', 'label_de' => 'Leistungsseite — Titel Seitenspalte'],
            ['page_key' => 'staedte', 'section_key' => 'leistung', 'field_key' => 'punkt_1', 'value' => 'Anfrage und Vermittlung kostenfrei', 'label_de' => 'Leistungsseite — Zusicherung 1'],
            ['page_key' => 'staedte', 'section_key' => 'leistung', 'field_key' => 'punkt_2', 'value' => 'Keine Registrierung nötig', 'label_de' => 'Leistungsseite — Zusicherung 2'],
            ['page_key' => 'staedte', 'section_key' => 'leistung', 'field_key' => 'punkt_3', 'value' => 'Sachverständige aus {stadt} und Umgebung', 'label_de' => 'Leistungsseite — Zusicherung 3'],
            ['page_key' => 'staedte', 'section_key' => 'leistung', 'field_key' => 'faq_ueberschrift', 'value' => 'Häufige Fragen {zum leistung}', 'label_de' => 'Leistungsseite — Überschrift Fragen', 'help_de' => 'Artikel mit in die Klammer nehmen, dann wird er an die Leistung angepasst: {zum leistung} wird zu „zum Unfallgutachten“ und zu „zur Beweissicherung“. In der Klammer immer die männliche Form schreiben — {den leistung}, {dem leistung}, {einen leistung}.'],
            ['page_key' => 'staedte', 'section_key' => 'leistung', 'field_key' => 'ablauf_ueberschrift', 'value' => 'So kommen Sie {zum leistung} in {stadt}', 'label_de' => 'Leistungsseite · Überschrift Ablauf', 'help_de' => 'Artikel mit in die Klammer nehmen, dann wird er an die Leistung angepasst: {zum leistung} wird zu „zum Unfallgutachten“ und zu „zur Beweissicherung“. In der Klammer immer die männliche Form schreiben — {den leistung}, {dem leistung}, {einen leistung}.'],
            ['page_key' => 'staedte', 'section_key' => 'leistung', 'field_key' => 'schritt_1', 'type' => 'richtext', 'value' => 'Sie stellen eine kostenlose Anfrage mit Standort in {stadt}, Fahrzeug und Art des Gutachtens.', 'label_de' => 'Leistungsseite · Schritt 1'],
            ['page_key' => 'staedte', 'section_key' => 'leistung', 'field_key' => 'schritt_2', 'type' => 'richtext', 'value' => 'Wir leiten sie an geprüfte Sachverständige weiter, deren Einsatzgebiet {stadt} abdeckt.', 'label_de' => 'Leistungsseite · Schritt 2'],
            ['page_key' => 'staedte', 'section_key' => 'leistung', 'field_key' => 'schritt_3', 'type' => 'richtext', 'value' => 'Der erste verfügbare Sachverständige übernimmt {den leistung} und meldet sich direkt bei Ihnen.', 'label_de' => 'Leistungsseite · Schritt 3'],
            ['page_key' => 'staedte', 'section_key' => 'leistung', 'field_key' => 'weitere_leistungen', 'value' => 'Weitere Leistungen in {stadt}', 'label_de' => 'Leistungsseite · Überschrift weitere Leistungen'],
            ['page_key' => 'staedte', 'section_key' => 'leistung', 'field_key' => 'meta_titel', 'value' => '{leistung} in {stadt} — Kfz-Sachverständigen finden | DKGZ', 'label_de' => 'Leistungsseite · Seitentitel für Google'],
            ['page_key' => 'staedte', 'section_key' => 'leistung', 'field_key' => 'meta_text', 'type' => 'richtext', 'value' => '{leistung} in {stadt} gesucht? DKGZ vermittelt Ihnen einen geprüften Kfz-Sachverständigen in {stadt} und Umgebung. Kostenlos, unverbindlich und ohne Registrierung.', 'label_de' => 'Leistungsseite · Beschreibung für Google'],
        ];
    }

    /**
     * The consent banner.
     *
     * It appears on every page and belongs to no page, so it has a section of
     * its own rather than hiding under the homepage where nobody would look for
     * it. Wording that asks for permission is exactly the wording an operator
     * needs to be able to correct.
     *
     * @return list<array<string, string>>
     */
    private function cookieBanner(): array
    {
        return [
            ['page_key' => 'cookies', 'section_key' => 'banner', 'field_key' => 'titel', 'value' => 'Statistik-Cookies', 'label_de' => 'Überschrift'],
            ['page_key' => 'cookies', 'section_key' => 'banner', 'field_key' => 'text', 'type' => 'richtext', 'value' => 'Wir möchten anonym messen, wie diese Seite genutzt wird. Für den Betrieb notwendige Cookies setzen wir ohnehin — dafür ist keine Zustimmung nötig. Sie können jederzeit widersprechen.', 'label_de' => 'Text', 'help_de' => 'Der Link zur Datenschutzerklärung wird automatisch angehängt.'],
            ['page_key' => 'cookies', 'section_key' => 'banner', 'field_key' => 'link', 'value' => 'Datenschutzerklärung', 'label_de' => 'Linktext zur Datenschutzerklärung'],
            ['page_key' => 'cookies', 'section_key' => 'banner', 'field_key' => 'button_ablehnen', 'value' => 'Nur notwendige', 'label_de' => 'Schaltfläche — ablehnen'],
            ['page_key' => 'cookies', 'section_key' => 'banner', 'field_key' => 'button_annehmen', 'value' => 'Einverstanden', 'label_de' => 'Schaltfläche — zustimmen'],
        ];
    }

    /**
     * The wording on the DKGZ invoice.
     *
     * The PDF was fixed text in a Blade file, so changing a payment term or the
     * VAT sentence meant a deployment. It is a document a business has to be
     * able to correct itself.
     *
     * @return list<array<string, string>>
     */
    private function invoiceDocument(): array
    {
        return [
            ['page_key' => 'rechnung', 'section_key' => 'absender', 'field_key' => 'ueberschrift', 'value' => 'Rechnungssteller', 'label_de' => 'Überschrift Absenderblock'],
            ['page_key' => 'rechnung', 'section_key' => 'absender', 'field_key' => 'anschrift', 'type' => 'richtext', 'value' => '', 'label_de' => 'Absenderanschrift', 'help_de' => 'Firma, Straße, PLZ und Ort — je Zeile eine Angabe. Leer lassen, um die Angaben aus Einstellungen → Kontakt zu verwenden.'],
            ['page_key' => 'rechnung', 'section_key' => 'absender', 'field_key' => 'steuer', 'value' => '', 'label_de' => 'Steuernummer / USt-IdNr.', 'help_de' => 'Erscheint klein unter der Anschrift. Leer lassen, um die USt-IdNr. aus Einstellungen → Kontakt zu verwenden.'],
            ['page_key' => 'rechnung', 'section_key' => 'fuss', 'field_key' => 'zeile', 'type' => 'richtext', 'value' => '', 'label_de' => 'Fußzeile mit Rechtsangaben', 'help_de' => 'Geschäftsführung, Registergericht, Registernummer, Bankverbindung. Leer lassen, um die Angaben aus Einstellungen → Kontakt zu verwenden.'],
            ['page_key' => 'rechnung', 'section_key' => 'kopf', 'field_key' => 'titel', 'value' => 'Provisionsabrechnung', 'label_de' => 'Titel oben rechts'],
            ['page_key' => 'rechnung', 'section_key' => 'kopf', 'field_key' => 'einleitung', 'type' => 'richtext', 'value' => 'für die Vermittlung des unten genannten Vorgangs berechnen wir die vereinbarte Vermittlungsgebühr.', 'label_de' => 'Einleitungssatz', 'help_de' => 'Steht über den Angaben zum Vorgang. Leer lassen, um ihn wegzulassen.'],
            ['page_key' => 'rechnung', 'section_key' => 'vorgang', 'field_key' => 'ueberschrift', 'value' => 'Abgerechneter Vorgang', 'label_de' => 'Überschrift Vorgangsdaten'],
            ['page_key' => 'rechnung', 'section_key' => 'posten', 'field_key' => 'ueberschrift', 'value' => 'Leistung', 'label_de' => 'Überschrift Leistung'],
            ['page_key' => 'rechnung', 'section_key' => 'posten', 'field_key' => 'bezeichnung', 'value' => 'Vermittlungsgebühr', 'label_de' => 'Bezeichnung der Leistung'],
            ['page_key' => 'rechnung', 'section_key' => 'posten', 'field_key' => 'summe', 'value' => 'Rechnungsbetrag', 'label_de' => 'Bezeichnung der Summe'],
            ['page_key' => 'rechnung', 'section_key' => 'steuer', 'field_key' => 'hinweis', 'type' => 'richtext', 'value' => 'Der Betrag ist umsatzsteuerfrei nach § 19 UStG (Kleinunternehmerregelung).', 'label_de' => 'Steuerhinweis', 'help_de' => 'Bitte an Ihre tatsächliche steuerliche Situation anpassen. Leer lassen, um ihn wegzulassen.'],
            ['page_key' => 'rechnung', 'section_key' => 'zahlung', 'field_key' => 'ueberschrift', 'value' => 'Zahlung', 'label_de' => 'Überschrift Zahlungshinweis'],
            ['page_key' => 'rechnung', 'section_key' => 'zahlung', 'field_key' => 'hinweis', 'type' => 'richtext', 'value' => 'Zahlbar innerhalb von 14 Tagen ohne Abzug.', 'label_de' => 'Zahlungshinweis', 'help_de' => 'Zahlungsziel und Bankverbindung. Leer lassen, um den Abschnitt wegzulassen.'],
        ];
    }

    /**
     * The services index and every service detail page.
     *
     * The index used to read the homepage's blocks and the detail page read
     * none at all, so editing either one in the admin panel changed nothing on
     * the site. Both now address their own copy here.
     *
     * @return list<array<string, string>>
     */
    private function servicesPage(): array
    {
        return [
            ['page_key' => 'leistungen', 'section_key' => 'kopf', 'field_key' => 'ueberschrift', 'value' => 'Welches Gutachten benötigen Sie?', 'label_de' => 'Überschrift'],
            ['page_key' => 'leistungen', 'section_key' => 'kopf', 'field_key' => 'text', 'type' => 'richtext', 'value' => 'Wir vermitteln für jede dieser Leistungen einen geprüften Sachverständigen aus Ihrer Region. Die Anfrage ist in jedem Fall kostenfrei.', 'label_de' => 'Fließtext'],
            ['page_key' => 'leistungen', 'section_key' => 'liste', 'field_key' => 'link', 'value' => 'Gutachter finden', 'label_de' => 'Kachel — Linktext'],
            ['page_key' => 'leistungen', 'section_key' => 'hilfe', 'field_key' => 'ueberschrift', 'value' => 'Nicht sicher, was Sie brauchen?', 'label_de' => 'Hinweisblock — Überschrift'],
            ['page_key' => 'leistungen', 'section_key' => 'hilfe', 'field_key' => 'text', 'type' => 'richtext', 'value' => 'Beschreiben Sie in der Anfrage kurz, was passiert ist. Der vermittelte Sachverständige ordnet den Fall ein und sagt Ihnen, welches Gutachten in Ihrem Fall das richtige ist.', 'label_de' => 'Hinweisblock — Fließtext'],
            ['page_key' => 'leistungen', 'section_key' => 'hilfe', 'field_key' => 'button', 'value' => 'Anfrage starten', 'label_de' => 'Hinweisblock — Schaltfläche'],

            ['page_key' => 'leistungen', 'section_key' => 'detail', 'field_key' => 'button_oben', 'value' => 'Gutachter finden', 'label_de' => 'Detailseite — Schaltfläche oben'],
            ['page_key' => 'leistungen', 'section_key' => 'detail', 'field_key' => 'ablauf_ueberschrift', 'value' => 'So kommen Sie {zum leistung}', 'label_de' => 'Detailseite — Überschrift Ablauf', 'help_de' => 'Artikel mit in die Klammer nehmen, dann wird er an die Leistung angepasst: {zum leistung} wird zu „zum Unfallgutachten“ und zu „zur Beweissicherung“. In der Klammer immer die männliche Form schreiben — {den leistung}, {dem leistung}, {einen leistung}.'],
            ['page_key' => 'leistungen', 'section_key' => 'detail', 'field_key' => 'faq_ueberschrift', 'value' => 'Häufige Fragen {zum leistung}', 'label_de' => 'Detailseite — Überschrift Fragen', 'help_de' => 'Artikel mit in die Klammer nehmen, dann wird er an die Leistung angepasst: {zum leistung} wird zu „zum Unfallgutachten“ und zu „zur Beweissicherung“. In der Klammer immer die männliche Form schreiben — {den leistung}, {dem leistung}, {einen leistung}.'],
            ['page_key' => 'leistungen', 'section_key' => 'detail', 'field_key' => 'punkt_1', 'value' => 'Anfrage und Vermittlung kostenfrei', 'label_de' => 'Detailseite — Zusicherung 1'],
            ['page_key' => 'leistungen', 'section_key' => 'detail', 'field_key' => 'punkt_2', 'value' => 'Keine Registrierung nötig', 'label_de' => 'Detailseite — Zusicherung 2'],
            ['page_key' => 'leistungen', 'section_key' => 'detail', 'field_key' => 'punkt_3', 'value' => 'Geprüfte Partner bundesweit', 'label_de' => 'Detailseite — Zusicherung 3'],
            ['page_key' => 'leistungen', 'section_key' => 'detail', 'field_key' => 'button_seite', 'value' => 'Anfrage starten', 'label_de' => 'Detailseite — Schaltfläche Seitenspalte'],
            ['page_key' => 'leistungen', 'section_key' => 'detail', 'field_key' => 'weitere', 'value' => 'Weitere Leistungen', 'label_de' => 'Detailseite — Überschrift weitere Leistungen'],
            ['page_key' => 'leistungen', 'section_key' => 'detail', 'field_key' => 'eyebrow', 'value' => 'Leistung', 'label_de' => 'Detailseite — Übertitel'],
            ['page_key' => 'leistungen', 'section_key' => 'detail', 'field_key' => 'ueberschrift', 'value' => '{leistung}', 'label_de' => 'Detailseite — Überschrift (H1)', 'help_de' => '{leistung} wird durch den Namen der Leistung ersetzt.'],
            ['page_key' => 'leistungen', 'section_key' => 'detail', 'field_key' => 'cta', 'value' => 'Jetzt Gutachter anfragen', 'label_de' => 'Detailseite — Schaltfläche'],
            ['page_key' => 'leistungen', 'section_key' => 'detail', 'field_key' => 'seitenleiste_titel', 'value' => '{leistung} anfragen', 'label_de' => 'Detailseite — Titel Seitenspalte'],
            ['page_key' => 'leistungen', 'section_key' => 'detail', 'field_key' => 'schritt_1', 'type' => 'richtext', 'value' => 'Sie stellen eine kostenlose Anfrage mit Standort, Fahrzeug und Art des Gutachtens.', 'label_de' => 'Detailseite — Schritt 1'],
            ['page_key' => 'leistungen', 'section_key' => 'detail', 'field_key' => 'schritt_2', 'type' => 'richtext', 'value' => 'Wir leiten sie an geprüfte Sachverständige weiter, deren Einsatzgebiet Ihren Standort abdeckt.', 'label_de' => 'Detailseite — Schritt 2'],
            ['page_key' => 'leistungen', 'section_key' => 'detail', 'field_key' => 'schritt_3', 'type' => 'richtext', 'value' => 'Der erste verfügbare Sachverständige übernimmt und meldet sich direkt bei Ihnen.', 'label_de' => 'Detailseite — Schritt 3'],
            ['page_key' => 'leistungen', 'section_key' => 'detail', 'field_key' => 'meta_titel', 'value' => '{leistung} — Kfz-Sachverständigen finden | DKGZ', 'label_de' => 'Detailseite — Seitentitel für Google'],
            ['page_key' => 'leistungen', 'section_key' => 'detail', 'field_key' => 'meta_text', 'type' => 'richtext', 'value' => '{leistung} gesucht? DKGZ vermittelt Ihnen bundesweit einen geprüften Kfz-Sachverständigen. Kostenlos und unverbindlich.', 'label_de' => 'Detailseite — Beschreibung für Google'],
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
