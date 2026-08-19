<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

/**
 * Copy taken from the FAQ section of the client's website structure document.
 */
class FaqSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->entries() as $index => $entry) {
            Faq::updateOrCreate(
                ['question_de' => $entry['question_de']],
                $entry + ['sort_order' => $index + 1, 'is_published' => true]
            );
        }
    }

    /** @return list<array<string, string>> */
    private function entries(): array
    {
        // The six questions on the homepage, verbatim from "DKGZ Homepage.dc.html".
        return [
            ['category' => 'Allgemein', 'question_de' => 'Was ist DKGZ?', 'answer_de' => 'DKGZ ist eine bundesweite Vermittlungsstelle für Kfz-Sachverständige. Wir nehmen Ihre Anfrage auf und leiten sie an qualifizierte Sachverständige weiter, deren Einsatzgebiet den Standort Ihres Fahrzeugs abdeckt.'],
            ['category' => 'Kosten', 'question_de' => 'Ist die Anfrage kostenlos?', 'answer_de' => 'Ja. Anfrage und Vermittlung sind für Sie kostenfrei. Die Kosten eines anschließend beauftragten Gutachtens rechnet der Sachverständige direkt mit Ihnen oder der Versicherung ab.'],
            ['category' => 'Allgemein', 'question_de' => 'Erstellt DKGZ die Gutachten selbst?', 'answer_de' => 'Nein. Die Begutachtung erbringt ausschließlich der vermittelte Sachverständige in eigener Verantwortung. DKGZ koordiniert die Vermittlung.'],
            ['category' => 'Allgemein', 'question_de' => 'Muss ich ein Konto anlegen?', 'answer_de' => 'Nein. Es gibt keine Registrierung, kein Passwort und kein Kundenkonto. Sie erhalten eine Vorgangsnummer und werden per E-Mail informiert.'],
            ['category' => 'Leistungen', 'question_de' => 'Welche Gutachten werden vermittelt?', 'answer_de' => 'Unfall-, Haftpflicht-, Kasko- und Fahrzeugschadengutachten, Wertgutachten, Oldtimergutachten, Gebrauchtwagen-Checks und Beweissicherungen. Weitere Leistungen auf Anfrage.'],
            ['category' => 'Ablauf', 'question_de' => 'Wie schnell meldet sich ein Sachverständiger?', 'answer_de' => 'Sobald ein Partner die Anfrage annimmt, erhält er Ihre Kontaktdaten und meldet sich direkt bei Ihnen. In der Regel geschieht das innerhalb eines Werktages.'],
        ];
    }
}
