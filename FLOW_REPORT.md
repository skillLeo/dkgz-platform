# DKGZ — Ablaufbericht

_Erzeugt am 20.08.2026 um 17:05 durch `php artisan dkgz:generate-flow-report`._
_Jede Zahl in diesem Dokument wird beim Erzeugen gezählt, nicht eingetragen._

## Kennzahlen

| Größe | Wert |
|---|---|
| Bildschirme | 59 |
| davon | Admin 23 + Auth 11 + Fehler 1 + Portal 12 + Public 12 = 59 |
| Routen insgesamt | 158 |
| Tests (bestanden) | 369 _(Lauf vom 20.08.2026 17:05)_ |
| Gerenderte Seiten | 59 |
| Rollen | 6 |
| E-Mail-Vorlagen | 21 |
| Geplante Aufgaben | 6 |
| Gutachtenarten | 9 |

## Öffentliche Seiten (21)

| Pfad | Route |
|---|---|
| `/` | home |
| `/ablauf` | process |
| `/agb` | legal.terms |
| `/anfrage` | request.create |
| `/anmelden` | login |
| `/datenschutz` | legal.privacy |
| `/email-bestaetigen` | verification.notice |
| `/fuer-sachverstaendige` | partner |
| `/impressum` | legal.imprint |
| `/kontakt` | contact |
| `/konto-gesperrt` | account.blocked |
| `/leistungen` | services |
| `/passwort-vergessen` | password.request |
| `/pruefung-laeuft` | registration.pending |
| `/registrieren` | register |
| `/registrierung-abgelehnt` | registration.rejected |
| `/robots.txt` | robots |
| `/sitemap.xml` | sitemap |
| `/ueber-uns` | about |
| `/up` |  |
| `/widerruf` | legal.withdrawal |

## Rechtliche Seiten (4)

| Slug | Titel | Platzhalter |
|---|---|---|
| `/agb` | Allgemeine Geschäftsbedingungen | ja — muss ersetzt werden |
| `/datenschutz` | Datenschutzerklärung | ja — muss ersetzt werden |
| `/impressum` | Impressum | ja — muss ersetzt werden |
| `/widerruf` | Widerrufsbelehrung | ja — muss ersetzt werden |

## Gutachtenarten (9)

Vorläufig und unter Administration → Leistungsarten änderbar.

- Unfallgutachten
- Haftpflichtgutachten
- Kaskogutachten
- Fahrzeugschadengutachten
- Wertgutachten
- Oldtimergutachten
- Gebrauchtwagen-Check
- Beweissicherung
- Kaskogutachten

## Rollen und Berechtigungen (6)

| Rolle | Berechtigungen |
|---|---|
| `admin` | 42 |
| `assessor` | 0 |
| `content_editor` | 7 |
| `manager` | 24 |
| `super_admin` | 44 |
| `support` | 6 |

Die Rolle `assessor` hält bewusst keine Verwaltungsrechte: Partner erreichen ihre eigenen Daten über die Richtlinien des Portals, nie über eine Berechtigung.

## E-Mail-Vorlagen (21)

