import json
import textwrap
from pathlib import Path

import pytest

from cantao_solax_add_on import cli


class DummyClient:
    def __init__(self, metrics=None):
        self.metrics = metrics or {"solax.power": 123}
        self.push_calls = []

    def fetch_metrics(self):
        return {"metrics": self.metrics, "raw": {"power": 123}}

    def push_metrics(self, metrics):
        self.push_calls.append(metrics)


@pytest.fixture()
def sample_config(tmp_path: Path) -> Path:
    config = tmp_path / "config.toml"
    config.write_text(
        textwrap.dedent(
            """
            [solax]
            base_url = "https://api.example"
            api_key = "abc"
            serial_number = "sn123"
            """
        ).strip()
    )
    return config


def test_cli_fetch_pretty(monkeypatch: pytest.MonkeyPatch, capsys, sample_config: Path):
    dummy = DummyClient()
    monkeypatch.setattr(cli, "_build_client", lambda config: dummy)

    exit_code = cli.main(["--config", str(sample_config), "fetch", "--pretty"])

    assert exit_code == 0
    captured = capsys.readouterr().out.strip()
    payload = json.loads(captured)
    assert payload["metrics"] == dummy.metrics
    assert "raw" in payload


def test_cli_push(monkeypatch: pytest.MonkeyPatch, capsys, sample_config: Path):
    dummy = DummyClient()
    monkeypatch.setattr(cli, "_build_client", lambda config: dummy)

    exit_code = cli.main(["--config", str(sample_config), "push"])

    assert exit_code == 0
    assert dummy.push_calls == [dummy.metrics]
    captured = capsys.readouterr().out.strip()
    assert json.loads(captured) == {"status": "ok", "pushed_metrics": len(dummy.metrics)}


def test_cli_serve(monkeypatch: pytest.MonkeyPatch, sample_config: Path):
    dummy = DummyClient()
    monkeypatch.setattr(cli, "_build_client", lambda config: dummy)

    called = {}

    def fake_run_dashboard(client, *, host, port, refresh_seconds, open_browser):
        called.update(
            {
                "client": client,
                "host": host,
                "port": port,
                "refresh": refresh_seconds,
                "open_browser": open_browser,
            }
        )

    monkeypatch.setattr(cli, "run_dashboard", fake_run_dashboard)

    exit_code = cli.main(
        [
            "--config",
            str(sample_config),
            "serve",
            "--host",
            "0.0.0.0",
            "--port",
            "8765",
            "--refresh",
            "10",
        ]
    )

    assert exit_code == 0
    assert called["client"] is dummy
    assert called["host"] == "0.0.0.0"
    assert called["port"] == 8765
    assert called["refresh"] == 10
    assert called["open_browser"] is False


def test_cli_serve_demo(monkeypatch: pytest.MonkeyPatch, sample_config: Path):
    monkeypatch.setattr(cli, "load_config", lambda path: (_ for _ in ()).throw(RuntimeError("should not load")))
    monkeypatch.setattr(cli, "_build_client", lambda config: (_ for _ in ()).throw(RuntimeError("should not build")))

    demo_client = object()
    monkeypatch.setattr(cli, "create_demo_client", lambda: demo_client)

    captured = {}

    def fake_run_dashboard(client, **kwargs):
        captured["client"] = client
        captured["kwargs"] = kwargs

    monkeypatch.setattr(cli, "run_dashboard", fake_run_dashboard)

    exit_code = cli.main(["--config", str(sample_config), "serve", "--demo-data", "--open-browser"])

    assert exit_code == 0
    assert captured["client"] is demo_client
    assert captured["kwargs"]["open_browser"] is True
