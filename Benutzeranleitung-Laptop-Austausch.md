# Laptop-Austausch — Benutzeranleitung
**Kreis Groß-Gerau · IT-Center** · Stand: Juni 2026

---

## Inhaltsverzeichnis

1. [Einleitung](#einleitung)
2. [Teil 1 — Anleitung für Mitarbeitende](#teil-1--anleitung-für-mitarbeitende)
   - [Anmelden](#anmelden)
   - [Das Dashboard](#das-dashboard)
   - [Termin buchen](#termin-buchen)
   - [Software-Formular ausfüllen](#software-formular-ausfüllen)
   - [Termin ansehen](#termin-ansehen)
   - [Termin verschieben](#termin-verschieben)
   - [Dokumente herunterladen](#dokumente--anleitungen-herunterladen)
3. [Teil 2 — Anleitung für Administratoren](#teil-2--anleitung-für-administratoren)
   - [Admin-Anmeldung](#admin-anmeldung)
   - [Kalender](#kalender-startseite-des-admin-panels)
   - [Buchungen verwalten](#buchungen-verwalten)
   - [Warteschlange](#warteschlange)
   - [Mitarbeitende verwalten](#mitarbeitende-verwalten)
   - [Erinnerungen](#erinnerungen)
   - [Software-Katalog](#software-katalog)
   - [Einstellungen](#einstellungen)
   - [Datensicherung](#datensicherung)
   - [Dokumente (Admin)](#dokumente--anleitungen-admin)
   - [Admin-Benutzer verwalten](#admin-benutzer-verwalten)
4. [Anhang](#anhang)

---

## Einleitung

Das Laptop-Austausch-Buchungssystem ermöglicht es Mitarbeitenden des Kreises Groß-Gerau, selbstständig einen Termin für die Übergabe des alten Laptops und die Abholung des neuen Geräts zu vereinbaren. Das IT-Center verwaltet den gesamten Prozess über ein separates Administrations-Panel.

| Benutzergruppe | Bereich |
|---|---|
| Mitarbeitende | Öffentliche Webseite → Termin buchen, Software erfassen |
| IT-Administratoren | Admin-Panel (`/admin`) → Verwaltung, Auswertung, Einstellungen |

**Wichtige Hinweise:**
- Es werden **keine Bestätigungs-E-Mails** verschickt. Bitte notieren Sie Ihren Termin oder laden Sie die Kalender-Datei (`.ics`) herunter.
- Die Anmeldung erfolgt mit **KVGG-Nummer** und **PC-Nummer** (auf dem Aufkleber Ihres Geräts).
- Nach **5 fehlgeschlagenen Anmeldeversuchen** wird der Zugang für **10 Minuten** gesperrt.

---

## Teil 1 — Anleitung für Mitarbeitende

### Anmelden

Rufen Sie die Adresse des Buchungssystems in Ihrem Browser auf. Sie sehen das Anmeldeformular mit dem Kreis-Groß-Gerau-Logo.

**Schritt 1 — KVGG-Nummer eingeben**
Geben Sie Ihre KVGG-Nummer ein (z. B. `kvgg1234`). Groß- und Kleinschreibung spielt keine Rolle.

**Schritt 2 — PC-Nummer eingeben**
Geben Sie Ihre PC-Nummer ein. Diese finden Sie auf dem Aufkleber an Ihrem aktuellen Laptop (z. B. `PC7438`). Auch hier ist die Groß-/Kleinschreibung egal.

**Schritt 3 — „Anmelden" klicken**
Optional: Aktivieren Sie „Angemeldet bleiben", um die erneute Eingabe beim nächsten Besuch zu überspringen.

> **Anmeldung fehlgeschlagen?**
> - Prüfen Sie, ob KVGG-Nummer und PC-Nummer korrekt eingegeben wurden.
> - Nach 5 Fehlversuchen ist der Zugang für 10 Minuten gesperrt.
> - Wenden Sie sich bei anhaltenden Problemen an das IT-Center.

---

### Das Dashboard

Nach der Anmeldung gelangen Sie zum Dashboard. Hier sehen Sie auf einen Blick:

- Ihren **Namen, Abteilung und KVGG-Nummer**
- Ob Sie **bereits einen Termin** gebucht haben (und wenn ja: Datum, Uhrzeit, Ort)
- Schaltflächen zum **Buchen oder Verschieben** eines Termins
- Vom IT-Center bereitgestellte **Dokumente** zum Herunterladen (falls vorhanden)

Haben Sie bereits einen Termin, wird dieser in einer grünen Karte angezeigt. Sie können ihn direkt ansehen oder verschieben.

---

### Termin buchen

Wenn Sie noch keinen Termin haben, klicken Sie auf dem Dashboard auf **„Termin buchen"**.

**Schritt 1 — Kalender aufrufen**
Der Buchungskalender öffnet sich. Er zeigt alle Zeitfenster gruppiert nach Woche an.

**Schritt 2 — Kalenderwoche wählen (optional)**
Über die Schaltflächen oben im Kalender können Sie nach Kalenderwoche filtern, um gezielt eine bestimmte Woche anzuzeigen.

**Schritt 3 — Freies Zeitfenster auswählen**

| Farbe | Bedeutung |
|---|---|
| 🟢 Grün | Verfügbar → klicken zum Buchen |
| 🔴 Rot | Bereits belegt → nicht buchbar |
| ⚫ Grau | Gesperrt (z. B. Feiertag) → nicht buchbar |

Die Verfügbarkeit wird alle 15 Sekunden automatisch aktualisiert.

**Schritt 4 — Termin bestätigen**
Nach dem Klick auf ein grünes Zeitfenster erscheint eine Bestätigungsseite. Prüfen Sie Datum und Uhrzeit und klicken Sie auf **„Termin verbindlich buchen"**.

**Schritt 5 — Software-Formular ausfüllen**
Sie werden direkt zum Software-Formular weitergeleitet. Bitte füllen Sie es aus (siehe nächster Abschnitt).

> **Hinweis:** Pro Mitarbeitende/m kann nur ein aktiver Termin gleichzeitig bestehen. Möchten Sie einen anderen Termin wählen, nutzen Sie „Termin verschieben".

---

### Software-Formular ausfüllen

Damit das IT-Center Ihr neues Gerät korrekt einrichten kann, teilen Sie mit, welche Software auf Ihrem aktuellen Laptop installiert ist. Das Formular erscheint direkt nach der Buchung und kann später jederzeit über **„Ändern"** auf der Termin-Seite bearbeitet werden.

#### Abschnitt: Gerät

Ihre PC-Nummer wird automatisch übernommen. Wählen Sie optional den **Hersteller** Ihres aktuellen Laptops aus der Dropdown-Liste.

#### Abschnitt: Benötigte Software

Hier legen Sie fest, welche Programme auf Ihrem neuen Gerät installiert sein sollen.

- **Grüner Kasten** — Standard-Software, die automatisch auf jedem Gerät installiert wird. Diese müssen Sie nicht extra angeben.
- **Blauer Kasten** — Software, die Sie selbst über das Softwarecenter installieren können.
- **Eingabefeld** — Tippen Sie den Namen eines Programms ein, wählen Sie aus den Vorschlägen oder drücken Sie `Enter`, um ein nicht gelistetes Programm einzutragen.
- Bereits gewählte Programme erscheinen als **Chips** (Badges). Klicken Sie auf **×**, um ein Programm zu entfernen.

> Programme, die nicht in der Liste erscheinen, können Sie einfach eintippen und mit `Enter` hinzufügen. Das IT-Center prüft diese Einträge und gibt sie gegebenenfalls frei.

#### Abschnitt: Zusätzliche Angaben (optional)

Hier können Sie dem IT-Center weitere Hinweise geben, zum Beispiel:
- Peripheriegeräte (zweiter Monitor, Dockingstation)
- Besondere Einstellungen oder Konfigurationen
- Sonstige Anmerkungen zum Gerät

Klicken Sie abschließend auf **„Speichern"**, um Ihre Angaben zu übermitteln.

---

### Termin ansehen

Klicken Sie auf dem Dashboard auf **„Termin ansehen"**, um die Bestätigungsseite aufzurufen. Dort finden Sie:

- Datum, Uhrzeit, Kalenderwoche und Ort Ihres Termins
- Den aktuellen Termin-Status (bestätigt)
- Ihre eingetragene Software (mit Möglichkeit zur Änderung)
- Schaltflächen: Dashboard, Termin verschieben, Zum Kalender hinzufügen

> **Termin in den Kalender übernehmen:** Klicken Sie auf **„Zum Kalender hinzufügen"**, um eine `.ics`-Datei herunterzuladen. Diese Datei können Sie in Outlook, Google Calendar oder andere Kalender-Programme importieren.

---

### Termin verschieben

Wenn Sie Ihren gebuchten Termin auf einen anderen Zeitpunkt legen möchten:

**Schritt 1** — Klicken Sie auf dem Dashboard oder der Termin-Seite auf **„Termin verschieben"**.

**Schritt 2** — Ein gelber Hinweisbalken zeigt Ihren bisherigen Termin an.

**Schritt 3** — Wählen Sie wie beim erstmaligen Buchen ein grünes (freies) Zeitfenster aus.

**Schritt 4** — Bestätigen Sie auf der nächsten Seite. Ihr alter Termin wird automatisch storniert, der neue Termin angelegt.

> **Wichtig:** Das Verschieben storniert den alten Termin sofort. Ihre eingetragene Software und sonstigen Angaben werden automatisch auf den neuen Termin übertragen.

---

### Dokumente & Anleitungen herunterladen

Falls das IT-Center Unterlagen bereitgestellt hat (z. B. Checklisten, Handbücher), erscheint auf Ihrem Dashboard eine Karte **„Dokumente & Anleitungen"**. Klicken Sie auf **„Herunterladen"** neben der jeweiligen Datei, um diese zu speichern.

Ist keine Datei hinterlegt, wird diese Karte nicht angezeigt.

---

## Teil 2 — Anleitung für Administratoren

### Admin-Anmeldung

Das Admin-Panel ist unter `/admin` erreichbar. Es gibt zwei Rollen:

| Rolle | Berechtigungen |
|---|---|
| **Admin** | Vollzugriff: lesen, schreiben, löschen |
| **Viewer** | Nur lesen — keine Bearbeitungen möglich |

Melden Sie sich mit Ihrer E-Mail-Adresse und Ihrem Passwort an. Das Admin-Panel verwendet eigene Zugangsdaten (unabhängig von der Mitarbeitenden-Anmeldung).

---

### Kalender (Startseite des Admin-Panels)

Der Kalender ist die Startseite des Admin-Panels. Er zeigt alle Zeitfenster der aktuellen Woche in einem Wochenraster an.

#### Navigation
- Nutzen Sie die **KW-Pfeile** oder das Dropdown oben, um zwischen Kalenderwochen zu wechseln.
- **Grün** = frei, **Blau/belegt** = gebucht, **Grau** = gesperrt.

#### Buchung anlegen (Admin-only)
1. Klicken Sie auf ein freies (grünes) Zeitfenster.
2. Ein Dialog öffnet sich: Wählen Sie die Mitarbeitende Person aus.
3. Klicken Sie auf **„Buchung anlegen"**.

#### Buchung ansehen
Klicken Sie auf ein belegtes Zeitfenster, um die Buchungsdetails zu öffnen.

#### Slots generieren (Admin-only)
Klicken Sie oben auf **„Slots generieren"**, um neue Zeitfenster zu erzeugen (Werktage, 08:00–15:00 Uhr, 8 Slots pro Tag). Tragen Sie den Zeitraum (Von/Bis) und ggf. Feiertage ein. Der Vorgang ist idempotent — bereits vorhandene Slots werden nicht doppelt angelegt.

#### Verschieben-Modus
Wenn in der Buchungsdetailansicht auf „Termin verschieben" geklickt wird, wechselt der Kalender in den Verschieben-Modus:
- Ein Banner oben nennt den Namen der Person.
- Freie Slots erscheinen in **Gelb-Orange** mit dem Hinweis „hierher →".
- Klicken Sie auf das gewünschte Zeitfenster und bestätigen Sie die Verschiebung.
- Zum Abbrechen: Klicken Sie auf „Abbrechen" im Banner.

> ⚠️ **„Alle Buchungen & Mitarbeitenden löschen" (Admin-only)**
> Diese Funktion setzt alle Buchungen und Mitarbeitenden zurück (Zeitfenster und Software-Katalog bleiben erhalten). Nur für den Start eines neuen Austauschrunds gedacht — **nicht rückgängig zu machen.**

---

### Buchungen verwalten

Unter **„Buchungen"** im linken Menü sehen Sie alle Buchungen in einer durchsuchbaren, filterbaren Tabelle.

#### Filtern und exportieren
- Nutzen Sie Suchfeld und Filter-Schaltflächen (Status, Datum etc.), um die Liste einzugrenzen.
- **„Export"** oben rechts: lädt die gefilterte Ansicht als Excel-Datei (`.xlsx`) herunter.
- **„Imaging-Blatt (Tag)"**: Druckt alle Imaging-Blätter eines Tages als PDF.

#### Buchung im Detail
Klicken Sie auf eine Buchung, um die Detailansicht zu öffnen. Folgende Aktionen stehen zur Verfügung (Admin-only):

| Aktion | Beschreibung |
|---|---|
| Als nicht erschienen markieren | Setzt Status auf „Nicht erschienen"; Grund kann angegeben werden |
| Als krank markieren | Setzt Status auf „Krank gemeldet"; Grund kann angegeben werden |
| Zurück auf bestätigt | Stellt einen Sonderstatus zurück auf „bestätigt" |
| Termin verschieben | Öffnet den Kalender im Verschieben-Modus |
| Stornieren | Storniert die Buchung (nicht rückgängig zu machen) |
| Imaging-Blatt drucken | Öffnet ein PDF-Dokument für den Techniker |

#### Buchungen bulk-löschen
Wählen Sie in der Buchungsliste mehrere Buchungen per Checkbox aus und klicken Sie auf **„Löschen"** in der Aktionsleiste. Die zugehörigen Zeitfenster werden automatisch wieder freigegeben.

---

### Warteschlange

Der Menüpunkt **„Warteschlange"** (mit orangefarbenem Zähler) zeigt alle bestätigten Buchungen, die noch nicht vom IT-Center abgearbeitet wurden.

#### Tabs
- **Offen** — Buchungen, die noch bearbeitet werden müssen (`reviewed_at` ist leer).
- **Bereit** — Buchungen, die als abgearbeitet markiert wurden.

#### Aktionen (Admin-only)
- **Bestätigen** — Markiert die Buchung als abgearbeitet (verschiebt sie in „Bereit").
- **Zurück in Warteschlange** — Stellt eine als bereit markierte Buchung zurück in „Offen".
- **Ansehen** — Öffnet die Buchungsdetail-Seite.

---

### Mitarbeitende verwalten

Unter **„Mitarbeitende"** sehen Sie alle importierten Mitarbeitenden. Von hier aus können Sie Einzelpersonen ansehen und (admin-only) löschen.

#### Mitarbeitende importieren (CSV)

**Schritt 1 — CSV-Datei vorbereiten**
Die Datei muss folgende Spaltenköpfe enthalten (Reihenfolge egal):

| CSV-Spalte | Feld |
|---|---|
| `PC-Nummer` | PC-Nummer |
| `Login` | KVGG-Nummer |
| `Vorname` | Vorname |
| `Nachname` | Nachname |
| `eMail-Adresse` | E-Mail (beachte: kleines `e`) |
| `Fachabteilung` | Abteilung |

Trennzeichen: Semikolon (`;`), Komma (`,`) oder Tabulator werden automatisch erkannt. Zeichensatz Windows-1252 (German Excel) und UTF-8 werden unterstützt.

**Schritt 2 — Import starten**
Klicken Sie in der Mitarbeitenden-Liste auf **„Mitarbeitende importieren"** (oben rechts). Wählen Sie Ihre CSV-Datei aus. Das System meldet eventuelle Fehler pro Zeile.

> **Upsert-Logik:** Bereits vorhandene Mitarbeitende (gleiche KVGG-Nummer) werden aktualisiert, nicht doppelt angelegt.

#### Mitarbeitende bulk-löschen
Wählen Sie Mitarbeitende per Checkbox aus und klicken Sie auf **„Löschen"**. Zugehörige Buchungen und deren Zeitfenster werden automatisch bereinigt.

---

### Erinnerungen

Der Menüpunkt **„Erinnerungen"** listet alle Mitarbeitenden **ohne aktiven Termin** auf. Pro Person erscheint eine Schaltfläche **„Erinnern"**, die Ihr E-Mail-Programm mit einer vorgefertigten deutschen Erinnerungsmail öffnet.

> Das System selbst versendet keine E-Mails — die Nachricht geht aus Ihrem eigenen Postfach ab.

---

### Software-Katalog

Der Software-Katalog enthält alle Programme, die Mitarbeitenden bei der Buchung auswählen können.

#### Status-Tabs
- **Alle** — Alle Einträge.
- **Wartet auf Freigabe** — Einträge von Mitarbeitenden, noch nicht geprüft. Die Zahl im Menü-Badge zeigt die Anzahl.
- **Freigegeben** — Für alle Mitarbeitenden sichtbare Einträge.

#### Eintrag freigeben
Klicken Sie in der Zeile des ausstehenden Eintrags auf das Mehr-Menü **(⋮)** und wählen Sie **„Freigeben"**.

#### Einträge zusammenführen (Merge)
Doppelte oder ähnliche Einträge (z. B. „Excel" und „excel") können zusammengeführt werden:

1. Öffnen Sie den Eintrag, den Sie **behalten** möchten (Gewinner).
2. Klicken Sie im ⋮-Menü auf **„Zusammenführen"**.
3. Wählen Sie den zu entfernenden Eintrag (Verlierer).
4. Bestätigen Sie: Alle Buchungen werden auf den Gewinner umgestellt, der Verlierer wird gelöscht.

#### Verwendung einsehen
Klicken Sie auf einen Katalog-Eintrag, um zu sehen, welche Mitarbeitenden diese Software angefordert haben (Tab **„Verwendet von"**).

#### Software aus Buchung entfernen
In der Buchungsdetailansicht können einzelne Software-Einträge über das Stift-Icon entfernt werden.

---

### Einstellungen

Unter **„Einstellungen"** (admin-only) können Sie drei Bereiche konfigurieren. Jeder Bereich hat eine eigene **„Bearbeiten"**-Schaltfläche direkt neben der Vorschau.

#### Ort
Der Veranstaltungsort (Gebäude + Raum, z. B. „Gebäude B, Raum 345") wird auf der Buchungsbestätigung für Mitarbeitende, auf dem Dashboard und im iCal-Termin angezeigt.

#### Texte des Software-Formulars
Die Texte, die Mitarbeitende beim Ausfüllen der Software-Angaben sehen, können hier angepasst werden:

| Textblock | Inhalt |
|---|---|
| Grüner Kasten (Standard-Software) | Überschrift + Beschreibungstext + Programmliste |
| Blauer Kasten (Softwarecenter) | Überschrift + Beschreibungstext + Programmliste |
| Einleitungstext „Ihre Arbeitsumgebung" | Überschrift + Beschreibungstext |

**Format:** Die erste Zeile jedes Textfelds wird als Fettschrift-Überschrift gerendert, alle weiteren als normaler Text. Programmlisten: ein Programm pro Zeile. Leer lassen = Standardwert wird angezeigt.

#### Footer-Text
Der Text, der nach dem Copyright-Jahr im Seitenfußbereich jeder Mitarbeitenden-Seite erscheint (z. B. Kontaktdaten des IT-Centers). Das Jahr wird automatisch gesetzt.

---

### Datensicherung

Unter **„Datensicherung"** (admin-only) können Sie SQL-Dumps der Datenbank erstellen, herunterladen und einspielen.

#### Backup erstellen
Klicken Sie oben auf **„Backup erstellen"**. Das System legt eine Datei `backup_JJJJ-MM-TT_HH-MM-SS.sql` auf dem Server ab und zeigt sie in der Liste an.

#### Backup herunterladen
Klicken Sie in der Tabelle auf **„Herunterladen"** neben dem gewünschten Backup.

#### Backup einspielen (Restore)
Klicken Sie oben auf **„Backup einspielen"**, wählen Sie eine `.sql`-Datei von Ihrem Computer und bestätigen Sie die Warnung.

> ⚠️ Beim Einspielen wird die aktuelle Datenbank vollständig überschrieben. Erstellen Sie vorher ein aktuelles Backup.

> **Automatische Backups:** Bei jedem Einspielen von Datenbank-Updates (Migrationen) wird automatisch ein Backup angelegt. Backups enthalten personenbezogene Daten und müssen sicher aufbewahrt werden.

---

### Dokumente & Anleitungen (Admin)

Unter **„Dokumente"** können Admins Dateien hochladen, die Mitarbeitenden auf ihrem Dashboard zum Download bereitstehen.

#### Datei hochladen
1. Klicken Sie oben auf **„Datei hochladen"**.
2. Wählen Sie die Datei von Ihrem Computer (z. B. eine PDF-Anleitung).
3. Bestätigen. Die Datei erscheint sofort für alle Mitarbeitenden auf ihrem Dashboard.

#### Datei löschen
Klicken Sie in der Tabelle auf **„Löschen"** neben der entsprechenden Datei. Die Datei wird vom Server entfernt und erscheint nicht mehr im Mitarbeitenden-Dashboard.

---

### Admin-Benutzer verwalten

Unter **„Admin-Benutzer"** (admin-only) verwalten Sie die Konten für das Admin-Panel.

#### Benutzer anlegen
1. Klicken Sie auf **„Benutzer anlegen"**.
2. Tragen Sie Name, E-Mail-Adresse, Passwort und Rolle (Admin / Viewer) ein.
3. Speichern Sie.

#### Benutzer bearbeiten
Klicken Sie auf einen Benutzer und dann auf **„Bearbeiten"**. Passwort-Feld leer lassen, wenn das Passwort nicht geändert werden soll.

#### Rollen
| Rolle | Beschreibung |
|---|---|
| **Admin** | Vollzugriff auf alle Funktionen |
| **Viewer** | Nur lesen — keine Änderungen möglich |

---

## Anhang

### Buchungs-Status Übersicht

| Status | Bedeutung |
|---|---|
| **Bestätigt** | Aktiver Termin — erscheint im Mitarbeitenden-Dashboard |
| **Storniert** | Termin wurde abgesagt; Zeitfenster ist wieder frei |
| **Abgeschlossen** | Austausch hat stattgefunden |
| **Nicht erschienen** | Mitarbeitende/r ist nicht zum Termin erschienen |
| **Krank gemeldet** | Mitarbeitende/r hat sich krank gemeldet |

---

### Häufige Fragen (FAQ)

**Ich habe meine PC-Nummer vergessen. Was tun?**
Die PC-Nummer steht auf einem Aufkleber an Ihrem Laptop (oft auf der Unterseite oder neben dem Display). Wenden Sie sich bei Fragen an das IT-Center.

**Kann ich meinen Termin absagen?**
Eine selbstständige Absage ist nicht vorgesehen. Bitte wenden Sie sich an das IT-Center, damit ein Administrator den Termin stornieren kann.

**Ich erhalte keine Bestätigungsmail.**
Das System versendet bewusst keine E-Mails. Laden Sie die `.ics`-Datei herunter, um den Termin in Ihren Kalender zu importieren, oder notieren Sie Datum und Uhrzeit.

**Das System zeigt „Zugang gesperrt".**
Nach 5 fehlgeschlagenen Anmeldeversuchen wird der Zugang für 10 Minuten gesperrt. Bitte warten Sie und versuchen Sie es dann erneut.

**Ein Zeitfenster war grün, ist jetzt aber rot.**
Der Kalender aktualisiert sich alle 15 Sekunden. Das Zeitfenster wurde in der Zwischenzeit von einer anderen Person gebucht. Bitte wählen Sie ein anderes freies Fenster.

**Meine Software ist nicht in der Liste.**
Tippen Sie den Namen des Programms in das Eingabefeld und drücken Sie `Enter`. Das IT-Center wird den Eintrag prüfen und ggf. freigeben.

**Als Admin sehe ich bestimmte Aktionen nicht.**
Manche Funktionen sind nur für die Rolle „Admin" verfügbar, nicht für „Viewer". Sprechen Sie ggf. mit einem Administrator, der Ihnen weiterhelfen kann.

---

*Erstellt mit dem Laptop-Austausch-Buchungssystem · Kreis Groß-Gerau*
