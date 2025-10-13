"""In-process HTTP dashboard for visualising Solax metrics without external deps."""

from __future__ import annotations

import argparse
import html
import json
import logging
import threading
import time
from dataclasses import dataclass
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from typing import Any, Dict, Iterable, Mapping, Tuple
from urllib.parse import parse_qs, urlparse

from .client import CantaoSolaxClient

logger = logging.getLogger(__name__)


SUMMARY_FIELDS: Tuple[Tuple[str, str, str], ...] = (
    ("solax.acpower", "Aktuelle Leistung", "W"),
    ("solax.yieldtoday", "Tagesertrag", "kWh"),
    ("solax.yieldtotal", "Gesamtertrag", "kWh"),
    ("solax.feedinpower", "Netzeinspeisung", "W"),
    ("solax.consumepower", "Hausverbrauch", "W"),
)


@dataclass
class DashboardCard:
    key: str
    label: str
    display: str
    raw: Any


def _format_value(value: Any) -> str:
    if value is None:
        return "–"
    if isinstance(value, bool):
        return "Ja" if value else "Nein"
    if isinstance(value, int) and not isinstance(value, bool):
        return str(value)
    if isinstance(value, float):
        return f"{value:.2f}"
    return str(value)


def _build_summary_cards(metrics: Mapping[str, Any]) -> Tuple[Iterable[DashboardCard], DashboardCard | None]:
    cards = []
    battery_card: DashboardCard | None = None

    for key, label, unit in SUMMARY_FIELDS:
        if key in metrics:
            raw = metrics[key]
            suffix = f" {unit}" if unit else ""
            cards.append(
                DashboardCard(
                    key=key,
                    label=label,
                    display=f"{_format_value(raw)}{suffix}",
                    raw=raw,
                )
            )

    soc_value = metrics.get("solax.soc")
    if isinstance(soc_value, (int, float)):
        percentage = max(0.0, min(100.0, float(soc_value)))
        battery_card = DashboardCard(
            key="solax.soc",
            label="Akkuladung",
            display=f"{percentage:.0f}%",
            raw=percentage,
        )

    return cards, battery_card


def _render_cards(cards: Iterable[DashboardCard]) -> str:
    parts = []
    for card in cards:
        parts.append(
            """
            <article class="card">
              <small>{key}</small>
              <h2>{label}</h2>
              <div class="value">{value}</div>
            </article>
            """.format(
                key=html.escape(card.key),
                label=html.escape(card.label),
                value=html.escape(card.display),
            )
        )
    if not parts:
        return ""
    return '<section class="cards">' + "".join(parts) + "</section>"


def _render_battery(card: DashboardCard | None) -> str:
    if not card:
        return ""
    width = max(0.0, min(100.0, float(card.raw)))
    return """
    <section class="battery">
      <h2>{label}</h2>
      <p>Aktueller Ladestand</p>
      <div class="bar"><span style="width:{width:.0f}%"></span></div>
      <p><strong>{value}</strong></p>
    </section>
    """.format(
        label=html.escape(card.label),
        value=html.escape(card.display),
        width=width,
    )


def _render_table(metrics_items: Iterable[Tuple[str, str]]) -> str:
    rows = [
        "<tr><td>{key}</td><td>{value}</td></tr>".format(
            key=html.escape(key), value=html.escape(value)
        )
        for key, value in metrics_items
    ]
    if not rows:
        return "<p>Keine Metriken gefunden. Bitte Filter zurücksetzen oder später erneut versuchen.</p>"
    return (
        "<section><table><thead><tr><th>Schlüssel</th><th>Wert</th></tr></thead><tbody>"
        + "".join(rows)
        + "</tbody></table></section>"
    )


