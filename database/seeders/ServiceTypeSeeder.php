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
                'dkgz_fee_cents' => 7900,
                'name_de' => 'Unfallgutachten',
                'description_de' => 'Schadenhöhe, Wertminderung und Ausfalldauer nach einem Verkehrsunfall.',
                'icon' => 'car',
            ],
            [
                'slug' => 'haftpflichtgutachten',
                'dkgz_fee_cents' => 7900,
                'name_de' => 'Haftpflichtgutachten',
                'description_de' => 'Unabhängige Feststellung gegenüber der Versicherung des Verursachers.',
                'icon' => 'shield-check',
            ],
            [
                'slug' => 'kaskogutachten',
                'dkgz_fee_cents' => 6900,
                'name_de' => 'Kaskogutachten',
                'description_de' => 'Schadenaufnahme nach Vorgaben der eigenen Kaskoversicherung.',
                'icon' => 'shield',
            ],
            [
                'slug' => 'fahrzeugschadengutachten',
                'dkgz_fee_cents' => 6900,
                'name_de' => 'Fahrzeugschadengutachten',
                'description_de' => 'Technische Bewertung von Schäden ohne Unfallbeteiligung Dritter.',
                'icon' => 'wrench',
            ],
            [
                'slug' => 'wertgutachten',
                'dkgz_fee_cents' => 5900,
                'name_de' => 'Wertgutachten',
                'description_de' => 'Belastbare Wertermittlung für Verkauf, Erbfall oder Finanzierung.',
                'icon' => 'euro',
            ],
            [
                'slug' => 'oldtimergutachten',
                'dkgz_fee_cents' => 8900,
                'name_de' => 'Oldtimergutachten',
                'description_de' => 'Zustandsnoten und Marktwert für Fahrzeuge mit historischem Wert.',
                'icon' => 'history',
            ],
            [
                'slug' => 'gebrauchtwagen-check',
                'dkgz_fee_cents' => 3900,
                'name_de' => 'Gebrauchtwagen-Check',
                'description_de' => 'Technische Prüfung vor dem Kauf, mit schriftlichem Befund.',
                'icon' => 'clipboard-check',
            ],
            [
                'slug' => 'beweissicherung',
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
