<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Support\Branding;
use App\Support\Settings;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->definitions() as $index => $definition) {
            $existing = Setting::where('key', $definition['key'])->first();

            // Never overwrite a value the operator has already configured.
            if ($existing !== null) {
                $existing->fill([
                    'group' => $definition['group'],
                    'type' => $definition['type'],
                    'is_encrypted' => $definition['is_encrypted'] ?? false,
                    'label_de' => $definition['label_de'],
                    'help_de' => $definition['help_de'] ?? null,
                    'sort_order' => $index,
                ])->save();

                continue;
            }

            $setting = new Setting([
                'group' => $definition['group'],
                'key' => $definition['key'],
                'type' => $definition['type'],
                'is_encrypted' => $definition['is_encrypted'] ?? false,
                'label_de' => $definition['label_de'],
                'help_de' => $definition['help_de'] ?? null,
                'sort_order' => $index,
            ]);

            $setting->writeValue($definition['value'] ?? null);
            $setting->save();
        }

        Settings::flush();
    }

    /** @return list<array<string, mixed>> */
    private function definitions(): array
    {
        $branding = [
            ['group' => 'branding', 'key' => 'branding.platform_name', 'type' => 'string', 'value' => 'DKGZ', 'label_de' => 'Name der Plattform', 'help_de' => 'Wortmarke im Kopfbereich und in E-Mails.'],
            ['group' => 'branding', 'key' => 'branding.platform_subtitle', 'type' => 'string', 'value' => 'Deutsche KFZ-Gutachterzentrale', 'label_de' => 'Zusatz zur Wortmarke'],
            ['group' => 'branding', 'key' => 'branding.logo_light', 'type' => 'file', 'label_de' => 'Logo für helle Flächen', 'help_de' => 'Ersetzt die komplette Wortmarke auf hellem Grund. PNG oder SVG. Ohne Angabe bleibt die gestaltete Wortmarke stehen.'],
            ['group' => 'branding', 'key' => 'branding.logo_dark', 'type' => 'file', 'label_de' => 'Logo für dunkle Flächen', 'help_de' => 'Dasselbe für dunklen Grund — Fußzeile, Menü und Seitenleiste. Ohne Angabe wird das helle Logo verwendet.'],
            ['group' => 'branding', 'key' => 'branding.seal', 'type' => 'file', 'label_de' => 'Siegelmarke (nur der Kreis)', 'help_de' => 'Ersetzt nur den runden Kreis links im Logo. Der Schriftzug DKGZ und der Untertitel bleiben unverändert. Am besten quadratisch mit transparentem Hintergrund.'],
            ['group' => 'branding', 'key' => 'branding.favicon', 'type' => 'file', 'label_de' => 'Favicon'],
        ];

        foreach (Branding::tokens() as $key => $token) {
            $branding[] = [
                'group' => 'branding',
                'key' => "branding.color_{$key}",
                'type' => 'string',
                'value' => $token['default'],
                'label_de' => $token['label'],
                'help_de' => 'Hexwert, z. B. '.$token['default'].'. Wirkt sofort, ohne neuen Build.',
            ];
        }

        return array_merge($branding, [
            ['group' => 'contact', 'key' => 'contact.company_name', 'type' => 'string', 'value' => 'DKGZ Deutsche KFZ-Gutachterzentrale', 'label_de' => 'Firmenname'],
            ['group' => 'contact', 'key' => 'contact.street', 'type' => 'string', 'value' => 'Musterstraße 00', 'label_de' => 'Straße und Hausnummer'],
            ['group' => 'contact', 'key' => 'contact.postal_code', 'type' => 'string', 'value' => '40589', 'label_de' => 'Postleitzahl'],
            ['group' => 'contact', 'key' => 'contact.city', 'type' => 'string', 'value' => 'Düsseldorf', 'label_de' => 'Ort'],
            ['group' => 'contact', 'key' => 'contact.country', 'type' => 'string', 'value' => 'Deutschland', 'label_de' => 'Land'],
            ['group' => 'contact', 'key' => 'contact.phone', 'type' => 'string', 'value' => '+49 179 4480169', 'label_de' => 'Telefonnummer'],
            ['group' => 'contact', 'key' => 'contact.support_email', 'type' => 'string', 'value' => 'info@dkgz.de', 'label_de' => 'E-Mail-Adresse'],
            ['group' => 'contact', 'key' => 'contact.office_hours', 'type' => 'string', 'value' => 'Mo–Fr 08:00–18:00', 'label_de' => 'Erreichbarkeit'],
            ['group' => 'contact', 'key' => 'contact.vat_id', 'type' => 'string', 'label_de' => 'USt-IdNr.'],
            ['group' => 'contact', 'key' => 'contact.managing_director', 'type' => 'string', 'label_de' => 'Geschäftsführung'],
            ['group' => 'contact', 'key' => 'contact.register_court', 'type' => 'string', 'label_de' => 'Registergericht'],
            ['group' => 'contact', 'key' => 'contact.register_number', 'type' => 'string', 'label_de' => 'Registernummer'],

            ['group' => 'email', 'key' => 'email.from_name', 'type' => 'string', 'value' => 'DKGZ Gutachterzentrale', 'label_de' => 'Absendername'],
            ['group' => 'email', 'key' => 'email.from_address', 'type' => 'string', 'value' => 'no-reply@dkgz.de', 'label_de' => 'Absenderadresse'],
            ['group' => 'email', 'key' => 'email.reply_to', 'type' => 'string', 'value' => 'info@dkgz.de', 'label_de' => 'Antwortadresse'],
            ['group' => 'email', 'key' => 'email.bounce_address', 'type' => 'string', 'label_de' => 'Rücklaufadresse (Bounce)', 'help_de' => 'Empfängt Unzustellbarkeitsmeldungen. Ohne Angabe wird die Absenderadresse verwendet.'],
            ['group' => 'email', 'key' => 'email.dkim_selector', 'type' => 'string', 'label_de' => 'DKIM-Selector', 'help_de' => 'Vom Versanddienst vorgegeben, zum Beispiel „mail“ oder „brevo1“. Wird nur zur Prüfung des DNS-Eintrags benötigt.'],
            ['group' => 'email', 'key' => 'email.unsubscribe_address', 'type' => 'string', 'label_de' => 'Abmeldeadresse für Rundmails', 'help_de' => 'Erscheint als List-Unsubscribe in nicht-transaktionalen E-Mails wie der Provisionsabrechnung.'],
            ['group' => 'email', 'key' => 'email.footer_text', 'type' => 'text', 'value' => 'Diese E-Mail wurde automatisch versendet. Bitte antworten Sie nicht auf diese Adresse.', 'label_de' => 'Fußzeile in allen E-Mails'],
            ['group' => 'email', 'key' => 'email.signoff', 'type' => 'text', 'value' => "Mit freundlichen Grüßen\nDeutsche KFZ-Gutachterzentrale", 'label_de' => 'Grußformel am Ende jeder Mail', 'help_de' => 'Steht unter dem Text jeder E-Mail. Zeilenumbrüche werden übernommen. Leer lassen, um sie wegzulassen.'],
            ['group' => 'email', 'key' => 'email.admin_recipient', 'type' => 'string', 'value' => 'info@dkgz.de', 'label_de' => 'Interne Empfängeradresse', 'help_de' => 'Erhält Registrierungen, Kontaktanfragen und Meldungen ohne Treffer.'],

            ['group' => 'integrations', 'key' => 'integrations.smtp_host', 'type' => 'string', 'label_de' => 'SMTP-Server', 'help_de' => 'Ohne Angabe wird der in der .env hinterlegte Mailer verwendet.'],
            ['group' => 'integrations', 'key' => 'integrations.smtp_port', 'type' => 'integer', 'value' => '587', 'label_de' => 'Port'],
            ['group' => 'integrations', 'key' => 'integrations.smtp_encryption', 'type' => 'string', 'value' => 'tls', 'label_de' => 'Verschlüsselung', 'help_de' => 'tls, ssl oder none.'],
            ['group' => 'integrations', 'key' => 'integrations.smtp_username', 'type' => 'string', 'label_de' => 'Benutzername'],
            ['group' => 'integrations', 'key' => 'integrations.smtp_password', 'type' => 'encrypted', 'is_encrypted' => true, 'label_de' => 'Passwort', 'help_de' => 'Verschlüsselt gespeichert und nie wieder angezeigt.'],
            ['group' => 'integrations', 'key' => 'integrations.map_api_key', 'type' => 'encrypted', 'is_encrypted' => true, 'label_de' => 'API-Schlüssel Kartendienst'],
            ['group' => 'integrations', 'key' => 'integrations.analytics_id', 'type' => 'string', 'label_de' => 'Analytics-Kennung'],
            ['group' => 'integrations', 'key' => 'integrations.google_site_verification', 'type' => 'string', 'label_de' => 'Google Search Console — Bestätigungscode', 'help_de' => 'Nur der Wert aus dem content-Attribut, ohne den umgebenden Meta-Tag. Setzt kein Cookie und braucht keine Zustimmung.'],

            ['group' => 'business', 'key' => 'business.commission_rate', 'type' => 'decimal', 'value' => '15.00', 'label_de' => 'Vermittlungsprovision in Prozent', 'help_de' => 'Gilt für neue Abschlüsse. Bereits abgerechnete Provisionen bleiben unverändert.'],
            ['group' => 'business', 'key' => 'business.notification_cadence_minutes', 'type' => 'integer', 'value' => '45', 'label_de' => 'Abrufintervall für Benachrichtigungen in Sekunden'],
            ['group' => 'business', 'key' => 'business.max_images_per_request', 'type' => 'integer', 'value' => '5', 'label_de' => 'Maximale Anzahl Fotos je Anfrage'],
            ['group' => 'business', 'key' => 'business.max_upload_mb', 'type' => 'integer', 'value' => '10', 'label_de' => 'Maximale Dateigröße in MB'],
            ['group' => 'business', 'key' => 'business.review_redirect_url', 'type' => 'string', 'label_de' => 'Weiterleitung nach guter Bewertung', 'help_de' => 'Zum Beispiel das öffentliche Bewertungsprofil.'],
            ['group' => 'business', 'key' => 'business.review_min_rating', 'type' => 'integer', 'value' => '8', 'label_de' => 'Mindestbewertung für die Weiterleitung'],
            ['group' => 'business', 'key' => 'business.review_delay_days', 'type' => 'integer', 'value' => '3', 'label_de' => 'Bewertungsanfrage nach Tagen'],
            ['group' => 'business', 'key' => 'business.require_valid_liability_cover', 'type' => 'boolean', 'value' => '1', 'label_de' => 'Gültigen Haftpflichtnachweis für die Vermittlung verlangen', 'help_de' => 'Ist dies aktiv, erhalten Partner mit abgelaufenem Nachweis keine neuen Anfragen. Der Nachweis wird in jedem Fall überwacht und es wird rechtzeitig erinnert.'],
            ['group' => 'business', 'key' => 'business.generate_commission_invoices', 'type' => 'boolean', 'value' => '1', 'label_de' => 'Provisionsrechnungen als PDF erzeugen'],
            ['group' => 'features', 'key' => 'features.collect_bank_details', 'type' => 'boolean', 'value' => '', 'label_de' => 'Bankverbindung der Partner erheben', 'help_de' => 'Nur aktivieren, wenn die Provision per Überweisung abgerechnet wird. Ist dies aus, wird keine IBAN gespeichert.'],
            ['group' => 'business', 'key' => 'business.retention_days', 'type' => 'integer', 'value' => '1095', 'label_de' => 'Aufbewahrung von Anfragedaten in Tagen', 'help_de' => 'Danach werden Kundendaten anonymisiert.'],

            ['group' => 'seo', 'key' => 'seo.default_title', 'type' => 'string', 'value' => 'Kfz-Sachverständigen finden — DKGZ Deutsche KFZ-Gutachterzentrale', 'label_de' => 'Standard-Seitentitel'],
            ['group' => 'seo', 'key' => 'seo.default_description', 'type' => 'text', 'value' => 'DKGZ vermittelt Ihre Anfrage bundesweit an geprüfte Kfz-Sachverständige. Kostenfrei, ohne Registrierung und ohne Angebotsvergleich.', 'label_de' => 'Standard-Beschreibung'],
            ['group' => 'seo', 'key' => 'seo.og_image', 'type' => 'file', 'label_de' => 'Vorschaubild für soziale Netzwerke'],
            ['group' => 'seo', 'key' => 'seo.robots', 'type' => 'string', 'value' => 'index, follow', 'label_de' => 'Robots-Anweisung'],
            ['group' => 'seo', 'key' => 'seo.sitemap_enabled', 'type' => 'boolean', 'value' => '1', 'label_de' => 'Sitemap ausliefern'],

            ['group' => 'features', 'key' => 'features.self_registration', 'type' => 'boolean', 'value' => '1', 'label_de' => 'Registrierung für Sachverständige geöffnet'],
            ['group' => 'features', 'key' => 'features.invitations', 'type' => 'boolean', 'value' => '1', 'label_de' => 'Einladungen aktiv'],
            ['group' => 'features', 'key' => 'features.review_flow', 'type' => 'boolean', 'value' => '1', 'label_de' => 'Bewertungen aktiv'],
            ['group' => 'features', 'key' => 'features.image_uploads', 'type' => 'boolean', 'value' => '1', 'label_de' => 'Fotos in der Anfrage erlauben'],
            ['group' => 'features', 'key' => 'features.maintenance_mode', 'type' => 'boolean', 'value' => '0', 'label_de' => 'Wartungsmodus'],
            ['group' => 'features', 'key' => 'features.maintenance_message', 'type' => 'text', 'value' => 'Die Seite wird derzeit gewartet. Bitte versuchen Sie es in Kürze erneut.', 'label_de' => 'Text im Wartungsmodus'],
        ]);
    }
}