class DashboardApp:
    """Responsible for rendering HTML and JSON payloads."""

    def __init__(self, client: CantaoSolaxClient, refresh_seconds: int = 30) -> None:
        self.client = client
        self.refresh_seconds = refresh_seconds

    def _fetch_metrics(self) -> Tuple[Dict[str, Any], Dict[str, Any], str | None]:
        try:
            payload = self.client.fetch_metrics()
            metrics = dict(payload.get("metrics", {}))
            raw = dict(payload.get("raw", {}))
            return metrics, raw, None
        except Exception as exc:  # pragma: no cover - exercised in tests via error path
            logger.exception("Dashboard konnte Daten nicht abrufen: %s", exc)
            return {}, {}, f"Fehler beim Abrufen der Daten: {exc}"

    def render_index(self, query: str = "") -> str:
        metrics, raw, error = self._fetch_metrics()

        if query:
            lowered = query.lower()
            filtered_metrics = {k: v for k, v in metrics.items() if lowered in k.lower()}
            summary_cards, battery_card = _build_summary_cards(filtered_metrics)
        else:
            filtered_metrics = metrics
            summary_cards, battery_card = _build_summary_cards(metrics)
        metrics_items = sorted((k, _format_value(v)) for k, v in filtered_metrics.items())

        raw_json = html.escape(json.dumps(raw, indent=2, sort_keys=True, ensure_ascii=False))
        cards_html = _render_cards(summary_cards)
        battery_html = _render_battery(battery_card)
        table_html = _render_table(metrics_items)

        error_html = (
            f'<div class="error">{html.escape(error)}</div>' if error else ""
        )

        generated_at = time.strftime("%d.%m.%Y %H:%M:%S")

        return f"""
<!DOCTYPE html>
<html lang=\"de\">
<head>
  <meta charset=\"utf-8\" />
  <title>Solax Monitoring Dashboard</title>
  <meta http-equiv=\"refresh\" content=\"{self.refresh_seconds}\" />
  <style>
    :root {{
      color-scheme: light dark;
      font-family: "Inter", "Helvetica Neue", Arial, sans-serif;
      background-color: #0f172a;
      color: #e2e8f0;
    }}
    body {{
      margin: 0;
      padding: 2rem;
      background: radial-gradient(circle at top left, rgba(148, 163, 184, 0.2), transparent 60%),
                  linear-gradient(180deg, rgba(15, 23, 42, 0.95) 0%, rgba(15, 23, 42, 1) 60%);
      min-height: 100vh;
    }}
    header {{
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 2rem;
    }}
    header h1 {{
      margin: 0;
      font-size: 2rem;
      letter-spacing: 0.05em;
    }}
    header form {{
      display: flex;
      gap: 0.5rem;
      flex-wrap: wrap;
      align-items: center;
    }}
    header input[type=\"search\"] {{
      padding: 0.5rem 0.75rem;
      border-radius: 999px;
      border: 1px solid rgba(148, 163, 184, 0.3);
      background: rgba(15, 23, 42, 0.6);
      color: inherit;
    }}
    header button, header a.button {{
      background: linear-gradient(135deg, #22d3ee, #0ea5e9);
      border: none;
      color: #0f172a;
      font-weight: 600;
      padding: 0.6rem 1.2rem;
      border-radius: 999px;
      cursor: pointer;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      transition: transform 0.15s ease, box-shadow 0.15s ease;
    }}
    header button:hover, header a.button:hover {{
      transform: translateY(-1px);
      box-shadow: 0 10px 30px rgba(14, 165, 233, 0.4);
    }}
    .cards {{
      display: grid;
      gap: 1rem;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      margin-bottom: 2rem;
    }}
    .card {{
      background: rgba(30, 41, 59, 0.75);
      border: 1px solid rgba(148, 163, 184, 0.2);
      border-radius: 1rem;
      padding: 1.25rem;
      box-shadow: 0 20px 45px rgba(15, 23, 42, 0.45);
      backdrop-filter: blur(12px);
      display: flex;
      flex-direction: column;
      gap: 0.4rem;
    }}
    .card h2 {{
      margin: 0;
      font-size: 1.1rem;
      color: #f8fafc;
    }}
    .card .value {{
      font-size: 1.8rem;
      font-weight: 700;
      color: #22d3ee;
    }}
    .card small {{
      color: #cbd5f5;
      letter-spacing: 0.05em;
      text-transform: uppercase;
    }}
    .battery {{
      background: rgba(20, 83, 45, 0.65);
      border: 1px solid rgba(134, 239, 172, 0.4);
      border-radius: 1rem;
      padding: 1.5rem;
      margin-bottom: 2rem;
      position: relative;
      overflow: hidden;
    }}
    .battery::after {{
      content: "";
      position: absolute;
      inset: 0;
      background: linear-gradient(90deg, rgba(34, 197, 94, 0.35), transparent);
      pointer-events: none;
    }}
    .battery h2 {{
      margin-top: 0;
      color: #bbf7d0;
    }}
    .battery .bar {{
      height: 18px;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.12);
      overflow: hidden;
      margin-top: 1rem;
    }}
    .battery .bar span {{
      display: block;
      height: 100%;
      background: linear-gradient(135deg, #4ade80, #22c55e);
    }}
    table {{
      width: 100%;
      border-collapse: collapse;
      background: rgba(30, 41, 59, 0.75);
      border-radius: 1rem;
      overflow: hidden;
      box-shadow: 0 20px 45px rgba(15, 23, 42, 0.45);
    }}
    table thead {{
      background: rgba(51, 65, 85, 0.7);
    }}
    th, td {{
      padding: 0.85rem 1rem;
      text-align: left;
    }}
    tr:nth-child(even) {{
      background: rgba(15, 23, 42, 0.35);
    }}
    .error {{
      background: rgba(248, 113, 113, 0.85);
      border-radius: 1rem;
      padding: 1rem 1.5rem;
      margin-bottom: 2rem;
      color: #450a0a;
      font-weight: 600;
    }}
    details {{
      margin-top: 2rem;
      background: rgba(30, 41, 59, 0.75);
      border-radius: 1rem;
      padding: 1.25rem;
      border: 1px solid rgba(148, 163, 184, 0.2);
    }}
    pre {{
      white-space: pre-wrap;
      word-break: break-word;
    }}
    footer {{
      margin-top: 2rem;
      color: #94a3b8;
      font-size: 0.85rem;
      text-align: center;
    }}
  </style>
</head>
<body>
  <header>
    <div>
      <h1>Solax Monitoring</h1>
      <p>{len(metrics)} Metriken geladen · Aktualisierung alle {self.refresh_seconds}s</p>
    </div>
    <form method=\"get\" action=\"/\">
      <input type=\"search\" name=\"q\" value=\"{html.escape(query)}\" placeholder=\"Nach Schlüssel filtern…\" />
      <button type=\"submit\">Filtern</button>
      <a class=\"button\" href=\"/metrics.json\" target=\"_blank\">JSON</a>
    </form>
  </header>
  {error_html}
  {cards_html}
  {battery_html}
  {table_html}
  <details>
    <summary>Rohdaten anzeigen</summary>
    <pre>{raw_json}</pre>
  </details>
  <footer>
    Bereitgestellt vom CANTAO Solax Add-on. Oberfläche aktualisiert am {generated_at}.
  </footer>
</body>
</html>
"""

    def render_metrics_json(self) -> Tuple[int, str]:
        try:
            payload = self.client.fetch_metrics()
            return 200, json.dumps(payload, ensure_ascii=False)
        except Exception as exc:  # pragma: no cover - guard mirrored in HTML
            logger.exception("Dashboard JSON Endpunkt fehlgeschlagen: %s", exc)
            return 500, json.dumps({"error": str(exc)})


