"""Configuration handling for the CANTAO Solax add-on."""

from __future__ import annotations

from dataclasses import dataclass, field
from pathlib import Path
from typing import Any, Dict, Optional
from urllib.parse import urlparse

import tomllib


def _validate_url(url: str) -> str:
    parsed = urlparse(url)
    if not parsed.scheme or not parsed.netloc:
        raise ValueError(f"Invalid URL: {url}")
    return url


@dataclass
class SolaxConfig:
    """Settings for accessing the Solax Cloud API."""

    base_url: str
    api_version: str = "v1"
    api_key: str = field(default="")
    serial_number: str = field(default="")
    site_id: Optional[str] = None
    timeout: int = 10

    def __post_init__(self) -> None:
        self.base_url = _validate_url(self.base_url)
        if self.api_version not in {"v1", "v2"}:
            raise ValueError("api_version must be 'v1' or 'v2'")
        if not self.api_key:
            raise ValueError("api_key must not be empty")
        if not self.serial_number:
            raise ValueError("serial_number must not be empty")
        if self.timeout <= 0:
            raise ValueError("timeout must be a positive integer")

    def to_request_params(self) -> Dict[str, Any]:
        params: Dict[str, Any] = {"sn": self.serial_number}

        if self.api_version == "v1":
            params["tokenId"] = self.api_key
            if self.site_id:
                params["plantId"] = self.site_id
        else:
            params["accessToken"] = self.api_key
            if self.site_id:
                params["uid"] = self.site_id

        return params


@dataclass
class CantaoConfig:
    """Settings describing how data should be pushed to CANTAO."""

    base_url: Optional[str] = None
    api_token: Optional[str] = None
    metric_prefix: str = "solax"
    metric_mapping: Dict[str, str] = field(default_factory=dict)

    def __post_init__(self) -> None:
        if self.base_url is not None:
            self.base_url = _validate_url(self.base_url)
        if self.metric_prefix.strip() == "":
            raise ValueError("metric_prefix must not be empty")

    def is_push_enabled(self) -> bool:
        return bool(self.base_url and self.api_token)


@dataclass
class AppConfig:
    solax: SolaxConfig
    cantao: CantaoConfig = field(default_factory=CantaoConfig)


def load_config(path: str | Path) -> AppConfig:
    config_path = Path(path)
    raw = tomllib.loads(config_path.read_text(encoding="utf-8"))

    if "solax" not in raw:
        raise ValueError("Missing [solax] section in configuration")

    solax = SolaxConfig(**raw["solax"])
    cantao = CantaoConfig(**raw.get("cantao", {}))
    return AppConfig(solax=solax, cantao=cantao)
