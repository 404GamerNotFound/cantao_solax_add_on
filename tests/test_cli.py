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