| Schlüssel | Empfänger | Auslöser | Betreff |
|---|---|---|---|
| `anfrage-eingegangen` | Kunde | Anfrage abgesendet | Ihre Anfrage ist eingegangen — {{ referenz }} |
| `anfrage-keine-rueckmeldung` | Kunde | Alle Partner abgelehnt, manuell geschlossen oder kein Partner im Gebiet | Ihre Anfrage {{ referenz }} — unser Zwischenstand |
| `auftrag-abgeschlossen` | Kunde | Auftrag abgeschlossen — mit Gutachten und Rechnung | Ihr Gutachten liegt vor — {{ referenz }} |
| `auftrag-bestaetigt` | Kunde | Partner hat angenommen | Auftrag bestätigt — {{ referenz }} |
| `auftrag-vergeben` | Partner | Ein anderer Partner hat angenommen | Anfrage {{ referenz }} wurde bereits vergeben |
| `bewertung-erhalten` | Büro | Bewertung abgegeben | Neue Bewertung: {{ bewertung }} von 10 — {{ referenz }} |
| `bewertungsanfrage` | Kunde | Nach der eingestellten Frist ab Abschluss | Wie zufrieden waren Sie? — {{ referenz }} |
| `einladung-partnerschaft` | Eingeladene Person | Administration versendet Einladung | Einladung in das DKGZ-Partnernetz |
| `email-bestaetigen` | Nutzer | Adresse muss bestätigt werden | Bitte bestätigen Sie Ihre E-Mail-Adresse |
| `haftpflicht-laeuft-ab` | Partner | 30, 14 und 3 Tage vor Ablauf sowie am Ablauftag | Ihr Haftpflichtnachweis läuft am {{ ablaufdatum }} ab |
| `keine-sachverstaendigen-gefunden` | Büro | Kein Partner im Gebiet | Nicht vermittelt: {{ referenz }} ({{ plz }}) |
| `kontaktanfrage` | Büro | Kontaktformular abgesendet | Kontaktanfrage von {{ name }} |
| `konto-gesperrt` | Partner | Administration hat gesperrt | Ihr Zugang wurde gesperrt |
| `neue-anfrage-im-gebiet` | Partner | Anfrage wurde vermittelt | Neue Anfrage in Ihrem Gebiet — {{ gutachtenart }}, {{ plz }} |
| `neue-registrierung-zur-pruefung` | Büro | Registrierung abgesendet | Neue Registrierung: {{ firma }} |
| `passwort-zuruecksetzen` | Nutzer | Zurücksetzen angefordert | Passwort zurücksetzen |
| `provisionsabrechnung` | Partner | Monatliche Abrechnung | Provisionsabrechnung {{ rechnungsnummer }} |
| `registrierung-abgelehnt` | Partner | Administration hat abgelehnt | Ihre Registrierung wurde nicht freigegeben |
| `registrierung-eingegangen` | Partner | Registrierung abgesendet | Ihre Registrierung ist eingegangen |
| `registrierung-freigegeben` | Partner | Administration hat freigegeben | Ihr Zugang ist freigegeben |
| `testmail` | Gewählte Adresse | Administration prüft den Versand | Testmail der DKGZ-Plattform |

## Geplante Aufgaben (6)

| Ausdruck | Befehl |
|---|---|
| `* * * * *` | `artisan queue:work --stop-when-empty --max-time=55 --tries=3` |
| `0 0 * * 0` | `artisan queue:prune-failed --hours=336` |
| `0 * * * *` | `artisan cache:prune-stale-tags` |
| `30 2 * * *` | `artisan dkgz:backup-database` |
| `0 7 * * *` | `artisan dkgz:check-liability-cover` |
| `20 3 * * *` | `artisan dkgz:anonymise-requests` |

Ohne den Cron-Eintrag auf dem Server läuft keine dieser Aufgaben — dann wird insbesondere **keine E-Mail versendet**.

## Zustände einer Anfrage

| Status | Bedeutung |
|---|---|
| `new` | Eingegangen. Mit `matched_count = 0` bedeutet das: kein Partner im Gebiet. |
| `matched` | An mindestens einen Partner vermittelt und offen. |
| `assigned` | Ein Partner hat angenommen, für alle anderen geschlossen. |
| `completed` | Gutachten und Rechnung liegen vor, Honorar erfasst. |
| `unanswered` | Alle vermittelten Partner haben abgelehnt. Bleibt für eine erneute Vermittlung offen. |
| `cancelled` | Von der Administration manuell geschlossen, mit Begründung. |

Es gibt **keine Annahmefrist**. Eine vermittelte Anfrage bleibt offen, bis ein Partner sie übernimmt oder die Administration sie mit Begründung schließt. In jedem Fall ohne Vermittlung erhält der Kunde die Nachricht `anfrage-keine-rueckmeldung`.

## Gebührenmodell

DKGZ berechnet je vermitteltem Auftrag einen **festen Betrag**, hinterlegt pro Gutachtenart unter Administration → Leistungsarten. Der Betrag ist dem Partner vor der Annahme sichtbar und wird bei der Annahme festgeschrieben, sodass eine spätere Änderung bereits angenommene Aufträge nicht berührt. Ältere Abrechnungen aus der Zeit des Prozentmodells bleiben unverändert und sind über `fee_type` als solche erkennbar.

| Gutachtenart | DKGZ-Gebühr |
|---|---|
| Unfallgutachten | 79,00 € |
| Haftpflichtgutachten | 79,00 € |
| Kaskogutachten | 69,00 € |
| Fahrzeugschadengutachten | 69,00 € |
| Wertgutachten | 59,00 € |
| Oldtimergutachten | 89,00 € |
| Gebrauchtwagen-Check | 39,00 € |
| Beweissicherung | 49,00 € |
| Kaskogutachten | — nicht festgelegt |
