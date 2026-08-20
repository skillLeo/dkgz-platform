<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * The four legal pages. Copy follows the Impressum template in
 * "DKGZ Öffentliche Seiten.dc.html" — bracketed fields are the operator's to
 * complete in the admin before going live.
 */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->pages() as $index => $page) {
            Page::updateOrCreate(
                ['slug' => $page['slug']],
                $page + ['sort_order' => $index + 1, 'is_published' => true]
            );
        }
    }

    /** @return list<array<string, string>> */
    private function pages(): array
    {
        return [
            [
                'slug' => 'impressum',
                'title_de' => 'Impressum',
                'meta_title' => 'Impressum — DKGZ',
                'meta_description' => 'Anbieterkennzeichnung der Deutschen KFZ-Gutachterzentrale.',
                'body_de' => <<<'HTML'
<h2>Angaben gemäß § 5 DDG</h2>
<p>DKGZ Deutsche KFZ-Gutachterzentrale<br>Musterstraße 00<br>40589 Düsseldorf<br>Deutschland</p>

<h2>Vertreten durch</h2>
<p>Geschäftsführung: [Name]</p>

<h2>Kontakt</h2>
<p>Telefon: +49 179 4480169<br>E-Mail: info@dkgz.de</p>

<h2>Registereintrag und Umsatzsteuer</h2>
<p>Eintragung im Handelsregister, Registergericht und Registernummer werden hier eingesetzt. Umsatzsteuer-Identifikationsnummer gemäß § 27 a Umsatzsteuergesetz: [USt-IdNr.]</p>

<h2>Art der Leistung</h2>
<p>DKGZ vermittelt Anfragen von Kundinnen und Kunden an selbstständige Kfz-Sachverständige eines bundesweiten Partnernetzes. DKGZ erstellt selbst keine Gutachten und erbringt keine Sachverständigenleistungen. Der Gutachtenauftrag kommt ausschließlich zwischen der anfragenden Person und dem vermittelten Sachverständigen zustande. Für Inhalt, Richtigkeit und Fristen des Gutachtens ist allein der beauftragte Sachverständige verantwortlich.</p>

<h2>Streitbeilegung</h2>
<p>Die Europäische Kommission stellt eine Plattform zur Online-Streitbeilegung bereit. Wir sind nicht verpflichtet und nicht bereit, an einem Streitbeilegungsverfahren vor einer Verbraucherschlichtungsstelle teilzunehmen.</p>

<h2>Haftung für Inhalte und Links</h2>
<p>Als Diensteanbieter sind wir für eigene Inhalte auf diesen Seiten nach den allgemeinen Gesetzen verantwortlich. Für Inhalte externer Links ist stets der jeweilige Anbieter der verlinkten Seiten verantwortlich. Bei Bekanntwerden von Rechtsverletzungen entfernen wir derartige Inhalte umgehend.</p>
HTML,
            ],
            [
                'slug' => 'datenschutz',
                'title_de' => 'Datenschutzerklärung',
                'meta_title' => 'Datenschutzerklärung — DKGZ',
                'meta_description' => 'Wie DKGZ personenbezogene Daten verarbeitet.',
                'body_de' => <<<'HTML'
<h2>Verantwortliche Stelle</h2>
<p>Verantwortlich für die Datenverarbeitung auf dieser Website ist DKGZ Deutsche KFZ-Gutachterzentrale, Musterstraße 00, 40589 Düsseldorf. Sie erreichen uns unter info@dkgz.de.</p>

<h2>Welche Daten wir erheben</h2>
<p>Wenn Sie eine Anfrage stellen, erheben wir die von Ihnen angegebenen Daten: Art des Gutachtens, Standort und Postleitzahl des Fahrzeugs, Fahrzeugdaten, Ihren Namen, Ihre Telefonnummer und Ihre E-Mail-Adresse sowie optional eine Beschreibung und Fotos. Zusätzlich speichern wir den Zeitpunkt Ihrer Einwilligung, Ihre IP-Adresse und die Kennung Ihres Browsers zur Missbrauchsvermeidung.</p>

<h2>Zweck und Rechtsgrundlage</h2>
<p>Wir verarbeiten diese Daten, um Ihre Anfrage an geeignete Sachverständige zu vermitteln. Rechtsgrundlage ist Artikel 6 Absatz 1 Buchstabe b DSGVO, da die Verarbeitung zur Durchführung vorvertraglicher Maßnahmen auf Ihre Anfrage hin erforderlich ist.</p>

<h2>Weitergabe an Sachverständige</h2>
<p>Ihre Kontaktdaten werden ausschließlich an denjenigen Sachverständigen übermittelt, der den Auftrag annimmt. Bis zur Annahme sehen die angefragten Sachverständigen nur Art des Gutachtens, Postleitzahl, Ort und Fahrzeugdaten — Name, Telefonnummer und E-Mail-Adresse bleiben verborgen.</p>

<h2>Speicherdauer</h2>
<p>Anfragedaten werden nach Ablauf der konfigurierten Aufbewahrungsfrist anonymisiert, sofern keine gesetzlichen Aufbewahrungspflichten entgegenstehen.</p>

<h2>Schriftarten</h2>
<p>Diese Website lädt keine Schriftarten von externen Servern. Alle verwendeten Schriften werden von unserem eigenen Server ausgeliefert. Es findet keine Übermittlung Ihrer IP-Adresse an Dritte statt.</p>

<h2>Ihre Rechte</h2>
<p>Sie haben das Recht auf Auskunft, Berichtigung, Löschung, Einschränkung der Verarbeitung, Datenübertragbarkeit und Widerspruch. Wenden Sie sich dazu an info@dkgz.de. Ihnen steht zudem ein Beschwerderecht bei einer Datenschutzaufsichtsbehörde zu.</p>
HTML,
            ],
            [
                'slug' => 'agb',
                'title_de' => 'Allgemeine Geschäftsbedingungen',
                'meta_title' => 'AGB — DKGZ',
                'meta_description' => 'Allgemeine Geschäftsbedingungen der Deutschen KFZ-Gutachterzentrale.',
                'body_de' => <<<'HTML'
<h2>Geltungsbereich</h2>
<p>Diese Bedingungen gelten für die Nutzung der Vermittlungsleistung der DKGZ Deutsche KFZ-Gutachterzentrale durch anfragende Personen und durch Sachverständige des Partnernetzes.</p>

<h2>Gegenstand der Leistung</h2>
<p>DKGZ vermittelt Anfragen an selbstständige Kfz-Sachverständige. DKGZ erbringt selbst keine Sachverständigenleistung und wird nicht Vertragspartner des Gutachtenauftrags.</p>

<h2>Kosten für anfragende Personen</h2>
<p>Die Anfrage und die Vermittlung sind für anfragende Personen kostenfrei. Die Vergütung der Begutachtung rechnet der Sachverständige unmittelbar mit der anfragenden Person oder deren Versicherung ab.</p>

<h2>Provision der Sachverständigen</h2>
<p>Für erfolgreich vermittelte und abgeschlossene Aufträge fällt eine Vermittlungsprovision auf das tatsächlich berechnete Netto-Honorar an. Der jeweils gültige Satz ist im Partnerportal ausgewiesen. Für abgelehnte oder nicht zustande gekommene Aufträge entsteht keine Provision.</p>

<h2>Pflichten der Sachverständigen</h2>
<p>Sachverständige halten ihr Einsatzgebiet und ihre Verfügbarkeit aktuell, melden sich zeitnah auf vermittelte Anfragen zurück und hinterlegen nach Abschluss Gutachten und Rechnung im Portal.</p>

<h2>Haftung</h2>
<p>DKGZ haftet nicht für Inhalt, Richtigkeit oder Fristen des Gutachtens. Diese Verantwortung liegt allein beim beauftragten Sachverständigen.</p>
HTML,
            ],
            [
                'slug' => 'widerruf',
                'title_de' => 'Widerrufsbelehrung',
                'meta_title' => 'Widerrufsbelehrung — DKGZ',
                'meta_description' => 'Widerrufsrecht bei der Nutzung der DKGZ-Vermittlung.',
                'body_de' => <<<'HTML'
<h2>Widerrufsrecht</h2>
<p>Verbraucherinnen und Verbraucher haben das Recht, binnen vierzehn Tagen ohne Angabe von Gründen einen im Fernabsatz geschlossenen Vertrag zu widerrufen. Die Frist beginnt mit dem Tag des Vertragsschlusses.</p>

<h2>Ausübung des Widerrufs</h2>
<p>Um Ihr Widerrufsrecht auszuüben, informieren Sie uns mittels einer eindeutigen Erklärung über Ihren Entschluss: DKGZ Deutsche KFZ-Gutachterzentrale, Musterstraße 00, 40589 Düsseldorf, info@dkgz.de. Zur Wahrung der Frist genügt die rechtzeitige Absendung.</p>

<h2>Hinweis zur Vermittlung</h2>
<p>Die Vermittlung über DKGZ ist für anfragende Personen unentgeltlich. Ein Widerruf der Vermittlung berührt einen bereits unmittelbar mit einem Sachverständigen geschlossenen Gutachtenvertrag nicht. Für dessen Widerruf gelten die Bedingungen des jeweiligen Sachverständigen.</p>

<h2>Folgen des Widerrufs</h2>
<p>Haben Sie verlangt, dass die Dienstleistung während der Widerrufsfrist beginnen soll, so haben Sie uns einen angemessenen Betrag zu zahlen, der dem Anteil der bis zum Widerruf erbrachten Leistungen entspricht. Da die Vermittlung unentgeltlich ist, entsteht Ihnen hieraus kein Zahlungsanspruch.</p>
HTML,
            ],
        ];
    }
}