def create_dashboard_app(client: CantaoSolaxClient, refresh_seconds: int = 30) -> DashboardApp:
    return DashboardApp(client, refresh_seconds)


class _DashboardRequestHandler(BaseHTTPRequestHandler):
    server: "_DashboardHTTPServer"

    def do_GET(self) -> None:  # noqa: N802 - signature required by BaseHTTPRequestHandler
        parsed = urlparse(self.path)
        if parsed.path == "/metrics.json":
            status, body = self.server.app.render_metrics_json()
            self._write_response(status, body, "application/json; charset=utf-8")
            return

        if parsed.path == "/":
            query = parse_qs(parsed.query).get("q", [""])[0]
            body = self.server.app.render_index(query)
            self._write_response(200, body, "text/html; charset=utf-8")
            return

        self.send_error(404, "Not Found")

    def log_message(self, format: str, *args: Any) -> None:  # noqa: A003 - keep signature
        logger.info("Dashboard request: " + format, *args)

    def _write_response(self, status: int, body: str, content_type: str) -> None:
        encoded = body.encode("utf-8")
        self.send_response(status)
        self.send_header("Content-Type", content_type)
        self.send_header("Content-Length", str(len(encoded)))
        self.end_headers()
        self.wfile.write(encoded)


class _DashboardHTTPServer(ThreadingHTTPServer):
    def __init__(self, server_address: Tuple[str, int], app: DashboardApp) -> None:
        super().__init__(server_address, _DashboardRequestHandler)
        self.app = app


