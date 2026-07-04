# CANTAO Solax Bundle
**Deutsch & English below** – beide Abschnitte sind vollständig und enthalten dieselben Schritt-für-Schritt-Anleitungen.

Das Bundle integriert Solax-Wechselrichter nahtlos in CANTAO. Es ruft zyklisch Messwerte über die Solax-Cloud-API ab, normalisiert
sie in ein einheitliches Schema und stellt sie innerhalb von Contao als Datensätze sowie als Metrikquelle für CANTAO-Dashboards
bereit. Ein separates Python- oder CLI-Tool wird nicht mehr benötigt – sämtliche Abläufe finden innerhalb des Contao-Ökosystems
statt. Darüber hinaus bringt das Bundle ein Frontend-Modul mit, sodass die Daten direkt in Seitenlayouts eingesetzt werden können.

## Funktionsumfang

- Unterstützung der Solax Cloud API (v1 und v2)
- Normalisierung der gelieferten Rohdaten in strukturierte Metriken
- Speicherung der Werte in der Contao-Tabelle `tl_solax_metric`
- Konfigurierbares Präfix und Mapping für individuelle CANTAO-Bezeichnungen
- Konfigurierbare Wiederholversuche und Timeout für eine robustere API-Kommunikation
- Optionales Ausblenden unerwünschter Rohfelder sowie Rundung von Dezimalwerten
- Registrierter Cron-Job `SolaxSyncCron`, der die Daten automatisiert synchronisiert
- Backend-Integration über DCA, damit die Werte im Contao-Backend eingesehen werden können
- Fake-Daten-Modus basierend auf aktuellem Sonnenaufgang und -untergang, Wolkendichte und Grundlast
- Frontend-Modul **Solax Messwerte** zur Einbindung in Seiten und Layouts (nutzt Echt- oder Fakedaten)

## 🚀 Schnellstart (DE)

1. **Repository klonen** (wenn Sie das Bundle oder die Skripte lokal ausführen möchten)
   ```bash
   git clone https://github.com/404GamerNotFound/cantao_solax_add_on.git
   cd cantao_solax_add_on
   ```

2. **Voraussetzungen**
   - Bestehende Contao 5-Installation mit gültiger `DATABASE_URL` (z. B. in `.env.local`).
   - PHP ≥ 8.1, Composer, und Shell-Zugriff auf das Projekt.

3. **Ein-Klick-Installation** (empfohlen)
   - Im Contao-Projekt ausführen: `./scripts/oneclick-install.sh`
   - Oder von außen mit Ziel: `./scripts/oneclick-install.sh /pfad/zu/contao`
   - Das Skript erledigt alles: Repository registrieren, Bundle installieren, Migrationen ausführen, Basis-Config setzen und
     fehlende Solax-Variablen (`SOLAX_API_KEY`, `SOLAX_SERIAL`, `SOLAX_SITE_ID`) als Platzhalter in `.env.local` anlegen.

4. **Manuelle Installation** (falls bevorzugt)
   ```bash
   composer config repositories.cantao-solax vcs https://github.com/404GamerNotFound/cantao_solax_add_on.git
   composer require cantao/solax-bundle:dev-main
   vendor/bin/contao-console contao:migrate --no-interaction
   ```

5. **Echtdaten vs. Fakedaten wählen**
   - Backend → **System → Einstellungen → Solax Fake-Daten**: Fake-Modus aktivieren/deaktivieren und Standort/Parameter setzen.
   - Backend → **System → Einstellungen → Solax Zugangsdaten**: API-Key, Seriennummer und Plant-ID/UID hinterlegen.
   - Ohne Zugangsdaten oder bei aktivem Fake-Modus nutzt das Bundle automatisch simulierte Werte.

6. **Frontend einbinden**
   - **Themes → Frontend-Module**: neues Modul vom Typ *Solax Messwerte* anlegen.
   - Modul im Seitenlayout oder als Inhaltselement einfügen. Quelle (API/Fake/Cached) und Zeitstempel werden automatisch angezeigt.

