# DKGZ — Vorführung für den Kunden

Ein durchgehender Durchlauf: Anfrage stellen, Vermittlung beobachten, Auftrag
annehmen, abschließen, bewerten. Dauer etwa zwölf Minuten.

**Vorbereitung** (einmalig auf dem Server):

```
php artisan dkgz:seed-demo
```

Danach zurücksetzen mit `php artisan dkgz:reset-demo`.

---

## Zugangsdaten

| Rolle | Adresse | Passwort |
|---|---|---|
| Administration | `admin@dkgz.de` | wird beim Einrichten gesetzt |
| Partner 1 · bundesweit, verfügbar | `nordrhein@demo.dkgz.test` | `DkgzDemo2026!` |
| Partner 2 · 40000–42999, verfügbar | `rheinbogen@demo.dkgz.test` | `DkgzDemo2026!` |
| Partner 3 · nicht verfügbar | `sander@demo.dkgz.test` | `DkgzDemo2026!` |

Partner melden sich unter `/anmelden` an, die Administration unter `/admin/anmelden`.

---

## 1 · Die Anfrage stellen

1. Öffnen Sie die Startseite.
2. Geben Sie im Hero die Postleitzahl **40589** ein und klicken Sie **Gutachter anfragen**.
3. Füllen Sie das Formular aus:
   - Art des Gutachtens: **Unfallgutachten**
   - Fahrzeug: **VW Passat B8**, Baujahr **2019**
   - Name, Telefon und eine E-Mail-Adresse, die Sie abrufen können
   - Dringlichkeit: **Innerhalb von zwei Werktagen**
4. **Anfrage absenden.**

**Zu zeigen:** Die Bestätigungsseite nennt die Vorgangsnummer und sagt ausdrücklich,
dass die Anfrage an *alle* passenden Partner geht, deren Einsatzgebiet 40589 abdeckt.
Es gibt kein Konto, keine Registrierung, keinen Angebotsvergleich.

---

## 2 · Beide Partner sehen dieselbe Anfrage

5. Melden Sie sich in einem zweiten Browserfenster als **Partner 1** an.
6. Öffnen Sie **Neue Anfragen**.
7. Melden Sie sich in einem dritten Fenster (oder privaten Fenster) als **Partner 2** an.

**Zu zeigen:**
- Beide sehen denselben Vorgang.
- Die Spalte **Kunde** zeigt einen grauen Balken mit Schloss: *„Sichtbar nach Annahme“*.
  Name, Telefon und E-Mail werden nicht nur ausgeblendet — sie werden gar nicht erst
  an den Browser gesendet.
- Die Spalte **Frist** zeigt eine echte Uhrzeit.
- Partner 3 (nicht verfügbar) erhält die Anfrage nicht.

---

## 3 · Annahme — und der Wettlauf

8. Öffnen Sie als **Partner 1** die Anfrage über **Details**.
9. Zeigen Sie das Feld **Frist zur Annahme** und den Satz
   *„Danach geht die Anfrage an weitere Partner im Gebiet.“*
10. Klicken Sie **Auftrag annehmen** und bestätigen Sie.

**Zu zeigen:**
- Die Kontaktdaten der anfragenden Person erscheinen sofort — Name, Telefon, E-Mail.
- Wechseln Sie ins Fenster von **Partner 2** und laden Sie neu: Der Vorgang ist
  verschwunden. Unter **Abgelehnt** steht er als *„Von anderem Partner angenommen“*.
- Der Kunde erhält die E-Mail *Auftrag bestätigt* mit den Daten des Sachverständigen.

---

## 4 · Den Auftrag bearbeiten

11. Als **Partner 1**: **Meine Aufträge** öffnen, den Auftrag anklicken.
12. **Bearbeitung beginnen.**
13. Laden Sie unter **Gutachten hochladen** eine beliebige PDF-Datei hoch.

**Zu zeigen:** Der Abschluss-Bereich sagt genau, was noch fehlt:
*„Rechnung fehlt noch“* — nicht „fast fertig“. Die Statusleiste links wandert mit.

14. Laden Sie unter **Rechnung an den Kunden** eine zweite PDF-Datei hoch.

---

## 5 · Abschluss und Provision

15. Klicken Sie **Auftrag abschließen**.
16. Tragen Sie als Honorar **850,00** ein.

**Zu zeigen:** Noch während der Eingabe rechnet der Dialog mit:
- DKGZ-Vermittlungsprovision 15 % → **127,50 €**
- Verbleibt bei Ihnen → **722,50 €**

17. Setzen Sie den Haken und klicken Sie **Abschluss bestätigen**.

**Zu zeigen:**
- Der Kunde erhält Gutachten und Rechnung per E-Mail.
- Im Portal unter **Provisionen** steht die Provision mit dem Satz, der zum
  Zeitpunkt des Abschlusses galt — spätere Änderungen des Satzes ändern sie nie.
- **Es wird an keiner Stelle Geld bewegt.** Die Plattform erfasst nur, was
  berechnet wurde.

---

## 6 · Die Bewertung

18. Rufen Sie die Bewertungs-E-Mail beim Kunden ab und öffnen Sie den Link.
19. Vergeben Sie **9 von 10** und senden Sie ab.

**Zu zeigen:** Auf dem Telefon bricht die Skala auf 5 × 2 mit größeren Feldern um.
Ab der eingestellten Mindestbewertung kann auf ein öffentliches Bewertungsprofil
weitergeleitet werden; darunter wird stattdessen intern nachgefragt.

---

## 7 · Die Administration

20. Melden Sie sich unter `/admin/anmelden` an.

**Zu zeigen:**
- **Dashboard:** sechs Kennzahlen, Anfragen pro Woche, und **Erfordert Aufmerksamkeit**
  — jeder Vorgang, den die Plattform nicht allein lösen kann, mit Angabe, seit wann.
- **Anfragen → Detail:** der vollständige **Vermittlungsverlauf** — wer wann
  benachrichtigt wurde, wer hingesehen hat, wie schnell geantwortet wurde.
- **Sachverständige → Detail:** Annahmequote, die eingereichten Nachweise als
  herunterladbare Dateien mit Gültigkeitsstand.
- **Provisionen:** Register mit Monatsfilter und Summenzeile; **Register exportieren**
  liefert eine CSV-Datei.
- **Inhalte:** jeder Text der öffentlichen Seiten ist hier änderbar, Bilder mit
  Hochladen, Ersetzen und Entfernen.

---

## 8 · Was bewusst fehlt

Zum Abschluss ausdrücklich benennen:

- **Keine Zahlungsabwicklung.** Nirgends. Die Provision wird erfasst, nicht eingezogen.
- **Kein Angebotsvergleich.** Der Kunde wählt nicht aus — der erste verfügbare
  Partner übernimmt.
- **Kein Kundenkonto.** Eine Anfrage, eine Referenznummer, fertig.
