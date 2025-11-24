#!/usr/bin/env bash
set -euo pipefail

# Simple wrapper to install the bundle into an existing Contao project in one go.
# If you run this script from the project root, it installs there automatically.
# You can also pass a target path as first argument: ./scripts/oneclick-install.sh /path/to/contao

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
INSTALL_SCRIPT="${SCRIPT_DIR}/install-contao.sh"

print_help() {
  cat <<'USAGE'
Nutzung: oneclick-install.sh [<projektpfad>] [--skip-composer] [--dry-run]

Startet den vollständigen Installationsablauf (composer require, contao:migrate,
Basis-Konfiguration) für das Solax Bundle in einem Schritt.

Argumente:
  <projektpfad>     Optionaler Pfad zur bestehenden Contao-Installation.
                    Standard ist das aktuelle Verzeichnis.
  --skip-composer   Überspringt den Composer-Require-Schritt (Bundle bereits vorhanden).
  --dry-run         Zeigt nur an, welche Schritte durchgeführt würden.
  -h, --help        Zeigt diese Hilfe an.

Alle zusätzlichen Parameter nach <projektpfad> werden an das Basisskript
install-contao.sh durchgereicht.
USAGE
}

if [[ $# -gt 0 ]]; then
  case "$1" in
    -h|--help)
      print_help
      exit 0
      ;;
  esac
fi

PROJECT_DIR="$(pwd)"
if [[ $# -gt 0 && ! "$1" =~ ^-- ]]; then
  PROJECT_DIR="$1"
  shift
fi

if [[ ! -x "${INSTALL_SCRIPT}" ]]; then
  echo "[ERROR] Basisskript install-contao.sh wurde nicht gefunden oder ist nicht ausführbar." >&2
  exit 1
fi

echo "[INFO] Starte One-Click-Installation für ${PROJECT_DIR}"
"${INSTALL_SCRIPT}" --project-dir "${PROJECT_DIR}" "$@"
