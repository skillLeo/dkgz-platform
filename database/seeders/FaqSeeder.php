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
        return [
            ['category' => 'Allgemein', 'question_de' => 'Was ist DKGZ?', 'answer_de' => 'DKGZ ist eine Vermittlungsplattform für Kfz-Sachverständige. Wir leiten Ihre Anfrage an geeignete Sachverständige aus unserem bundesweiten Partnernetz weiter.'],
            ['category' => 'Kosten', 'question_de' => 'Ist die Anfrage kostenfrei?', 'answer_de' => 'Ja. Die Anfrage und die Vermittlung über DKGZ sind für Sie kostenfrei. Die Kosten des Gutachtens rechnet der Sachverständige direkt mit Ihnen oder Ihrer Versicherung ab.'],
            ['category' => 'Allgemein', 'question_de' => 'Erstellt DKGZ die Gutachten selbst?', 'answer_de' => 'Nein. Die Begutachtung erbringt der jeweils vermittelte Sachverständige in eigener Verantwortung. DKGZ vermittelt ausschließlich.'],
            ['category' => 'Allgemein', 'question_de' => 'Muss ich mich registrieren?', 'answer_de' => 'Nein. Für eine Anfrage benötigen Sie kein Kundenkonto und kein Passwort.'],
            ['category' => 'Leistungen', 'question_de' => 'Welche Gutachten können vermittelt werden?', 'answer_de' => 'Unter anderem Haftpflicht- und Kaskogutachten, Unfall- und Wertgutachten, Oldtimergutachten, Gebrauchtwagen-Checks, Reparaturbestätigungen und Beweissicherungen.'],
            ['category' => 'Ablauf', 'question_de' => 'Wie schnell meldet sich ein Sachverständiger?', 'answer_de' => 'Sobald ein Partner den Auftrag annimmt, erhält er Ihre Kontaktdaten und meldet sich direkt bei Ihnen — in der Regel innerhalb eines Werktages.'],
            ['category' => 'Datenschutz', 'question_de' => 'Wer sieht meine Kontaktdaten?', 'answer_de' => 'Ausschließlich der Sachverständige, der den Auftrag annimmt. Bis zur Annahme sehen die angefragten Partner nur Art des Gutachtens, Postleitzahl, Ort und Fahrzeugdaten.'],
            ['category' => 'Ablauf', 'question_de' => 'Erhalte ich mehrere Angebote zum Vergleich?', 'answer_de' => 'Nein. DKGZ ist kein Vergleichsportal. Der erste verfügbare Sachverständige übernimmt den Auftrag, damit Sie nicht auf Angebote warten müssen.'],
            ['category' => 'Ablauf', 'question_de' => 'Was passiert, wenn kein Sachverständiger verfügbar ist?', 'answer_de' => 'Ihre Anfrage geht dann an unsere Vermittlung, die sich persönlich um einen passenden Partner in Ihrer Region kümmert und sich bei Ihnen meldet.'],
            ['category' => 'Partner', 'question_de' => 'Wie werde ich Partner im Netz?', 'answer_de' => 'Über die Registrierung für Sachverständige. Nach Prüfung Ihrer Nachweise geben wir Ihren Zugang frei. Es gibt keine Grundgebühr und keine Vertragslaufzeit.'],
        ];
    }
}
