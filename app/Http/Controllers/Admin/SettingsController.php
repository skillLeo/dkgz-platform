<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Branding;
use App\Support\MailDomainCheck;
use App\Support\Mailer;
use App\Support\SafeStorage;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(Request $request, string $group = 'business'): Response
    {
        $this->authorize('viewAny', Setting::class);

        abort_unless(in_array($group, $this->groups(), true), 404);

        return Inertia::render('Admin/Einstellungen', [
            'group' => $group,
            'groups' => $this->groupLabels(),
            // The rate is per service now; the row stays for rollback safety.
            'settings' => collect($this->settingsFor($group))
                ->reject(fn (array $s) => $s['field'] === 'business__commission_rate')
                ->values()
                ->all(),
            'canEdit' => $request->user()->can('updateGroup', [Setting::class, $group]),
            'smtpConfigured' => filled(Settings::get('integrations.smtp_host')),
            'brandingTokens' => $group === 'branding' ? Branding::tokens() : [],
            // The deliverability panel only belongs where mail is configured.
            'deliverability' => in_array($group, ['integrations', 'email'], true)
                ? MailDomainCheck::run()
                : null,
            // Empty host means no mail can leave at all, whatever the DNS says.
            'mailTransportReady' => filled(config('mail.mailers.smtp.host'))
                || filled(Settings::get('integrations.smtp_host'))
                || config('mail.default') !== 'smtp',
            'mailPresets' => in_array($group, ['integrations', 'email'], true)
                ? $this->mailPresets()
                : [],
        ]);
    }

    public function update(Request $request, string $group): RedirectResponse
    {
        abort_unless(in_array($group, $this->groups(), true), 404);
        $this->authorize('updateGroup', [Setting::class, $group]);

        $keys = Setting::where('group', $group)->pluck('type', 'key');
        $payload = [];

        foreach ($keys as $key => $type) {
            $field = str_replace('.', '__', $key);

            if ($type === 'file') {
                if ($request->hasFile($field)) {
                    $request->validate(
                        [$field => ['file', 'mimes:png,jpg,jpeg,svg,webp,ico', 'max:2048']],
                        [],
                        [$field => 'die Datei']
                    );

                    $payload[$key] = $request->file($field)->store('branding', 'public');
                }

                continue;
            }

            if (! $request->has($field)) {
                continue;
            }

            $value = $request->input($field);

            // A colour override must be a literal hex value — it is injected
            // straight into a stylesheet.
            if (str_starts_with($key, 'branding.color_') && filled($value) && ! Branding::isHexColour((string) $value)) {
                return back()->withErrors([$field => 'Bitte geben Sie einen Hexwert an, z. B. #14294A.']);
            }

            $payload[$key] = $type === 'boolean' ? $request->boolean($field) : $value;
        }

        Settings::setMany($payload);

        return back()->with('success', 'Die Einstellungen wurden gespeichert.');
    }

    /**
     * Sends a real message through the configured mailer and prints the real
     * driver error. A generic failure would leave the client unable to fix
     * their own SMTP settings, which is the whole point of the button.
     */
    public function testMail(Request $request): RedirectResponse
    {
        $this->authorize('updateGroup', [Setting::class, 'integrations']);

        $data = $request->validate(
            ['email' => ['required', 'string', 'email']],
            [],
            ['email' => 'die E-Mail-Adresse']
        );

        $result = Mailer::sendTest($data['email']);

        if ($result['ok']) {
            return back()->with('success', "Die Testmail wurde an {$data['email']} versendet.");
        }

        return back()->withErrors(['smtp' => $result['error']]);
    }

    /** @return array<int, array<string, mixed>> */
    private function settingsFor(string $group): array
    {
        return Setting::where('group', $group)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Setting $s) => [
                'key' => $s->key,
                'field' => str_replace('.', '__', $s->key),
                'type' => $s->type,
                'label' => $s->label_de,
                'help' => $s->help_de,
                // A secret is never rendered back: the UI shows whether one is
                // stored, and an empty submission leaves it untouched.
                'value' => $s->isSecret() ? null : $s->typedValue(),
                'is_secret' => $s->isSecret(),
                'has_value' => $s->hasValue(),
                'preview_url' => $s->type === 'file' ? SafeStorage::url($s->rawValue()) : null,
            ])
            ->values()
            ->all();
    }

    /** @return array<int, string> */
    private function groups(): array
    {
        return ['branding', 'contact', 'email', 'integrations', 'business', 'seo', 'features'];
    }

    /** @return array<string, string> */
    private function groupLabels(): array
    {
        return [
            'branding' => 'Erscheinungsbild',
            'contact' => 'Kontakt und Firma',
            'email' => 'E-Mail',
            'integrations' => 'Integrationen',
            'business' => 'Geschäftsregeln',
            'seo' => 'Suchmaschinen',
            'features' => 'Funktionen',
        ];
    }

    /**
     * Re-resolves the sending domain's DNS on demand, so an operator who has
     * just added a record can see it land without waiting for a page cache.
     */
    public function checkMailDomain(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', Setting::class);

        $result = MailDomainCheck::run();

        $failing = collect($result['records'] ?? [])
            ->reject(fn (array $r) => $r['state'] === MailDomainCheck::STATE_OK)
            ->count();

        return back()->with(
            $failing === 0 ? 'success' : 'info',
            $failing === 0
                ? 'SPF, DKIM und DMARC sind korrekt hinterlegt.'
                : "Die Prüfung wurde durchgeführt. {$failing} Eintrag/Einträge benötigen noch Aufmerksamkeit."
        );
    }

    /**
     * Host, port and encryption for the services a German operator is most
     * likely to use. Chosen from a list so nobody has to remember whether
     * Brevo wants 587 or 465.
     *
     * @return array<int, array<string, mixed>>
     */
    private function mailPresets(): array
    {
        return [
            ['id' => 'brevo', 'label' => 'Brevo (Sendinblue)', 'host' => 'smtp-relay.brevo.com', 'port' => 587, 'encryption' => 'tls'],
            ['id' => 'mailgun', 'label' => 'Mailgun (EU)', 'host' => 'smtp.eu.mailgun.org', 'port' => 587, 'encryption' => 'tls'],
            ['id' => 'postmark', 'label' => 'Postmark', 'host' => 'smtp.postmarkapp.com', 'port' => 587, 'encryption' => 'tls'],
            ['id' => 'smtp', 'label' => 'Allgemeiner SMTP-Server', 'host' => '', 'port' => 587, 'encryption' => 'tls'],
        ];
    }
}
