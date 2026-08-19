<?php

namespace Database\Seeders;

use App\Models\ServiceType;
use Illuminate\Database\Seeder;

class ServiceTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'slug' => 'haftpflichtgutachten',
                'name_de' => 'Haftpflichtgutachten',
                'description_de' => 'Schadengutachten nach einem unverschuldeten Unfall. Die Kosten trägt in der Regel die gegnerische Haftpflichtversicherung.',
                'icon' => 'file-text',
            ],
            [
                'slug' => 'kaskogutachten',
                'name_de' => 'Kaskogutachten',
                'description_de' => 'Schadenermittlung für die eigene Teil- oder Vollkaskoversicherung, etwa nach Hagel, Wild- oder Glasschaden.',
                'icon' => 'shield',
            ],
            [
                'slug' => 'unfallgutachten',
                'name_de' => 'Unfallgutachten',
                'description_de' => 'Vollständige Dokumentation von Schadenhöhe, Wertminderung und Wiederbeschaffungswert nach einem Verkehrsunfall.',
                'icon' => 'car',
            ],
            [
                'slug' => 'wertgutachten',
                'name_de' => 'Wertgutachten',
                'description_de' => 'Ermittlung des aktuellen Marktwerts eines Fahrzeugs, etwa für Verkauf, Erbschaft oder Auseinandersetzung.',
                'icon' => 'euro',
            ],
            [
                'slug' => 'oldtimergutachten',
                'name_de' => 'Oldtimergutachten',
                'description_de' => 'Zustands- und Wertgutachten für Klassiker, einschließlich Bewertung nach Zustandsnoten und H-Kennzeichen-Eignung.',
                'icon' => 'award',
            ],
            [
                'slug' => 'gebrauchtwagen-check',
                'name_de' => 'Gebrauchtwagen-Check',
                'description_de' => 'Technische Prüfung vor dem Kauf, mit Bericht zu Zustand, Vorschäden und erkennbaren Mängeln.',
                'icon' => 'search-check',
            ],
            [
                'slug' => 'reparaturbestaetigung',
                'name_de' => 'Reparaturbestätigung',
                'description_de' => 'Nachweis der fachgerechten Instandsetzung gegenüber der Versicherung, häufig bei fiktiver Abrechnung.',
                'icon' => 'wrench',
            ],
            [
                'slug' => 'beweissicherung',
                'name_de' => 'Beweissicherung',
                'description_de' => 'Gerichtsfeste Dokumentation eines Fahrzeugzustands, etwa bei Streit über Mängel oder Rückgabe.',
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
