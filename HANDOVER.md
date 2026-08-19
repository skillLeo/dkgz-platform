# Handbuch für die Administration

Diese Anleitung richtet sich an die Betreiber der DKGZ-Plattform. Sie setzt
keine technischen Kenntnisse voraus. Alles, was hier beschrieben ist, ändern Sie
selbst im Administrationsbereich — ohne Programmierung und ohne dass jemand
etwas neu hochladen muss.

---

## 1. Anmelden

Die Administration erreichen Sie unter:

    https://dkgz.skillleo.com/admin/anmelden

Melden Sie sich mit der E-Mail-Adresse und dem Passwort an, die Sie bei der
Einrichtung erhalten haben.

**Bitte ändern Sie das Passwort bei der ersten Anmeldung.** Sie finden die
Funktion unter *Benutzer* → Ihr Konto.

Sachverständige melden sich an einer anderen Adresse an:

    https://dkgz.skillleo.com/anmelden

---

## 2. Die Übersicht verstehen

Nach der Anmeldung sehen Sie fünf Kennzahlen:

| Kachel | Bedeutung |
|---|---|
| **Offene Anfragen** | Eingegangen, aber noch nicht vergeben |
| **Nicht vermittelt** | Kein Partner deckt das Gebiet ab, oder alle haben abgelehnt |
| **Laufende Aufträge** | Angenommen, aber noch nicht abgeschlossen |
| **Wartende Partner** | Registrierungen, die auf Ihre Prüfung warten |
| **Offene Provision** | Bereits verdient, aber noch nicht abgerechnet |

**„Nicht vermittelt“ ist die wichtigste Kachel.** Steht dort eine Zahl größer
als null, wartet eine Kundenanfrage darauf, dass sich jemand kümmert. Klicken
Sie die Kachel an, um die betroffenen Anfragen zu sehen.

---

## 3. Einen Partner freigeben

1. **Sachverständige** im Menü öffnen.
2. Oben auf **Status** filtern und *Wartet auf Prüfung* wählen.
3. Den Eintrag anklicken.
4. Prüfen Sie Firmenname, Anschrift, USt-IdNr., Zertifizierungsstelle und
   Zertifizierungsnummer. Rechts sehen Sie, ob ein Qualifikationsnachweis
   hinterlegt ist.
5. **Freigeben** — der Partner erhält sofort eine E-Mail und ab diesem Moment
   passende Anfragen.

**Ablehnen** verlangt eine Begründung. Diese Begründung bekommt der
Sachverständige wörtlich per E-Mail, formulieren Sie sie also so, dass er weiß,
was er nachreichen muss.

**Sperren** nimmt einen bereits freigegebenen Partner aus der Verteilung. Auch
hier ist eine Begründung Pflicht. Der Zugang bleibt bestehen, der Partner sieht
aber nur noch den Hinweis auf die Sperre. Über **Sperre aufheben** machen Sie
das jederzeit rückgängig.

> Ein Partner erhält nur dann Anfragen, wenn er freigegeben **und** auf
> „verfügbar“ gestellt ist **und** ein Einsatzgebiet **und** mindestens eine
> Leistung hinterlegt hat. Fehlt eines davon, bleibt er stumm. Auf der
> Detailseite sehen Sie alle vier Angaben auf einen Blick.

---

## 4. Nachvollziehen, was mit einer Anfrage passiert ist

**Anfragen** → eine Anfrage anklicken.

Unter *Vermittlungsverlauf* steht für jeden angefragten Sachverständigen:

- **Benachrichtigt** — wann die E-Mail hinausging
- **Geöffnet** — wann er die Anfrage im Portal angesehen hat, oder „nicht geöffnet“
- **Antwort** — wann er entschieden hat
- **Ergebnis** — angenommen, abgelehnt (mit Begründung), geschlossen oder offen

„Geschlossen“ heißt: ein anderer war schneller. Das ist kein Fehler, sondern das
normale Verhalten — der erste verfügbare Partner übernimmt.

**Erneut vermitteln** schickt die Anfrage noch einmal an alle passenden Partner.
Das ist sinnvoll, wenn Sie gerade einen neuen Partner für die Region
freigegeben haben. Bereits abgelehnte Partner werden dabei nicht erneut
belästigt.

---

## 5. Texte auf der Website ändern

