"""Low-level helpers for communicating with the Solax Cloud API."""

from __future__ import annotations

from dataclasses import dataclass, field
import json
from typing import Any, Callable, Dict, Optional
from urllib import error, request
from urllib.parse import urlencode, urljoin

from .config import SolaxConfig


class SolaxAPIError(RuntimeError):
    """Raised when the Solax API returns an error."""


ResponseOpener = Callable[[request.Request], Any]


@dataclass
class SolaxAPI:
    """Lightweight client for the Solax Cloud API."""

    config: SolaxConfig
    opener: ResponseOpener = field(default_factory=lambda: request.urlopen)

    def _build_url(self, path: str) -> str:
        api_root = f"/api/{self.config.api_version.strip('/')}/"
        return urljoin(str(self.config.base_url), api_root + path.lstrip("/"))

    def _request(self, path: str, extra_params: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
        params = self.config.to_request_params()
        if extra_params:
            params.update(extra_params)

        url = self._build_url(path)
        if params:
            url = f"{url}?{urlencode(params)}"

        req = request.Request(url, headers={"Accept": "application/json"})

        try:
            with self.opener(req) as resp:
                body = resp.read().decode("utf-8")
        except error.HTTPError as exc:  # pragma: no cover - network errors hard to reproduce
            raise SolaxAPIError(f"HTTP error {exc.code} from Solax API") from exc
        except error.URLError as exc:  # pragma: no cover - network errors hard to reproduce
            raise SolaxAPIError(f"Could not reach Solax API: {exc.reason}") from exc

        payload = json.loads(body)

        # the API signals errors in several ways depending on the version
        success_flag = payload.get("success")
        if success_flag in (False, 0, "false", "0"):
            raise SolaxAPIError(payload.get("exception") or "Solax API reported an error")

        if "result" in payload:
            return payload["result"]
        if "data" in payload:
            return payload["data"]

        if isinstance(payload, dict):
            return payload

        raise SolaxAPIError("Unexpected response format from Solax API")

    def get_realtime_data(self) -> Dict[str, Any]:
        """Fetch the latest realtime metrics."""

        endpoint = "getRealtimeInfo"
        return self._request(endpoint)

    def get_inverter_info(self) -> Dict[str, Any]:
        """Fetch static inverter information if supported by the account."""

        endpoint = "getInverterInfo"
        return self._request(endpoint)
