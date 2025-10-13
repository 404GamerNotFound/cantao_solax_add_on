# CANTAO Solax Bundle

Das Bundle integriert Solax-Wechselrichter nahtlos in CANTAO. Es ruft zyklisch Messwerte über die Solax-Cloud-API ab, normalisiert
sie in ein einheitliches Schema und stellt sie innerhalb von Contao als Datensätze sowie als Metrikquelle für CANTAO-Dashboards
bereit. Ein separates Python- oder CLI-Tool wird nicht mehr benötigt – sämtliche Abläufe finden innerhalb des Contao-Ökosystems
statt.

## Funktionsumfang

- Unterstützung der Solax Cloud API (v1 und v2)
- Normalisierung der gelieferten Rohdaten in strukturierte Metriken
- Speicherung der Werte in der Contao-Tabelle `tl_solax_metric`
- Konfigurierbares Präfix und Mapping für individuelle CANTAO-Bezeichnungen
- Konfigurierbare Wiederholversuche und Timeout für eine robustere API-Kommunikation
- Optionales Ausblenden unerwünschter Rohfelder sowie Rundung von Dezimalwerten
- Registrierter Cron-Job `SolaxSyncCron`, der die Daten automatisiert synchronisiert
- Backend-Integration über DCA, damit die Werte im Contao-Backend eingesehen werden können

## Installation

### Über Composer

Fügen Sie das Bundle Ihrem Contao-Projekt als VCS-Abhängigkeit hinzu und installieren Sie es per Composer:

```bash
composer config repositories.cantao-solax vcs https://github.com/404GamerNotFound/cantao_solax_add_on.git
composer require cantao/solax-bundle:dev-main
```

Führen Sie anschließend die Contao-Migrationen aus, damit die benötigte Datenbanktabelle angelegt wird:

```bash
vendor/bin/contao-console contao:migrate --no-interaction
```

### Automatisiertes Installationsskript (optional)

Alternativ kann das mitgelieferte Skript `scripts/install-contao.sh` die obigen Schritte ausführen. Beispiel:

```bash
./scripts/install-contao.sh --project-dir /pfad/zu/contao
```

Das Skript führt – sofern nicht über Parameter deaktiviert – `composer require`, `contao:migrate` und ergänzt eine
Basis-Konfiguration in `config/config.yml`.

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

Die Parameter `retry_count` und `retry_delay` definieren, wie oft und wie lange verzögert fehlgeschlagene API-Anfragen erneut
versucht werden. So lassen sich temporäre Ausfälle oder Netzwerkprobleme abfedern, ohne dass der Cron-Job dauerhaft fehlschlägt.

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