**Inhalte** im Menü. Links wählen Sie die Seite, rechts stehen alle Texte dieser
Seite in derselben Reihenfolge, in der sie auf der Website erscheinen.

Ändern, **Inhalte speichern**, fertig — die Änderung ist sofort öffentlich
sichtbar.

Für Impressum, Datenschutzerklärung, AGB und Widerrufsbelehrung gibt es einen
eigenen Punkt: **Seiten**. Dort steht der komplette Rechtstext. Überschriften
werden als `<h2>…</h2>` geschrieben, Absätze als `<p>…</p>`.

Häufige Fragen pflegen Sie unter **FAQ**. Mit den Pfeilen ändern Sie die
Reihenfolge; sie erscheinen genau so auf der Startseite.

---

## 6. Logo und Farben ändern

**Erscheinungsbild** im Menü.

- **Name der Plattform** und **Zusatz zur Wortmarke** stehen im Kopfbereich und
  in jeder E-Mail.
- **Logo für helle Flächen** und **Logo für dunkle Flächen** ersetzen die
  Schriftmarke. Ohne Logo wird die gesetzte Wortmarke verwendet, was ebenfalls
  gut aussieht — Sie müssen also nichts hochladen.
- Darunter steht jede Farbe des Systems einzeln. Tragen Sie einen Hexwert ein,
  etwa `#14294A`. Das Kästchen daneben zeigt sofort, welche Farbe das ist.

**Die Änderung wirkt sofort auf der gesamten Website**, einschließlich der
E-Mails. Es muss nichts neu gebaut oder hochgeladen werden.

Wenn Sie sich verfärbt haben: die ursprünglichen Werte stehen im Hilfetext unter
jedem Feld.

---

## 7. E-Mail-Versand einrichten

**Integrationen** im Menü. Tragen Sie die Zugangsdaten Ihres Mailanbieters ein:

| Feld | Typischer Wert |
|---|---|
| SMTP-Server | `smtp.ihr-anbieter.de` |
| Port | `587` |
| Verschlüsselung | `tls` |
| Benutzername | meist die volle E-Mail-Adresse |
| Passwort | das Postfachpasswort |

Speichern, dann **Testmail senden** — Ihre eigene Adresse eintragen und
absenden.

- Kommt die Mail an, ist alles richtig.
- Kommt sie nicht an, erscheint direkt auf der Seite die Fehlermeldung des
  Mailservers im Wortlaut. Diese Meldung sagt Ihnen, was falsch ist — meist ein
  Tippfehler im Benutzernamen oder ein falscher Port.

Das Passwort wird verschlüsselt gespeichert und nie wieder angezeigt. Solange
Sie das Feld leer lassen, bleibt das gespeicherte Passwort unverändert.

> Solange kein SMTP-Server hinterlegt ist, erscheint oben ein Warnhinweis.
> Nehmen Sie ihn ernst: ohne funktionierenden Versand erfahren Sachverständige
> nichts von neuen Anfragen.

---

## 8. Eine E-Mail-Vorlage ändern

**E-Mail-Vorlagen** → eine Vorlage anklicken.

Sie ändern **Betreff**, **Vorschautext** und **Inhalt**. Rechts sehen Sie
laufend eine Vorschau, wie die fertige Nachricht aussieht.

Unter *Platzhalter* stehen die Bausteine, die das System beim Versand einsetzt,
etwa `{{ nachname }}` oder `{{ referenz }}`. Ein Klick fügt sie in den Text ein.
Schreiben Sie sie genau so, mit den geschweiften Klammern.

Kopfband, Fußzeile und die Rechtszeile stammen aus einem gemeinsamen Baustein
und sind bewusst nicht je Vorlage änderbar — so bleiben alle Nachrichten
einheitlich. Diese Angaben pflegen Sie unter **Kontakt und Firma**.

Mit **Test senden** schicken Sie sich die Vorlage mit Beispieldaten zu.

---

## 9. Rollen und Berechtigungen

