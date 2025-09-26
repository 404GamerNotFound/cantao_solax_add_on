"""Command line interface for the CANTAO Solax add-on."""

from __future__ import annotations

import argparse
import json
import logging
import sys

from .api import SolaxAPI, SolaxAPIError
from .client import CantaoPushError, CantaoSolaxClient
from .config import AppConfig, load_config


def _configure_logging(level: str) -> None:
    logging.basicConfig(
        level=getattr(logging, level.upper(), logging.INFO),
        format="%(asctime)s - %(name)s - %(levelname)s - %(message)s",
    )


def _build_client(config: AppConfig) -> CantaoSolaxClient:
    api = SolaxAPI(config.solax)
    return CantaoSolaxClient(api, config.cantao)


def _cmd_fetch(client: CantaoSolaxClient, pretty: bool) -> int:
    result = client.fetch_metrics()
    metrics = result["metrics"]
    output = {
        "metrics": metrics,
        "raw": result["raw"],
    }
    if pretty:
        print(json.dumps(output, indent=2, sort_keys=True, ensure_ascii=False))
    else:
        print(json.dumps(output, separators=(",", ":"), ensure_ascii=False))
    return 0


def _cmd_push(client: CantaoSolaxClient, dry_run: bool, pretty: bool) -> int:
    result = client.fetch_metrics()
    metrics = result["metrics"]

    if dry_run:
        return _cmd_fetch(client, pretty)

    client.push_metrics(metrics)
    print(json.dumps({"status": "ok", "pushed_metrics": len(metrics)}))
    return 0


def build_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(description="CANTAO Solax integration helper")
    parser.add_argument("--config", required=True, help="Path to the configuration TOML file")
    parser.add_argument("--log-level", default="INFO", help="Python logging level (default: INFO)")

    subparsers = parser.add_subparsers(dest="command", required=True)

    fetch_parser = subparsers.add_parser("fetch", help="Fetch realtime data and print it")
    fetch_parser.add_argument("--pretty", action="store_true", help="Pretty print JSON output")

    push_parser = subparsers.add_parser("push", help="Fetch realtime data and push it to CANTAO")
    push_parser.add_argument("--pretty", action="store_true", help="Pretty print JSON output for dry-run")
    push_parser.add_argument("--dry-run", action="store_true", help="Fetch data but do not push it")

    return parser


def main(argv: list[str] | None = None) -> int:
    parser = build_parser()
    args = parser.parse_args(argv)

    _configure_logging(args.log_level)

    try:
        config = load_config(args.config)
        client = _build_client(config)

        if args.command == "fetch":
            return _cmd_fetch(client, args.pretty)
        if args.command == "push":
            return _cmd_push(client, args.dry_run, args.pretty)
        parser.error("Unknown command")
    except FileNotFoundError as exc:
        logging.error("Configuration file not found: %%s", exc)
        return 1
    except ValueError as exc:
        logging.error("Configuration error: %%s", exc)
        return 1
    except SolaxAPIError as exc:
        logging.error("Solax API error: %%s", exc)
        return 2
    except CantaoPushError as exc:
        logging.error("CANTAO push failed: %%s", exc)
        return 3
    except Exception as exc:  # pragma: no cover - safety net
        logging.exception("Unexpected error: %%s", exc)
        return 99

    return 0


if __name__ == "__main__":
    sys.exit(main())