7. **Cron prüfen**
   - Der Job `SolaxSyncCron` läuft im hinterlegten Intervall (Standard: stündlich) und holt neue Daten ab.
   - Im System-Log sehen Sie, ob Werte geschrieben/übersprungen wurden oder ob Zugangsdaten fehlen.

## 🚀 Quickstart (EN)

1. **Clone the repository** (if you want to run the bundle or scripts locally)
   ```bash
   git clone https://github.com/404GamerNotFound/cantao_solax_add_on.git
   cd cantao_solax_add_on
   ```

2. **Prerequisites**
   - Existing Contao 5 installation with a valid `DATABASE_URL` (e.g., in `.env.local`).
   - PHP ≥ 8.1, Composer, and shell access to the project.

3. **One-click install** (recommended)
   - Inside the Contao project: `./scripts/oneclick-install.sh`
   - Or from outside with a target path: `./scripts/oneclick-install.sh /path/to/contao`
   - The script handles everything: registers the VCS repo, installs the bundle, runs migrations, writes baseline config, and adds
     missing Solax placeholders (`SOLAX_API_KEY`, `SOLAX_SERIAL`, `SOLAX_SITE_ID`) to `.env.local` if absent.

4. **Manual install** (if you prefer)
   ```bash
   composer config repositories.cantao-solax vcs https://github.com/404GamerNotFound/cantao_solax_add_on.git
   composer require cantao/solax-bundle:dev-main
   vendor/bin/contao-console contao:migrate --no-interaction
   ```

5. **Choose real vs. fake data**
   - Backend → **System → Settings → Solax Fake Data**: toggle fake mode and set location/parameters.
   - Backend → **System → Settings → Solax Credentials**: store API key, serial number, and plant/UID.
   - With fake mode enabled or missing credentials, the bundle automatically serves simulated metrics.

6. **Render on a page**
   - **Themes → Frontend modules**: create a new module of type *Solax metrics*.
   - Place it in your page layout or as a content element. It shows the source (API/Fake/Cached) and timestamp automatically.

7. **Cron health check**
   - The `SolaxSyncCron` job runs at the configured interval (hourly by default) to pull fresh data.
   - Use the system log to see how many records were written/skipped or whether credentials are missing.

## Konfiguration

Das Bundle liest seine Einstellungen aus der Contao-Konfiguration (z. B. `config/config.yml`). Ein Minimalbeispiel lautet:

```yaml
cantao_solax:
  solax:
    base_url: 'https://www.solaxcloud.com:9443'
    api_version: 'v1'
    api_key: '%env(SOLAX_API_KEY)%'
    serial_number: '%env(SOLAX_SERIAL)%'
    site_id: '%env(string:SOLAX_SITE_ID)%'
    timeout: 10
    retry_count: 2
    retry_delay: 1000 # in Millisekunden
  cantao:
    metric_prefix: 'solax'
    metric_mapping:
      yieldtoday: 'energy.today'
      yieldtotal: 'energy.total'
    ignore_fields:
      - inverterSN
    decimal_precision: 2
  storage:
    table: 'tl_solax_metric'
  cron:
    interval: 'hourly'
```

Sensitive Werte sollten – wie im Beispiel – über Umgebungsvariablen eingebunden werden. Die Option `metric_mapping` erlaubt es,
die automatisch generierten Schlüssel auf projektspezifische Namen abzubilden. Mit `ignore_fields` lassen sich störende Rohwerte
komplett aus der Verarbeitung entfernen, und `decimal_precision` legt fest, wie viele Nachkommastellen bei Fließkommawerten
erhalten bleiben.

### Fake-Data-Modus und Backend-Einstellungen

Über **System → Einstellungen** stehen zusätzliche Konfigurationsmöglichkeiten bereit:

- Im Abschnitt **Solax Fake-Daten** lässt sich ein Fake-Data-Modus aktivieren. Er simuliert PV-Werte anhand der aktuellen Uhrzeit,
  Sonnenauf- und -untergang sowie einer konfigurierbaren Wolkenwahrscheinlichkeit. Standort (Breiten- und Längengrad), Peakleistung
  und Grundlast können angepasst werden.