**Rollen** im Menü. Jede Rolle ist ein Bündel von Berechtigungen; die
Berechtigungen sind einzeln an- und abwählbar, mit *Alle*/*Keine* je Gruppe.

Mitgeliefert sind:

| Rolle | Gedacht für |
|---|---|
| **Systemadministration** | alles, einschließlich Rollen und Integrationen |
| **Administration** | alles Operative; keine Rollen, keine Integrationen |
| **Vermittlung** | Anfragen, Aufträge, Partner, Provisionen; keine Einstellungen |
| **Kundenbetreuung** | nur lesen |
| **Redaktion** | nur Inhalte, Seiten, FAQ, E-Mail-Vorlagen, Erscheinungsbild |
| **Sachverständiger** | ausschließlich das Partnerportal |

*Systemadministration* und *Sachverständiger* sind fest und nicht änderbar. Eine
Rolle lässt sich nur löschen, wenn ihr kein Benutzer mehr zugeordnet ist. Und
Sie können sich die Rollenverwaltung nicht selbst entziehen — das ließe sich
nicht rückgängig machen.

Neue Mitarbeiter legen Sie unter **Benutzer** an.

---

## 10. Das Provisionsregister lesen

**Provisionen** im Menü. Oben stehen drei Summen: offen, berechnet, beglichen.

Jede Zeile zeigt Honorar, Satz und Provisionsbetrag.

> **Wichtig:** Der Satz in einer Zeile ist der Satz, der beim Abschluss dieses
> Auftrags galt. Wenn Sie den Provisionssatz später unter *Geschäftsregeln*
> ändern, gilt der neue Satz nur für neue Abschlüsse. Bereits abgerechnete
> Vorgänge bleiben unverändert — das ist Absicht und buchhalterisch notwendig.

Auf der Detailseite einer Provision:

- **Abrechnung erzeugen** erstellt eine fortlaufend nummerierte Rechnung als PDF
  (`DKGZ-RE-2026-0001`, `-0002`, …) und schickt sie dem Sachverständigen zu.
- **Beglichen** vermerkt den Zahlungseingang.
- **Erlassen** verzichtet auf die Provision. Eine Begründung ist Pflicht, und
  der Vorgang wird protokolliert.

Über **Export** laden Sie das Register als CSV für Ihre Buchhaltung. Die Datei
ist mit Semikolon getrennt und öffnet sich in Excel direkt richtig.

---

## 11. Partner einladen

**Einladungen** → *Einladung senden*. Sie tragen die E-Mail-Adresse ein und
optional eine persönliche Nachricht, die als Zitat in der Einladung erscheint.

Ein eingeladener Partner gilt als bereits geprüft: er startet freigegeben und
muss nur noch Einsatzgebiet und Leistungen hinterlegen.

Die Einladung ist 14 Tage gültig. *Erneut senden* erzeugt einen neuen Link und
setzt die Frist zurück, der alte Link wird dabei ungültig.

---

## 12. Was Sie sonst noch einstellen können

**Einstellungen** im Menü:

- **Geschäftsregeln** — Provisionssatz, Frist zur Annahme, maximale Zahl an
  Fotos, Bewertungsschwelle für die Weiterleitung, Aufbewahrungsfrist
- **Kontakt und Firma** — Anschrift, Telefon, USt-IdNr., Geschäftsführung,
  Registergericht. Diese Angaben stehen im Impressum, im Fuß jeder E-Mail und
  auf der Provisionsrechnung.
- **Funktionen** — Registrierung öffnen oder schließen, Einladungen,
  Bewertungen, Foto-Upload, Wartungsmodus
- **Suchmaschinen** — Seitentitel, Beschreibung, Sitemap

**Wartungsmodus** schaltet die öffentliche Website ab und zeigt Besuchern einen
Hinweis. Sie selbst und alle Mitarbeiter kommen weiterhin überall hin — Sie
können sich also nicht aussperren.

---

## 13. Wenn etwas nicht stimmt

**System** im Menü zeigt den Betriebszustand:

- **Warteschlange** — offene Hintergrundaufträge. Eine dauerhaft wachsende Zahl
  bedeutet fast immer, dass der Minuten-Cronjob auf dem Server fehlt. Ohne ihn
  wird keine einzige E-Mail versendet.
- **E-Mail-Fehler** — unten stehen die letzten Fehlschläge mit der wörtlichen
  Meldung des Mailservers.
- **Prüfungen** — vier Punkte, die im Livebetrieb alle grün sein sollten.

**Protokoll** im Menü zeigt, wer was wann geändert hat: Freigaben, Sperren,
Provisionsentscheidungen, Rollenänderungen.

Bei technischen Problemen wenden Sie sich an Ihre Entwicklung und nennen Sie,
was auf der Seite **System** steht — das beantwortet die meisten Rückfragen
sofort.
