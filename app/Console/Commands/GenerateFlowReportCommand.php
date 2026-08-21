<?php

namespace App\Console\Commands;

use App\Models\EmailTemplate;
use App\Models\Page;
use App\Models\ServiceType;
use App\Support\Formatter;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;

/**
 * Writes FLOW_REPORT.md from the codebase itself.
 *
 * The hand-written version drifted: screen counts that did not sum, a test count
 * from an older run, an e-mail table missing templates that existed. A document
 * that goes to a client cannot be maintained by memory, so every figure here is
 * counted at the moment of writing — from the filesystem, the router, the
 * scheduler and the database.
 */
class GenerateFlowReportCommand extends Command
{
    protected $signature = 'dkgz:generate-flow-report
        {--path=FLOW_REPORT.md : Zieldatei}
        {--with-tests : Testlauf ausführen und die Zahlen aufzeichnen}';

    protected $description = 'Erzeugt FLOW_REPORT.md aus dem Code, damit die Zahlen nicht auseinanderlaufen.';

    /** Who each template is written to, keyed by template. */
    private const RECIPIENTS = [
        'anfrage-eingegangen' => ['Kunde', 'Anfrage abgesendet'],
        'neue-anfrage-im-gebiet' => ['Partner', 'Anfrage wurde vermittelt'],
        'auftrag-bestaetigt' => ['Kunde', 'Partner hat angenommen'],
        'auftrag-vergeben' => ['Partner', 'Ein anderer Partner hat angenommen'],
        'auftrag-abgeschlossen' => ['Kunde', 'Auftrag abgeschlossen — mit Gutachten und Rechnung'],
        'keine-sachverstaendigen-gefunden' => ['Büro', 'Kein Partner im Gebiet'],
        'bewertungsanfrage' => ['Kunde', 'Nach der eingestellten Frist ab Abschluss'],
        'bewertung-erhalten' => ['Büro', 'Bewertung abgegeben'],
        'registrierung-eingegangen' => ['Partner', 'Registrierung abgesendet'],
        'neue-registrierung-zur-pruefung' => ['Büro', 'Registrierung abgesendet'],
        'registrierung-freigegeben' => ['Partner', 'Administration hat freigegeben'],
        'registrierung-abgelehnt' => ['Partner', 'Administration hat abgelehnt'],
        'konto-gesperrt' => ['Partner', 'Administration hat gesperrt'],
        'einladung-partnerschaft' => ['Eingeladene Person', 'Administration versendet Einladung'],
        'anfrage-angebot' => ['Externe Person ohne Zugang', 'Administration sendet eine Anfrage von Hand'],
        'email-bestaetigen' => ['Nutzer', 'Adresse muss bestätigt werden'],
        'passwort-zuruecksetzen' => ['Nutzer', 'Zurücksetzen angefordert'],
        'provisionsabrechnung' => ['Partner', 'Monatliche Abrechnung'],
        'haftpflicht-laeuft-ab' => ['Partner', '30, 14 und 3 Tage vor Ablauf sowie am Ablauftag'],
        'anfrage-keine-rueckmeldung' => ['Kunde', 'Alle Partner abgelehnt, manuell geschlossen oder kein Partner im Gebiet'],
        'kontaktanfrage' => ['Büro', 'Kontaktformular abgesendet'],
        'testmail' => ['Gewählte Adresse', 'Administration prüft den Versand'],
    ];

    public function handle(Schedule $schedule): int
    {
        $screens = $this->screens();
        $tests = $this->testCounts();

        $markdown = $this->render($screens, $tests, $schedule);

        File::put(base_path($this->option('path')), $markdown);

        $this->info('FLOW_REPORT.md geschrieben.');
        $this->line('  Screens: '.$screens['total'].' ('.collect($screens['groups'])
            ->map(fn (int $n, string $g) => "{$g} {$n}")->implode(' · ').')');
        $this->line('  Tests: '.($tests['tests'] ?? 'unbekannt').' · Seiten-Renderings: '.($tests['renders'] ?? 'unbekannt'));

        return self::SUCCESS;
    }

