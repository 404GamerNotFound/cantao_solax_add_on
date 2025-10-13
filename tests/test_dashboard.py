import json

import pytest

from cantao_solax_add_on import dashboard


class DummyClient:
    def __init__(self, payload):
        self.payload = payload
        self.calls = 0

    def fetch_metrics(self):
        self.calls += 1
        if isinstance(self.payload, Exception):
            raise self.payload
        return self.payload


@pytest.fixture()
def base_payload():
    return {
        "metrics": {
            "solax.acpower": 2500,
            "solax.yieldtoday": 9.5,
            "solax.yieldtotal": 4100.2,
            "solax.soc": 72,
            "solax.gridvoltage": 228.1,
        },
        "raw": {"source": "tests"},
    }


def test_dashboard_renders_metrics(base_payload):
    app = dashboard.create_dashboard_app(DummyClient(base_payload), refresh_seconds=15)

    html_output = app.render_index()

    assert "Solax Monitoring" in html_output
    assert "2500" in html_output
    assert "Aktuelle Leistung" in html_output
    assert 'meta http-equiv="refresh" content="15"' in html_output


def test_dashboard_filters_results(base_payload):
    app = dashboard.create_dashboard_app(DummyClient(base_payload))

    html_output = app.render_index("grid")

    assert "solax.gridvoltage" in html_output
    assert "solax.yieldtoday" not in html_output


def test_dashboard_error_display():
    failing_client = DummyClient(RuntimeError("kaputt"))
    app = dashboard.create_dashboard_app(failing_client)

    html_output = app.render_index()
    assert "Fehler beim Abrufen" in html_output


def test_dashboard_metrics_json(base_payload):
    dummy = DummyClient(base_payload)
    app = dashboard.create_dashboard_app(dummy)

    status, payload_text = app.render_metrics_json()
    assert status == 200
    payload = json.loads(payload_text)
    assert payload["metrics"]["solax.acpower"] == 2500
