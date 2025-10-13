# CANTAO Solax Add-on

Dieses Projekt liefert ein Python-Add-on, das Messwerte von Solax-Wechselrichtern über die Solax-Cloud-API abruft, normalisiert und für CANTAO bereitstellt. Der Fokus liegt auf einer leichtgewichtigen Integration, die ohne tiefgreifende Änderungen am CANTAO-Kern auskommt und sich direkt in bestehende Dashboards integrieren lässt.

## Funktionen

- Unterstützung der Solax Cloud API (v1 und v2)
- Automatische Normalisierung der Messwerte in ein einheitliches Datenmodell
- Konfigurierbare Transformationen zur Abbildung auf CANTAO-Metriken
- CLI-Tool zum Abrufen oder direkten Weiterleiten der Daten an eine CANTAO-Instanz
- Robuste Fehlerbehandlung samt Logging
- Dokumentierte Schritt-für-Schritt-Anleitung für die Einbindung in der CANTAO-Oberfläche

## Mögliche Entitäten

Das Add-on normalisiert Solax-Felder in metrische Schlüssel, die in CANTAO als Entitäten erscheinen. Die konkrete Auswahl hängt vom jeweiligen Wechselrichter und Firmware-Stand ab. Typische Felder sind:

| Solax-Feld          | Standard-Metrikschlüssel      | Beschreibung                                  |
|---------------------|-------------------------------|-----------------------------------------------|
| `acpower`           | `solax.acpower`               | Aktuelle AC-Ausgangsleistung in Watt          |
| `yieldtoday`        | `solax.yieldtoday`            | Tagesertrag in kWh                            |
| `yieldtotal`        | `solax.yieldtotal`            | Gesamtertrag in kWh                           |
| `feedinpower`       | `solax.feedinpower`           | Einspeiseleistung Richtung Netz in Watt       |
| `feedinenergy`      | `solax.feedinenergy`          | Gesamteinspeisung in kWh                      |
| `consumeenergy`     | `solax.consumeenergy`         | Gesamtverbrauch in kWh                        |
| `consumepower`      | `solax.consumepower`          | Aktuelle Leistungsaufnahme in Watt            |
| `soc`               | `solax.soc`                   | Ladezustand des Speichers in Prozent          |
| `batterypower`      | `solax.batterypower`          | Batterie-Leistungsfluss (Vorzeichen beachten) |
| `pvpower1` / `pvpower2` | `solax.pvpower1` / `solax.pvpower2` | Eingangsleistung der PV-Strings in Watt |
| `temperature`       | `solax.temperature`          | Gerätetemperatur in °C                        |
| `gridvoltage`       | `solax.gridvoltage`           | Netzspannung in Volt                          |

Über die Option `metric_mapping` in der Konfiguration können diese Schlüssel individuell auf bestehende CANTAO-Entitäten abgebildet werden, z. B. `"yieldtoday" = "energy.today"`.

## Installation

### Contao-Integration

Das Repository enthält nun ein vollwertiges Contao-Bundle (`cantao/solax-bundle`), das die komplette Kommunikation mit der Solax-Cloud innerhalb einer Contao-Instanz erledigt.

1. Binden Sie das Bundle über Composer in Ihr Contao-Projekt ein:

   ```bash
   composer require cantao/solax-bundle:@dev
   ```

   > Hinweis: Beim lokalen Entwickeln kann das Bundle auch per `path`-Repository eingebunden werden.

2. Führen Sie den Contao Manager oder `vendor/bin/contao-console contao:migrate` aus, damit die Tabelle `tl_solax_metric` angelegt wird.

3. Hinterlegen Sie die Solax-Zugangsdaten in Ihrer Projektkonfiguration, z. B. in `config/config.yml`:

   ```yaml
   cantao_solax:
     solax:
       base_url: 'https://www.solaxcloud.com:9443'
       api_version: 'v1'
       api_key: '%env(SOLAX_API_KEY)%'
       serial_number: '%env(SOLAX_SERIAL)%'
       site_id: '%env(string:SOLAX_SITE_ID)%'
       timeout: 10
     cantao:
       metric_prefix: 'solax'
       metric_mapping:
         yieldtoday: 'energy.today'
         yieldtotal: 'energy.total'
     storage:
       table: 'tl_solax_metric'
     cron:
       interval: 'hourly' # möglich sind z. B. minutely, hourly, daily
   ```

4. Nach erfolgreicher Konfiguration steht unter **System → Cron** ein Job „SolaxSyncCron“ zur Verfügung. Dieser ruft in dem angegebenen Intervall die Werte ab und schreibt sie in die Tabelle `tl_solax_metric`. Die Datensätze lassen sich über das Backend (DCA `tl_solax_metric`) einsehen und weiterverarbeiten.

### Python-Add-on

```bash
pip install .
```

Alternativ kann das Paket im Entwicklungsmodus installiert werden:

```bash
pip install -e .
```

## Konfiguration

Die Konfiguration erfolgt über eine TOML-Datei. Eine Vorlage befindet sich in `examples/config.example.toml`.

```toml
[solax]
base_url = "https://www.solaxcloud.com:9443"
api_version = "v1"
api_key = "<API_KEY>"
serial_number = "<INVERTER_SERIAL>"
site_id = "<PLANT_ID>"
timeout = 10

[cantao]
base_url = "https://cantao.example.com"
api_token = "<CANTAO_TOKEN>"
metric_prefix = "solax"
metric_mapping = { "yieldtoday" = "energy.today" }
```

## Verwendung

### Daten abrufen

```bash
cantao-solax --config /pfad/zur/config.toml fetch
```

### Daten an CANTAO senden

```bash
cantao-solax --config /pfad/zur/config.toml push
```

Der Push-Befehl sendet die normalisierten Daten als JSON-Load an die konfigurierte CANTAO-Instanz. Auf CANTAO-Seite wird ein generischer `/api/v1/metrics`-Endpunkt angenommen.

## Integration in CANTAO

Eine ausführliche Schritt-für-Schritt-Anleitung zur Einrichtung (inkl. UI-Navigation, Token-Hinterlegung und Dashboard-Konfiguration) finden Sie in [docs/integration-guide.md](docs/integration-guide.md). Die wichtigsten Schritte lauten zusammengefasst:

1. Add-on installieren und Konfiguration anpassen.
2. Mit `cantao-solax fetch` einen Testabruf durchführen.
3. Einen periodischen `push`-Job (z. B. Cron) einrichten.
4. In der CANTAO-Oberfläche unter **Einstellungen → Integrationen** eine neue externe Metrikquelle anlegen, das API-Token hinterlegen und gewünschte Widgets konfigurieren.

## Entwicklung

- Tests ausführen: `pytest`
- Linting (optional): `ruff check src`

## Weiterführende Informationen

- Änderungsverlauf: siehe [CHANGELOG.md](CHANGELOG.md)
- Ausführliche Integrationsbeschreibung: [docs/integration-guide.md](docs/integration-guide.md)

## Lizenz

MIT