    /** @return array{groups: array<string, int>, total: int} */
    private function screens(): array
    {
        $base = resource_path('js/Pages');
        $groups = [];

        foreach (File::directories($base) as $directory) {
            $groups[basename($directory)] = count(File::files($directory));
        }

        // Pages sitting directly in Pages/ belong to no group; count them anyway
        // so the total can never disagree with the sum of its parts.
        $loose = count(File::files($base));

        if ($loose > 0) {
            $groups['Sonstige'] = $loose;
        }

        ksort($groups);

        return ['groups' => $groups, 'total' => array_sum($groups)];
    }

    /** Where a verified suite run leaves its figures for the report to read. */
    public const SUMMARY_PATH = 'storage/app/private/test-summary.json';

    /**
     * Reads the figures from a recorded suite run.
     *
     * Deliberately does not run the suite itself. Two of the tests shell out to
     * vite and node, and running the whole thing from inside an artisan process
     * makes those flake — the first attempt at this reported 231 passing where a
     * direct run reports 343. A report that goes to a client must not print a
     * number it did not verify, so when there is no recorded run it says so.
     *
     * Refresh with: `php artisan dkgz:generate-flow-report --with-tests`
     *
     * @return array<string, int|string|null>
     */
    private function testCounts(): array
    {
        if ($this->option('with-tests')) {
            $this->recordTestRun();
        }

        $path = base_path(self::SUMMARY_PATH);

        if (! File::exists($path)) {
            return ['tests' => null, 'renders' => null, 'recorded_at' => null];
        }

        $data = json_decode(File::get($path), true);

        return [
            'tests' => $data['tests'] ?? null,
            'renders' => $data['renders'] ?? null,
            'recorded_at' => $data['recorded_at'] ?? null,
        ];
    }

    /**
     * Runs the suite in its own process and records what it reported.
     *
     * The child must not inherit this process's environment. PHPUnit only
     * applies the `<env>` entries in phpunit.xml when the variable is not
     * already defined, so a nested run would keep APP_ENV=local — which leaves
     * CSRF verification on and fails every form test with a 419. That is what
     * produced the misleading "231 passing" on the first attempt.
     */
    private function recordTestRun(): void
    {
        $this->line('Testlauf läuft …');

        $inherited = collect(['APP_ENV', 'APP_MAINTENANCE_DRIVER', 'BCRYPT_ROUNDS', 'BROADCAST_CONNECTION',
            'CACHE_STORE', 'DB_CONNECTION', 'DB_DATABASE', 'DB_URL', 'MAIL_MAILER', 'QUEUE_CONNECTION',
            'SESSION_DRIVER', 'PULSE_ENABLED', 'TELESCOPE_ENABLED', 'NIGHTWATCH_ENABLED'])
            ->map(fn (string $name) => "-u {$name}")
            ->implode(' ');

        $lines = [];
        exec('cd '.escapeshellarg(base_path())." && env {$inherited} php vendor/bin/pest 2>/dev/null", $lines);
        $pest = json_decode(implode('', $lines), true);

        $renderOutput = [];
        exec('cd '.escapeshellarg(base_path()).' && node tests/Js/render-runner.mjs 2>/dev/null', $renderOutput);
        preg_match('/(\d+) Seiten gerendert/', implode(' ', $renderOutput), $m);

        if (is_array($pest) && ($pest['failed'] ?? 0) === 0) {
            $this->line('  '.($pest['passed'] ?? 0).' Tests bestanden, '.($m[1] ?? 0).' Seiten gerendert.');
        }

        // Only a clean run is recorded. Publishing "339 of 343" as a headline
        // figure would be worse than publishing nothing.
        if (! is_array($pest) || ($pest['failed'] ?? 0) > 0) {
            $this->warn('Der Testlauf war nicht sauber — es werden keine Zahlen aufgezeichnet.');

            return;
        }

        File::ensureDirectoryExists(dirname(base_path(self::SUMMARY_PATH)));
        File::put(base_path(self::SUMMARY_PATH), json_encode([
            'tests' => $pest['passed'] ?? $pest['tests'] ?? null,
            'renders' => isset($m[1]) ? (int) $m[1] : null,
            'recorded_at' => now()->toIso8601String(),
        ], JSON_PRETTY_PRINT));
    }