def run_dashboard(
    client: CantaoSolaxClient,
    *,
    host: str = "127.0.0.1",
    port: int = 5000,
    refresh_seconds: int = 30,
    open_browser: bool = False,
) -> None:
    app = create_dashboard_app(client, refresh_seconds)
    server = _DashboardHTTPServer((host, port), app)

    if open_browser:
        import webbrowser

        def _open() -> None:
            url = f"http://{host}:{port}/"
            time.sleep(0.5)
            try:
                webbrowser.open(url)
            except Exception:  # pragma: no cover - environment dependent
                logger.warning("Browser konnte nicht geöffnet werden", exc_info=True)

        threading.Thread(target=_open, daemon=True).start()

    try:
        logger.info("Dashboard läuft auf http://%s:%s", host, port)
        server.serve_forever()
    except KeyboardInterrupt:  # pragma: no cover - manual shutdown
        logger.info("Dashboard wird beendet…")
    finally:
        server.server_close()


class _DemoClient:
    def __init__(self) -> None:
        now = time.strftime("%Y-%m-%dT%H:%M:%SZ", time.gmtime())
        self._payload = {
            "metrics": {
                "solax.acpower": 3421,
                "solax.yieldtoday": 11.8,
                "solax.yieldtotal": 4876.5,
                "solax.feedinpower": 2100,
                "solax.consumepower": 1450,
                "solax.soc": 68,
                "solax.temperature": 36.2,
                "solax.gridvoltage": 229.5,
            },
            "raw": {
                "timestamp": now,
                "source": "demo",
            },
        }

    def fetch_metrics(self) -> Dict[str, Any]:
        return self._payload


def create_demo_client() -> CantaoSolaxClient:
    return _DemoClient()  # type: ignore[return-value]


def _build_arg_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(description="Solax Dashboard Demo Server")
    parser.add_argument("--host", default="127.0.0.1", help="Bind address")
    parser.add_argument("--port", default=5000, type=int, help="Bind port")
    parser.add_argument("--refresh", default=30, type=int, help="Refresh interval")
    parser.add_argument("--demo", action="store_true", help="Integrierte Demo-Daten verwenden")
    return parser


def main() -> None:
    parser = _build_arg_parser()
    args = parser.parse_args()

    if args.demo:
        client = create_demo_client()
    else:  # pragma: no cover - direct invocation w/o demo only via CLI
        parser.error("Bitte verwenden Sie den CLI-Befehl `cantao-solax serve` für Live-Daten.")
        return

    run_dashboard(client, host=args.host, port=args.port, refresh_seconds=args.refresh)


if __name__ == "__main__":  # pragma: no cover - manual execution helper
    logging.basicConfig(level=logging.INFO)
    main()
