<?php

namespace App\Support;

/**
 * Reads the DNS an operator actually has, and states what is missing.
 *
 * Mail from a domain with no SPF record is filed as spam by most large German
 * providers, which on this platform means a partner never learns a request came
 * in. That failure is invisible from inside the application — everything reports
 * "sent" — so the check has to look outward at DNS.
 */
class MailDomainCheck
{
    public const STATE_OK = 'ok';

    public const STATE_MISSING = 'missing';

    public const STATE_PROBLEM = 'problem';

    /** @return array<string, mixed> */
    public static function run(?string $fromAddress = null): array
    {
        $fromAddress ??= Settings::get('email.from_address');
        $domain = self::domainOf($fromAddress);

        if ($domain === null) {
            return [
                'domain' => null,
                'checked_at' => now(),
                'records' => [],
                'available' => false,
                'message' => 'Es ist keine gültige Absenderadresse hinterlegt.',
            ];
        }

        if (! function_exists('dns_get_record')) {
            return [
                'domain' => $domain,
                'checked_at' => now(),
                'records' => [],
                'available' => false,
                'message' => 'Dieser Server erlaubt keine DNS-Abfragen. Bitte prüfen Sie die Einträge extern.',
            ];
        }

        return [
            'domain' => $domain,
            'checked_at' => now(),
            'available' => true,
            'message' => null,
            'records' => [
                self::checkSpf($domain),
                self::checkDmarc($domain),
                self::checkDkim($domain),
            ],
        ];
    }

    public static function domainOf(?string $address): ?string
    {
        if (blank($address) || ! str_contains((string) $address, '@')) {
            return null;
        }

        $domain = strtolower(trim(substr((string) $address, strrpos((string) $address, '@') + 1)));

        return $domain === '' ? null : $domain;
    }

    /** @return array<string, mixed> */
    private static function checkSpf(string $domain): array
    {
        $records = self::txt($domain);
        $spf = collect($records)->first(fn (string $t) => str_starts_with(strtolower($t), 'v=spf1'));

        if ($spf === null) {
            return self::record(
                'SPF', 'TXT', $domain, self::suggestedSpf(), self::STATE_MISSING,
                'Es ist kein SPF-Eintrag vorhanden. Ohne ihn landen Ihre E-Mails bei vielen Anbietern im Spam-Ordner.'
            );
        }

        if (count(array_filter($records, fn (string $t) => str_starts_with(strtolower($t), 'v=spf1'))) > 1) {
            return self::record(
                'SPF', 'TXT', $domain, $spf, self::STATE_PROBLEM,
                'Es sind mehrere SPF-Einträge vorhanden. Erlaubt ist genau einer — fassen Sie sie zusammen.'
            );
        }

        if (str_contains($spf, '+all')) {
            return self::record(
                'SPF', 'TXT', $domain, $spf, self::STATE_PROBLEM,
                'Der Eintrag endet auf „+all“ und erlaubt damit jedem Server, in Ihrem Namen zu senden. Verwenden Sie „~all“ oder „-all“.'
            );
        }

        return self::record('SPF', 'TXT', $domain, $spf, self::STATE_OK, 'Ein gültiger SPF-Eintrag ist vorhanden.');
    }

    /** @return array<string, mixed> */
    private static function checkDmarc(string $domain): array
    {
        $records = self::txt('_dmarc.'.$domain);
        $dmarc = collect($records)->first(fn (string $t) => str_starts_with(strtolower($t), 'v=dmarc1'));

        if ($dmarc === null) {
            return self::record(
                'DMARC', 'TXT', '_dmarc.'.$domain, self::suggestedDmarc($domain), self::STATE_MISSING,
                'Es ist kein DMARC-Eintrag vorhanden. Er legt fest, wie Empfänger mit gefälschten Absendern umgehen sollen.'
            );
        }

        if (str_contains(strtolower($dmarc), 'p=none')) {
            return self::record(
                'DMARC', 'TXT', '_dmarc.'.$domain, $dmarc, self::STATE_PROBLEM,
                'Die Richtlinie steht auf „p=none“ — es wird nur beobachtet, nichts abgewiesen. Für den Start ist das in Ordnung; stellen Sie später auf „p=quarantine“.'
            );
        }

        return self::record('DMARC', 'TXT', '_dmarc.'.$domain, $dmarc, self::STATE_OK, 'Ein gültiger DMARC-Eintrag ist vorhanden.');
    }

    /**
     * DKIM lives on a selector the provider chooses, so it cannot be discovered
     * without knowing it. The panel states the requirement rather than pretending
     * to have checked something it cannot see.
     *
     * @return array<string, mixed>
     */
    private static function checkDkim(string $domain): array
    {
        $selector = Settings::get('email.dkim_selector');

        if (blank($selector)) {
            return self::record(
                'DKIM', 'TXT', "<selector>._domainkey.{$domain}", 'Vom E-Mail-Anbieter bereitgestellt',
                self::STATE_MISSING,
                'DKIM wird vom Versanddienst bereitgestellt. Tragen Sie den Selector unten ein, damit er hier geprüft werden kann.'
            );
        }

        $host = "{$selector}._domainkey.{$domain}";
        $records = self::txt($host);
        $dkim = collect($records)->first(fn (string $t) => str_contains(strtolower($t), 'p='));

        return $dkim === null
            ? self::record('DKIM', 'TXT', $host, 'Vom E-Mail-Anbieter bereitgestellt', self::STATE_MISSING,
                "Unter „{$host}“ ist kein Schlüssel hinterlegt. Prüfen Sie den Selector und den Eintrag beim Anbieter.")
            : self::record('DKIM', 'TXT', $host, $dkim, self::STATE_OK, 'Ein DKIM-Schlüssel ist hinterlegt.');
    }

    /** @return array<int, string> */
    private static function txt(string $host): array
    {
        $records = @dns_get_record($host, DNS_TXT);

        if ($records === false) {
            return [];
        }

        return collect($records)
            ->map(fn (array $r) => $r['txt'] ?? implode('', $r['entries'] ?? []))
            ->filter()
            ->values()
            ->all();
    }

    private static function suggestedSpf(): string
    {
        $host = Settings::get('integrations.smtp_host');

        $include = match (true) {
            str_contains((string) $host, 'brevo') || str_contains((string) $host, 'sendinblue') => 'include:spf.brevo.com',
            str_contains((string) $host, 'mailgun') => 'include:mailgun.org',
            str_contains((string) $host, 'postmark') => 'include:spf.mtasv.net',
            str_contains((string) $host, 'sendgrid') => 'include:sendgrid.net',
            filled($host) => 'a:'.$host,
            default => 'include:_spf.ihr-anbieter.de',
        };

        return "v=spf1 {$include} ~all";
    }

    private static function suggestedDmarc(string $domain): string
    {
        $reports = Settings::get('email.admin_recipient') ?: "postmaster@{$domain}";

        return "v=DMARC1; p=quarantine; rua=mailto:{$reports}; adkim=r; aspf=r; pct=100";
    }

    /** @return array<string, mixed> */
    private static function record(
        string $name,
        string $type,
        string $host,
        string $value,
        string $state,
        string $explanation,
    ): array {
        return compact('name', 'type', 'host', 'value', 'state', 'explanation');
    }
}