    /** @return Collection<int, array<string, string>> */
    private function publicRoutes(): Collection
    {
        return collect(Route::getRoutes())
            ->filter(fn ($route) => in_array('GET', $route->methods(), true))
            ->map(fn ($route) => ['uri' => '/'.ltrim($route->uri(), '/'), 'name' => $route->getName() ?? ''])
            ->reject(fn (array $r) => str_starts_with(ltrim($r['uri'], '/'), 'admin')
                || str_starts_with(ltrim($r['uri'], '/'), 'portal')
                || str_starts_with(ltrim($r['uri'], '/'), 'api')
                || str_starts_with(ltrim($r['uri'], '/'), '_')
                || str_contains($r['uri'], '{'))
            ->unique('uri')
            ->sortBy('uri')
            ->values();
    }

    /** @return Collection<int, array<string, string>> */
    private function scheduledTasks(Schedule $schedule): Collection
    {
        return collect($schedule->events())
            ->map(fn ($event) => [
                'command' => trim(str_replace([PHP_BINARY, "'"], '', $event->command ?? $event->getSummaryForDisplay())),
                'expression' => $event->expression,
            ])
            ->values();
    }

    private function render(array $screens, array $tests, Schedule $schedule): string
    {
        $templates = EmailTemplate::orderBy('key')->get();
        $roles = Role::with('permissions')->orderBy('name')->get();
        $routes = $this->publicRoutes();
        $tasks = $this->scheduledTasks($schedule);
        $types = ServiceType::orderBy('sort_order')->get();
        $pages = Page::orderBy('slug')->get();

        $routeCount = collect(Route::getRoutes())->count();

        $groupLine = collect($screens['groups'])
            ->map(fn (int $n, string $g) => "{$g} {$n}")
            ->implode(' + ').' = '.$screens['total'];

        $md = "# DKGZ — Ablaufbericht\n\n";
        $md .= '_Erzeugt am '.now()->format('d.m.Y \u\m H:i')." durch `php artisan dkgz:generate-flow-report`._\n";
        $md .= "_Jede Zahl in diesem Dokument wird beim Erzeugen gezählt, nicht eingetragen._\n\n";

        $md .= "## Kennzahlen\n\n";
        $md .= "| Größe | Wert |\n|---|---|\n";
        $md .= "| Bildschirme | {$screens['total']} |\n";
        $md .= '| davon | '.$groupLine." |\n";
        $md .= "| Routen insgesamt | {$routeCount} |\n";
        $stamp = $tests['recorded_at']
            ? ' _(Lauf vom '.Carbon::parse($tests['recorded_at'])->format('d.m.Y H:i').')_'
            : '';
        $md .= '| Tests (bestanden) | '.($tests['tests'] ?? 'nicht ermittelt — `--with-tests` ausführen').$stamp." |\n";
        $md .= '| Gerenderte Seiten | '.($tests['renders'] ?? 'nicht ermittelt')." |\n";
        $md .= '| Rollen | '.$roles->count()." |\n";
        $md .= '| E-Mail-Vorlagen | '.$templates->count()." |\n";
        $md .= '| Geplante Aufgaben | '.$tasks->count()." |\n";
        $md .= '| Gutachtenarten | '.$types->count()." |\n\n";

        $md .= '## Öffentliche Seiten ('.$routes->count().")\n\n";
        $md .= "| Pfad | Route |\n|---|---|\n";
        foreach ($routes as $route) {
            $md .= "| `{$route['uri']}` | {$route['name']} |\n";
        }
        $md .= "\n";

        $md .= '## Rechtliche Seiten ('.$pages->count().")\n\n";
        $md .= "| Slug | Titel | Platzhalter |\n|---|---|---|\n";
        foreach ($pages as $page) {
            $flag = $page->is_placeholder ? 'ja — muss ersetzt werden' : 'nein';
            $md .= "| `/{$page->slug}` | {$page->title_de} | {$flag} |\n";
        }
        $md .= "\n";

        $md .= '## Gutachtenarten ('.$types->count().")\n\n";
        $md .= "Vorläufig und unter Administration → Leistungsarten änderbar.\n\n";
        foreach ($types as $type) {
            $md .= "- {$type->name_de}\n";
        }
        $md .= "\n";

        $md .= '## Rollen und Berechtigungen ('.$roles->count().")\n\n";
        $md .= "| Rolle | Berechtigungen |\n|---|---|\n";
        foreach ($roles as $role) {
            $count = $role->permissions->count();
            $md .= "| `{$role->name}` | {$count} |\n";
        }
        $md .= "\nDie Rolle `assessor` hält bewusst keine Verwaltungsrechte: Partner erreichen ";
        $md .= "ihre eigenen Daten über die Richtlinien des Portals, nie über eine Berechtigung.\n\n";

        $md .= '## E-Mail-Vorlagen ('.$templates->count().")\n\n";
        $md .= "| Schlüssel | Empfänger | Auslöser | Betreff |\n|---|---|---|---|\n";
        foreach ($templates as $template) {
            [$recipient, $trigger] = self::RECIPIENTS[$template->key] ?? ['—', '—'];
            $subject = str_replace('|', '\|', (string) $template->subject_de);
            $md .= "| `{$template->key}` | {$recipient} | {$trigger} | {$subject} |\n";
        }
        $md .= "\n";

        $md .= '## Geplante Aufgaben ('.$tasks->count().")\n\n";
        $md .= "| Ausdruck | Befehl |\n|---|---|\n";
        foreach ($tasks as $task) {
            $command = str_replace('|', '\|', $task['command']);
            $md .= "| `{$task['expression']}` | `{$command}` |\n";
        }
        $md .= "\nOhne den Cron-Eintrag auf dem Server läuft keine dieser Aufgaben — dann wird ";
        $md .= "insbesondere **keine E-Mail versendet**.\n\n";

        $md .= "## Zustände einer Anfrage\n\n";
        $md .= "| Status | Bedeutung |\n|---|---|\n";
        $md .= "| `new` | Eingegangen. Mit `matched_count = 0` bedeutet das: kein Partner im Gebiet. |\n";
        $md .= "| `matched` | An mindestens einen Partner vermittelt und offen. |\n";
        $md .= "| `assigned` | Ein Partner hat angenommen, für alle anderen geschlossen. |\n";
        $md .= "| `completed` | Gutachten und Rechnung liegen vor, Honorar erfasst. |\n";
        $md .= "| `unanswered` | Alle vermittelten Partner haben abgelehnt. Bleibt für eine erneute Vermittlung offen. |\n";
        $md .= "| `cancelled` | Von der Administration manuell geschlossen, mit Begründung. |\n\n";
        $md .= 'Es gibt **keine Annahmefrist**. Eine vermittelte Anfrage bleibt offen, bis ein Partner ';
        $md .= 'sie übernimmt oder die Administration sie mit Begründung schließt. In jedem Fall ohne ';
        $md .= "Vermittlung erhält der Kunde die Nachricht `anfrage-keine-rueckmeldung`.\n\n";

        $md .= "## Gebührenmodell\n\n";
        $md .= 'DKGZ berechnet je vermitteltem Auftrag einen **festen Betrag**, hinterlegt pro ';
        $md .= 'Gutachtenart unter Administration → Leistungsarten. Der Betrag ist dem Partner vor der ';
        $md .= 'Annahme sichtbar und wird bei der Annahme festgeschrieben, sodass eine spätere Änderung ';
        $md .= 'bereits angenommene Aufträge nicht berührt. Ältere Abrechnungen aus der Zeit des ';
        $md .= "Prozentmodells bleiben unverändert und sind über `fee_type` als solche erkennbar.\n\n";

        $md .= "| Gutachtenart | DKGZ-Gebühr |\n|---|---|\n";

        foreach ($types as $type) {
            $fee = $type->dkgz_fee_cents === null ? '— nicht festgelegt' : Formatter::money($type->dkgz_fee_cents);
            $md .= "| {$type->name_de} | {$fee} |\n";
        }

        return $md;
    }
}
