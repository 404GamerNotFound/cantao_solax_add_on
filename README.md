# CANTAO Solax Add-on

Dieses Projekt liefert ein Python Add-on, das die Daten von Solax Wechselrichtern über die Solax Cloud API abruft und für CANTAO aufbereitet. Der Fokus liegt auf einer leichtgewichtigen Integration, die ohne tiefgreifende Änderungen am CANTAO-Kern auskommt.

## Funktionen

- Unterstützung der Solax Cloud API (v1 und v2)
- Automatische Normalisierung der Messwerte in ein einheitliches Datenmodell
- Konfigurierbare Transformationen zur Abbildung auf CANTAO-Metriken
- CLI-Tool zum Abrufen oder direkten Weiterleiten der Daten an eine CANTAO Instanz
- Robuste Fehlerbehandlung samt Logging

## Installation

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

## Entwicklung

- Tests ausführen: `pytest`
- Linting (optional): `ruff check src`

## Lizenz

MIT
