#!/usr/bin/env bash
set -euo pipefail

SCRIPT_NAME="$(basename "$0")"
PROJECT_DIR="$(pwd)"
COMPOSER_BIN="composer"
CONSOLE_BIN="vendor/bin/contao-console"
BUNDLE_PACKAGE="cantao/solax-bundle"
BUNDLE_VERSION="@dev"
CONFIG_RELATIVE_PATH="config/config.yml"
SKIP_COMPOSER=0
DRY_RUN=0

usage() {
  cat <<USAGE
${SCRIPT_NAME} [--project-dir <pfad>] [--composer <bin>] [--console <bin>] [--skip-composer] [--dry-run]

Automatisiert die Installation und Grundkonfiguration des ${BUNDLE_PACKAGE} Bundles in einer bestehenden Contao-Instanz.

Optionen:
  --project-dir <pfad>   Pfad zu Ihrem Contao-Projekt (Standard: aktuelles Verzeichnis)
  --composer <bin>       Alternativer Composer-Binary (Standard: composer)
  --console <bin>        Pfad zur Contao-Konsole (Standard: vendor/bin/contao-console)
  --skip-composer        Überspringt den Composer-Require-Schritt
  --dry-run              Zeigt nur an, welche Schritte ausgeführt würden
  -h, --help             Zeigt diese Hilfe an
USAGE
}

log() {
  printf '\033[1;32m[INFO]\033[0m %s\n' "$1"
}

warn() {
  printf '\033[1;33m[WARN]\033[0m %s\n' "$1"
}

error() {
  printf '\033[1;31m[ERROR]\033[0m %s\n' "$1" >&2
  exit 1
}

run_cmd() {
  local cmd="$1"
  if [[ ${DRY_RUN} -eq 1 ]]; then
    printf '\033[1;34m[DRY]\033[0m %s\n' "$cmd"
  else
    eval "$cmd"
  fi
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --project-dir)
      PROJECT_DIR="$2"
      shift 2
      ;;
    --composer)
      COMPOSER_BIN="$2"
      shift 2
      ;;
    --console)
      CONSOLE_BIN="$2"
      shift 2
      ;;
    --skip-composer)
      SKIP_COMPOSER=1
      shift
      ;;
    --dry-run)
      DRY_RUN=1
      shift
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      error "Unbekannte Option: $1"
      ;;
  esac
done

if [[ ! -d "${PROJECT_DIR}" ]]; then
  error "Projektverzeichnis ${PROJECT_DIR} wurde nicht gefunden."
fi

cd "${PROJECT_DIR}"

if [[ ! -f composer.json ]]; then
  error "Im Projektverzeichnis wurde keine composer.json gefunden. Bitte in ein Contao-Projekt wechseln."
fi

if ! command -v "${COMPOSER_BIN}" >/dev/null 2>&1; then
  error "Composer wurde unter '${COMPOSER_BIN}' nicht gefunden."
fi

if [[ ${DRY_RUN} -eq 0 && ! -x "${CONSOLE_BIN}" ]]; then
  warn "Die Contao-Konsole (${CONSOLE_BIN}) ist nicht ausführbar. Es wird versucht, sie dennoch zu verwenden."
fi

if [[ ${SKIP_COMPOSER} -eq 0 ]]; then
  log "Prüfe, ob ${BUNDLE_PACKAGE} bereits installiert ist"
  if ${COMPOSER_BIN} show "${BUNDLE_PACKAGE}" >/dev/null 2>&1; then
    log "Bundle ${BUNDLE_PACKAGE} ist bereits vorhanden. Überspringe composer require."
  else
    log "Installiere ${BUNDLE_PACKAGE}${BUNDLE_VERSION:+ (${BUNDLE_VERSION})} via Composer"
    run_cmd "${COMPOSER_BIN} require ${BUNDLE_PACKAGE}${BUNDLE_VERSION:+:${BUNDLE_VERSION}}"
  fi
else
  warn "Composer-Installationsschritt wurde übersprungen."
fi

if [[ ${DRY_RUN} -eq 0 ]]; then
  if [[ ! -x "${CONSOLE_BIN}" ]]; then
    warn "Die Contao-Konsole konnte nicht gefunden oder ausgeführt werden. Überspringe Datenbankmigration."
  else
    log "Führe Contao-Migrationen aus"
    run_cmd "${CONSOLE_BIN} contao:migrate --no-interaction"
  fi
else
  log "(Dry-Run) Überspringe tatsächliche Ausführung der Contao-Konsole"
fi

CONFIG_FILE="${PROJECT_DIR}/${CONFIG_RELATIVE_PATH}"
CONFIG_DIR="$(dirname "${CONFIG_FILE}")"
CONFIG_SNIPPET=$(cat <<'YAML'
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
    metric_mapping: {}
  storage:
    table: 'tl_solax_metric'
  cron:
    interval: 'hourly'
YAML
)

log "Aktualisiere Konfigurationsdatei ${CONFIG_RELATIVE_PATH}"

if [[ ! -d "${CONFIG_DIR}" ]]; then
  if [[ ${DRY_RUN} -eq 1 ]]; then
    log "(Dry-Run) Würde Verzeichnis ${CONFIG_DIR} anlegen"
  else
    mkdir -p "${CONFIG_DIR}"
  fi
fi

if [[ -f "${CONFIG_FILE}" ]] && grep -q "cantao_solax" "${CONFIG_FILE}"; then
  warn "In ${CONFIG_RELATIVE_PATH} existiert bereits ein cantao_solax-Block. Es wurden keine Änderungen vorgenommen."
else
  if [[ ${DRY_RUN} -eq 1 ]]; then
    log "(Dry-Run) Würde folgenden YAML-Block an ${CONFIG_RELATIVE_PATH} anhängen:\n${CONFIG_SNIPPET}"
  else
    {
      printf '\n';
      echo "# CANTAO Solax Konfiguration (automatisch hinzugefügt)";
      echo "${CONFIG_SNIPPET}";
    } >> "${CONFIG_FILE}"
    log "Konfiguration wurde ergänzt. Bitte prüfen Sie ${CONFIG_RELATIVE_PATH} und passen Sie die Werte bei Bedarf an."
  fi
fi

cat <<HINT

Die folgenden Umgebungsvariablen sollten in Ihrer .env oder Server-Konfiguration gesetzt werden:
  SOLAX_API_KEY    = <Ihr Solax API Key>
  SOLAX_SERIAL     = <Seriennummer des Wechselrichters>
  SOLAX_SITE_ID    = <Optionale Anlagen-ID>

Installation abgeschlossen.
HINT

if [[ ${DRY_RUN} -eq 1 ]]; then
  log "Dry-Run abgeschlossen. Es wurden keine Änderungen vorgenommen."
fi
