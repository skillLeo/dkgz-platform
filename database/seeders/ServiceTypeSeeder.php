<?php

namespace Database\Seeders;

use App\Models\ServiceType;
use Illuminate\Database\Seeder;

class ServiceTypeSeeder extends Seeder
{
    public function run(): void
    {
        // Names, descriptions and icons are verbatim from the services grid in
        // "DKGZ Homepage.dc.html".
        $types = [
            [
                'slug' => 'unfallgutachten',
                'includes_de' => 'Vollständige Dokumentation des Unfallschadens: Sichtprüfung des Fahrzeugs, Fotodokumentation aller Beschädigungen, Kalkulation der Reparaturkosten, Feststellung von Wertminderung und Wiederbeschaffungswert sowie Angaben zur voraussichtlichen Reparaturdauer und zum Nutzungsausfall.',
                'target_audience_de' => 'Fahrzeughalterinnen und -halter, die unverschuldet in einen Unfall geraten sind und ihre Ansprüche gegenüber der gegnerischen Versicherung belegen müssen.',
                'typical_situations_de' => 'Nach einem Auffahrunfall, bei dem die Schuldfrage geklärt ist. Bei erkennbaren Schäden über der Bagatellgrenze von etwa 750 Euro. Immer dann, wenn die gegnerische Versicherung eine eigene Begutachtung vorschlägt und Sie ein unabhängiges Gutachten vorziehen.',
                'differences_de' => 'Anders als beim Kaskogutachten rechnet der Sachverständige hier gegenüber der gegnerischen Versicherung ab. Anders als beim Fahrzeugschadengutachten wird zusätzlich die Wertminderung ermittelt, die bei einem unverschuldeten Unfall erstattungsfähig ist.',
                'additional_info_de' => 'Bei einem unverschuldeten Unfall trägt in der Regel die gegnerische Versicherung die Kosten des Gutachtens. Sie haben das Recht, den Sachverständigen frei zu wählen — auch wenn die Versicherung einen eigenen vorschlägt.',
                'dkgz_fee_cents' => 7900,
                'name_de' => 'Unfallgutachten',
                'description_de' => 'Schadenhöhe, Wertminderung und Ausfalldauer nach einem Verkehrsunfall.',
                'icon' => 'car',
            ],
            [
                'slug' => 'haftpflichtgutachten',
                'includes_de' => 'Feststellung und Bewertung des Schadens, den ein Dritter an Ihrem Fahrzeug verursacht hat: Schadenumfang, Reparaturkosten, merkantile Wertminderung, Wiederbeschaffungs- und Restwert sowie die Prüfung, ob ein wirtschaftlicher Totalschaden vorliegt.',
                'target_audience_de' => 'Geschädigte, die einen Schaden gegenüber der Haftpflichtversicherung des Verursachers geltend machen.',
                'typical_situations_de' => 'Wenn ein anderer Verkehrsteilnehmer Ihr Fahrzeug beschädigt hat. Bei Parkschäden mit bekanntem Verursacher. Wenn die Versicherung des Gegners den Schadenumfang bestreitet oder kürzt.',
                'differences_de' => 'Der Unterschied zum Unfallgutachten liegt weniger im Umfang als im Anlass: hier steht die Haftung eines Dritten im Vordergrund, nicht zwingend ein Verkehrsunfall.',
                'additional_info_de' => 'Reichen Sie das Gutachten möglichst früh ein. Je später die Begutachtung, desto schwieriger wird der Nachweis, dass ein Schaden aus dem gemeldeten Ereignis stammt.',
                'dkgz_fee_cents' => 7900,
                'name_de' => 'Haftpflichtgutachten',
                'description_de' => 'Unabhängige Feststellung gegenüber der Versicherung des Verursachers.',
                'icon' => 'shield-check',
            ],
            [
                'slug' => 'kaskogutachten',
                'includes_de' => 'Schadenfeststellung nach den Bedingungen Ihrer eigenen Teil- oder Vollkaskoversicherung: Umfang, Reparaturkosten, Restwert und die Einordnung, ob ein Totalschaden im Sinne der Versicherungsbedingungen vorliegt.',
                'target_audience_de' => 'Versicherungsnehmerinnen und -nehmer mit Kaskoschutz, die einen Schaden bei der eigenen Versicherung melden.',
                'typical_situations_de' => 'Bei selbstverschuldeten Unfällen. Nach Hagel, Sturm oder Wildunfall. Bei Diebstahl oder Vandalismus. Immer dann, wenn Ihre eigene Versicherung reguliert.',
                'differences_de' => 'Anders als beim Haftpflichtgutachten gibt es hier keinen Dritten, der haftet — und in der Regel keine erstattungsfähige Wertminderung. Die Selbstbeteiligung Ihres Vertrags bleibt bestehen.',
                'additional_info_de' => 'Klären Sie vor der Beauftragung mit Ihrer Versicherung, ob und in welcher Höhe die Gutachterkosten übernommen werden. Anders als beim Haftpflichtschaden ist das nicht selbstverständlich.',
                'dkgz_fee_cents' => 6900,
                'name_de' => 'Kaskogutachten',
                'description_de' => 'Schadenaufnahme nach Vorgaben der eigenen Kaskoversicherung.',
                'icon' => 'shield',
            ],
            [
                'slug' => 'fahrzeugschadengutachten',
                'includes_de' => 'Sachliche Feststellung eines Fahrzeugschadens unabhängig von Schuldfrage und Versicherung: Schadenbild, Ursache soweit feststellbar, Reparaturweg und Kostenrahmen.',
                'target_audience_de' => 'Alle, die einen Schaden belegen müssen, ohne dass eine Versicherung beteiligt ist — etwa bei Streit mit einer Werkstatt oder einem Verkäufer.',
                'typical_situations_de' => 'Bei Streit über eine mangelhafte Reparatur. Wenn ein Schaden nach einer Werkstattfahrt aufgetreten ist. Zur Vorlage bei Gericht oder gegenüber einem Vertragspartner.',
                'differences_de' => 'Enthält im Gegensatz zum Unfall- oder Haftpflichtgutachten keine Wertminderung und keine Abrechnung gegenüber einer Versicherung. Es ist die sachliche Grundlage, kein Anspruchsschreiben.',
                'additional_info_de' => 'Halten Sie Rechnungen, Werkstattaufträge und Vorkorrespondenz bereit. Sie helfen dem Sachverständigen, die Schadensursache einzugrenzen.',
                'dkgz_fee_cents' => 6900,
                'name_de' => 'Fahrzeugschadengutachten',
                'description_de' => 'Technische Bewertung von Schäden ohne Unfallbeteiligung Dritter.',
                'icon' => 'wrench',
            ],
            [
                'slug' => 'wertgutachten',
                'includes_de' => 'Ermittlung des aktuellen Marktwerts: Zustandsbewertung, Ausstattung, Laufleistung, Vorschäden, Wartungshistorie und die Einordnung in den regionalen Markt — als Händlereinkaufs-, Händlerverkaufs- oder Wiederbeschaffungswert.',
                'target_audience_de' => 'Verkäuferinnen und Verkäufer, Erbengemeinschaften, Geschiedene, Unternehmen mit Fuhrpark und alle, die einen belastbaren Wert brauchen.',
                'typical_situations_de' => 'Vor einem privaten Verkauf. Bei Erbauseinandersetzung oder Zugewinnausgleich. Für das Finanzamt bei einer Entnahme aus dem Betriebsvermögen. Bei Leasingrückgabe.',
                'differences_de' => 'Hier geht es nicht um einen Schaden, sondern um den Wert. Ein Wertgutachten enthält keine Reparaturkalkulation.',
                'additional_info_de' => 'Für Oldtimer ist das Oldtimergutachten die passende Wahl, da dort Originalität und Zustandsnote nach eigenen Maßstäben bewertet werden.',
                'dkgz_fee_cents' => 5900,
                'name_de' => 'Wertgutachten',
                'description_de' => 'Belastbare Wertermittlung für Verkauf, Erbfall oder Finanzierung.',
                'icon' => 'euro',
            ],
            [
                'slug' => 'oldtimergutachten',
                'includes_de' => 'Bewertung nach den für historische Fahrzeuge üblichen Maßstäben: Zustandsnote von 1 bis 5, Originalität, Dokumentation der Historie, Marktwert und Wiederbeschaffungswert sowie die für das H-Kennzeichen erforderliche Beurteilung.',
                'target_audience_de' => 'Besitzerinnen und Besitzer historischer Fahrzeuge, die versichern, verkaufen oder ein H-Kennzeichen beantragen möchten.',
                'typical_situations_de' => 'Vor Abschluss einer Oldtimerversicherung, die fast immer ein Wertgutachten verlangt. Zur Beantragung des H-Kennzeichens. Vor Verkauf oder Ankauf eines Liebhaberfahrzeugs.',
                'differences_de' => 'Ein gewöhnliches Wertgutachten bildet den Markt für Gebrauchtwagen ab. Beim Oldtimer bestimmen Originalität, Zustandsnote und Seltenheit den Wert — ein anderer Maßstab.',
                'additional_info_de' => 'Bringen Sie alle Belege mit: Restaurationsrechnungen, Fahrzeugbriefe, historische Fotos. Sie wirken sich unmittelbar auf die Bewertung aus.',
                'dkgz_fee_cents' => 8900,
                'name_de' => 'Oldtimergutachten',
                'description_de' => 'Zustandsnoten und Marktwert für Fahrzeuge mit historischem Wert.',
                'icon' => 'history',
            ],
            [
                'slug' => 'gebrauchtwagen-check',
                'includes_de' => 'Technische Kurzprüfung vor dem Kauf: Sichtprüfung von Karosserie, Unterboden und Motorraum, Auslesen des Fehlerspeichers, Prüfung auf Unfallspuren und Lackschichtmessung, Plausibilitätsprüfung der Laufleistung.',
                'target_audience_de' => 'Käuferinnen und Käufer, die ein gebrauchtes Fahrzeug privat oder beim Händler erwerben wollen.',
                'typical_situations_de' => 'Vor dem Kauf eines Gebrauchtwagens, besonders bei Privatkauf ohne Gewährleistung. Wenn Ihnen der angebotene Preis zu günstig erscheint. Bei Fahrzeugen mit auffällig hoher oder niedriger Laufleistung.',
                'differences_de' => 'Kein vollständiges Gutachten, sondern eine gezielte Prüfung vor der Kaufentscheidung. Enthält keine gerichtsfeste Wertermittlung.',
                'additional_info_de' => 'Vereinbaren Sie den Termin am besten beim Verkäufer und planen Sie etwa eine Stunde ein. Eine Probefahrt vorab hilft, gezielt nach Auffälligkeiten zu suchen.',
                'dkgz_fee_cents' => 3900,
                'name_de' => 'Gebrauchtwagen-Check',
                'description_de' => 'Technische Prüfung vor dem Kauf, mit schriftlichem Befund.',
                'icon' => 'clipboard-check',
            ],
            [
                'slug' => 'beweissicherung',
                'includes_de' => 'Gerichtsfeste Dokumentation eines Zustands zu einem bestimmten Zeitpunkt: umfassende Fotodokumentation, Vermessung, schriftliche Feststellung und Archivierung für spätere Verwendung.',
                'target_audience_de' => 'Alle, die einen Zustand nachweisen müssen, bevor er sich verändert — vor einer Reparatur, vor Rückgabe eines Fahrzeugs oder vor einem Rechtsstreit.',
                'typical_situations_de' => 'Vor Rückgabe eines Leasing- oder Mietfahrzeugs. Bevor eine strittige Reparatur ausgeführt wird. Wenn ein Rechtsstreit absehbar ist und Spuren verloren gehen könnten.',
                'differences_de' => 'Sichert den Ist-Zustand, ohne Ursachen zu bewerten oder Kosten zu kalkulieren. Es ist eine Momentaufnahme mit Beweiskraft, kein Schadengutachten.',
                'additional_info_de' => 'Je früher die Beweissicherung stattfindet, desto belastbarer ist sie. Nach einer Reparatur lässt sich der ursprüngliche Zustand nicht mehr feststellen.',
                'dkgz_fee_cents' => 4900,
                'name_de' => 'Beweissicherung',
                'description_de' => 'Dokumentation des Fahrzeugzustands für spätere Auseinandersetzungen.',
                'icon' => 'camera',
            ],
        ];

        foreach ($types as $index => $type) {
            ServiceType::updateOrCreate(
                ['slug' => $type['slug']],
                $type + ['sort_order' => $index + 1, 'is_active' => true]
            );
        }
    }
}
