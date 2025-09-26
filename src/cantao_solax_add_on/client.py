"""High level client coordinating Solax and CANTAO interactions."""

from __future__ import annotations

import json
import logging
from dataclasses import dataclass, field
from typing import Any, Callable, Dict
from urllib import request
from urllib.error import HTTPError, URLError
from urllib.parse import urljoin

from .api import SolaxAPI
from .config import CantaoConfig

_LOGGER = logging.getLogger(__name__)


class CantaoPushError(RuntimeError):
    """Raised when posting data to CANTAO fails."""


Sender = Callable[[request.Request], Any]


@dataclass
class CantaoSolaxClient:
    """Coordinator that retrieves data from Solax and forwards it to CANTAO."""

    solax_api: SolaxAPI
    cantao_config: CantaoConfig
    sender: Sender = field(default_factory=lambda: request.urlopen)

    def fetch_metrics(self) -> Dict[str, Any]:
        """Fetch realtime data and normalise the payload."""

        _LOGGER.debug("Fetching realtime data from Solax")
        raw = self.solax_api.get_realtime_data()
        normalised = self._normalise_payload(raw)
        return {"raw": raw, "metrics": normalised}

    def push_metrics(self, metrics: Dict[str, Any]) -> None:
        """Push prepared metrics to CANTAO."""

        if not self.cantao_config.is_push_enabled():
            raise CantaoPushError("CANTAO push is not configured. Please set base_url and api_token.")

        endpoint = "/api/v1/metrics"
        url = urljoin(str(self.cantao_config.base_url), endpoint)
        headers = {
            "Authorization": f"Bearer {self.cantao_config.api_token}",
            "Content-Type": "application/json",
        }
        payload = json.dumps({"metrics": metrics}).encode("utf-8")

        _LOGGER.debug("Pushing %d metrics to CANTAO", len(metrics))

        req = request.Request(url, data=payload, headers=headers, method="POST")

        try:
            with self.sender(req) as resp:
                status = getattr(resp, "status", getattr(resp, "code", 200))
                if status >= 400:
                    body = resp.read().decode("utf-8", errors="ignore")
                    _LOGGER.error("Failed to push metrics to CANTAO: %s", body)
                    raise CantaoPushError(f"CANTAO responded with {status}")
        except HTTPError as exc:
            _LOGGER.error("HTTP error while pushing to CANTAO: %s", exc)
            raise CantaoPushError(f"CANTAO responded with {exc.code}") from exc
        except URLError as exc:
            _LOGGER.error("Connection error while pushing to CANTAO: %s", exc)
            raise CantaoPushError("Could not reach CANTAO endpoint") from exc

    def _normalise_payload(self, raw: Dict[str, Any]) -> Dict[str, Any]:
        mapping = self.cantao_config.metric_mapping
        prefix = self.cantao_config.metric_prefix
        metrics: Dict[str, Any] = {}

        for key, value in raw.items():
            if value in (None, ""):
                continue
            if isinstance(value, (int, float, bool)):
                prepared_value = value
            else:
                try:
                    prepared_value = float(value)
                except (TypeError, ValueError):
                    _LOGGER.debug("Skipping non-numeric field \"%s\"", key)
                    continue

            metric_key = mapping.get(key, f"{prefix}.{key}")
            metrics[metric_key] = prepared_value

        return metrics
