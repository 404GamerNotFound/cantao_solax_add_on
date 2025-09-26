from cantao_solax_add_on.client import CantaoSolaxClient, CantaoPushError
from cantao_solax_add_on.config import CantaoConfig


class DummySolaxAPI:
    def __init__(self, payload):
        self.payload = payload

    def get_realtime_data(self):
        return self.payload


class DummyResponse:
    def __init__(self, status: int, body: bytes):  # pragma: no cover - trivial helper
        self.status = status
        self._body = body

    def __enter__(self):
        return self

    def __exit__(self, exc_type, exc, tb):
        return False

    def read(self):
        return self._body


class DummySender:
    def __init__(self, status_code=200, body=b"{}"):  # pragma: no cover - trivial
        self.status_code = status_code
        self.body = body
        self.calls = []

    def __call__(self, req):
        self.calls.append(req)
        return DummyResponse(self.status_code, self.body)


def test_normalise_payload():
    payload = {"yieldtoday": "3.5", "yieldtotal": 1234.0, "temp": "not_a_number"}
    api = DummySolaxAPI(payload)
    cantao_config = CantaoConfig(metric_mapping={"yieldtoday": "energy.today"})
    client = CantaoSolaxClient(api, cantao_config)

    result = client.fetch_metrics()
    assert result["metrics"] == {"energy.today": 3.5, "solax.yieldtotal": 1234.0}


def test_push_requires_configuration():
    api = DummySolaxAPI({})
    cantao_config = CantaoConfig()
    client = CantaoSolaxClient(api, cantao_config)

    try:
        client.push_metrics({})
    except CantaoPushError as exc:
        assert "not configured" in str(exc)
    else:  # pragma: no cover - this path should never be reached
        raise AssertionError("Expected CantaoPushError")


def test_push_executes_post():
    api = DummySolaxAPI({})
    cantao_config = CantaoConfig(base_url="https://cantao.example", api_token="token")
    sender = DummySender()
    client = CantaoSolaxClient(api, cantao_config, sender=sender)

    client.push_metrics({"solax.power": 100})
    assert sender.calls
    req = sender.calls[0]
    assert req.get_header("Authorization") == "Bearer token"
