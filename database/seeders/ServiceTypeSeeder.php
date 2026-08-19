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
                'name_de' => 'Unfallgutachten',
                'description_de' => 'Schadenhöhe, Wertminderung und Ausfalldauer nach einem Verkehrsunfall.',
                'icon' => 'car',
            ],
            [
                'slug' => 'haftpflichtgutachten',
                'name_de' => 'Haftpflichtgutachten',
                'description_de' => 'Unabhängige Feststellung gegenüber der Versicherung des Verursachers.',
                'icon' => 'shield-check',
            ],
            [
                'slug' => 'kaskogutachten',
                'name_de' => 'Kaskogutachten',
                'description_de' => 'Schadenaufnahme nach Vorgaben der eigenen Kaskoversicherung.',
                'icon' => 'shield',
            ],
            [
                'slug' => 'fahrzeugschadengutachten',
                'name_de' => 'Fahrzeugschadengutachten',
                'description_de' => 'Technische Bewertung von Schäden ohne Unfallbeteiligung Dritter.',
                'icon' => 'wrench',
            ],
            [
                'slug' => 'wertgutachten',
                'name_de' => 'Wertgutachten',
                'description_de' => 'Belastbare Wertermittlung für Verkauf, Erbfall oder Finanzierung.',
                'icon' => 'euro',
            ],
            [
                'slug' => 'oldtimergutachten',
                'name_de' => 'Oldtimergutachten',
                'description_de' => 'Zustandsnoten und Marktwert für Fahrzeuge mit historischem Wert.',
                'icon' => 'history',
            ],
            [
                'slug' => 'gebrauchtwagen-check',
                'name_de' => 'Gebrauchtwagen-Check',
                'description_de' => 'Technische Prüfung vor dem Kauf, mit schriftlichem Befund.',
                'icon' => 'clipboard-check',
            ],
            [
                'slug' => 'beweissicherung',
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