- Im Abschnitt **Solax Zugangsdaten** werden die echten API-Zugangsdaten hinterlegt. Die Felder für API-Schlüssel, Seriennummer und
  UID/Plant-ID werden verschlüsselt gespeichert und lassen sich ohne Konfigurationsdateien direkt im Backend pflegen. Sobald gültige
  Anmeldedaten vorhanden sind und der Fake-Data-Modus deaktiviert ist, ruft das Bundle automatisch Live-Daten aus der Solax-Cloud ab.

Ohne aktivierten Fake-Data-Modus und ohne hinterlegte Zugangsdaten überspringt der Cronjob die Synchronisation und protokolliert
einen Hinweis im System-Log.

Die Parameter `retry_count` und `retry_delay` definieren, wie oft und wie lange verzögert fehlgeschlagene API-Anfragen erneut
versucht werden. So lassen sich temporäre Ausfälle oder Netzwerkprobleme abfedern, ohne dass der Cron-Job dauerhaft fehlschlägt.

### Frontend-Einbindung

Über das neue Frontend-Modul **Solax Messwerte** lassen sich die Daten direkt auf einer Seite anzeigen.

English: Use the **Solax metrics** frontend module to drop the live/fake metrics onto any page.

## Betrieb

Nach erfolgreicher Installation registriert das Bundle den Cron-Job `SolaxSyncCron`. Dieser ruft im konfigurierten Intervall die
Solax-Cloud ab, normalisiert die Daten und schreibt sie in die Tabelle `tl_solax_metric`. Der Job prüft vor dem Schreiben, ob sich
die Werte seit dem letzten Lauf verändert haben, protokolliert die Anzahl der gespeicherten bzw. übersprungenen Datensätze und
vermeidet so unnötige Schreiboperationen. Über den Contao-Backendbereich lassen sich die Datensätze weiterhin prüfen und bei Bedarf
weiterverarbeiten.

Für die Visualisierung innerhalb von CANTAO wählen Sie in Ihren Dashboards die Integration „Solax“ aus und fügen die gewünschten
Metriken hinzu. Die standardmäßig gelieferten Kennzahlen umfassen unter anderem AC-Leistung, Tages- und Gesamtertrag, Einspeisung,
Verbrauch, Ladezustand und PV-String-Leistungen.

## Anpassungen

- **Cron-Intervall:** Das Intervall kann in der Konfiguration (`cantao_solax.cron.interval`) angepasst werden.
- **Eigene Mappings:** Über `cantao_solax.cantao.metric_mapping` lassen sich Rohschlüssel auf bestehende CANTAO-Entitäten abbilden.
- **Feldfilter:** Mit `cantao_solax.cantao.ignore_fields` blenden Sie unerwünschte Rohfelder vollständig aus.
- **Rundung:** `cantao_solax.cantao.decimal_precision` steuert die Anzahl der Nachkommastellen für Fließkommazahlen.
- **Retry-Strategie:** Über `cantao_solax.solax.retry_count` und `cantao_solax.solax.retry_delay` definieren Sie einen robusten
  Wiederholmechanismus bei kurzzeitigen Ausfällen.
- **Logging:** Das Bundle nutzt den Symfony-Logger. Stellen Sie sicher, dass dieser im Projekt korrekt konfiguriert ist, um Fehler
  beim Abruf oder der Speicherung nachvollziehen zu können.

## Monitoring & Fehlerbehebung

- Der Cron-Job protokolliert, wie viele Werte geschrieben bzw. unverändert übersprungen wurden. So erkennen Sie auf einen Blick,
  ob neue Daten eingetroffen sind.
- Werden alle Werte übersprungen, liegt das entweder an unveränderten Messwerten oder an zu restriktiven Filtern in
  `ignore_fields`.
- Bei häufigen Netzwerkproblemen erhöhen Sie testweise `retry_count` oder verlängern `retry_delay`, bevor Sie drastisch an der
  Timeout-Konfiguration drehen.

## Entwicklung

- Composer-Abhängigkeiten installieren: `composer install`
- Statische Analyse (optional): `composer run-script lint`

## Lizenz

MIT

## Support

If you find this project helpful, you can support it via PayPal: [paypal.me/TonyBrueser](https://www.paypal.com/paypalme/TonyBrueser)

