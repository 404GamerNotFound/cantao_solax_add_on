"""Minimal PEP 517 backend used for CI compatibility."""

from __future__ import annotations

import io
import tarfile
import zipfile
from pathlib import Path
from typing import Mapping, Sequence

_NAME = "cantao-solax-bundle"
_VERSION = "0.0.0"
_SUMMARY = (
    "Placeholder package so CI 'pip install .' succeeds for the PHP bundle."
)
_LICENSE = "MIT"


def _dist_info_dir() -> str:
    normalized = _NAME.replace("-", "_")
    return f"{normalized}-{_VERSION}.dist-info"


def _metadata() -> str:
    return (
        "Metadata-Version: 2.1\n"
        f"Name: {_NAME}\n"
        f"Version: {_VERSION}\n"
        f"Summary: {_SUMMARY}\n"
        f"License: {_LICENSE}\n"
    )


def _wheel_file() -> str:
    return (
        "Wheel-Version: 1.0\n"
        "Generator: cantao-solax-placeholder (custom backend)\n"
        "Root-Is-Purelib: true\n"
        "Tag: py3-none-any\n"
    )


def get_requires_for_build_wheel(config_settings: Mapping[str, Sequence[str]] | None = None) -> list[str]:
    return []


def get_requires_for_build_sdist(config_settings: Mapping[str, Sequence[str]] | None = None) -> list[str]:
    return []


def prepare_metadata_for_build_wheel(
    metadata_directory: str, config_settings: Mapping[str, Sequence[str]] | None = None
) -> str:
    path = Path(metadata_directory)
    dist_info = path / _dist_info_dir()
    dist_info.mkdir(parents=True, exist_ok=True)
    (dist_info / "METADATA").write_text(_metadata(), encoding="utf-8")
    (dist_info / "WHEEL").write_text(_wheel_file(), encoding="utf-8")
    (dist_info / "RECORD").write_text("", encoding="utf-8")
    return dist_info.name


def build_wheel(
    wheel_directory: str,
    config_settings: Mapping[str, Sequence[str]] | None = None,
    metadata_directory: str | None = None,
) -> str:
    wheel_dir = Path(wheel_directory)
    wheel_dir.mkdir(parents=True, exist_ok=True)
    filename = f"{_NAME.replace('-', '_')}-{_VERSION}-py3-none-any.whl"
    dist_info = _dist_info_dir()
    wheel_path = wheel_dir / filename

    with zipfile.ZipFile(wheel_path, "w") as archive:
        archive.writestr(f"{dist_info}/METADATA", _metadata())
        archive.writestr(f"{dist_info}/WHEEL", _wheel_file())
        archive.writestr(f"{dist_info}/RECORD", "")

    return filename


def build_sdist(
    sdist_directory: str,
    config_settings: Mapping[str, Sequence[str]] | None = None,
) -> str:
    sdist_dir = Path(sdist_directory)
    sdist_dir.mkdir(parents=True, exist_ok=True)
    filename = f"{_NAME}-{_VERSION}.tar.gz"
    sdist_path = sdist_dir / filename

    with tarfile.open(sdist_path, "w:gz") as archive:
        info = tarfile.TarInfo(name=f"{_NAME}-{_VERSION}/PKG-INFO")
        data = _metadata().encode("utf-8")
        info.size = len(data)
        archive.addfile(info, io.BytesIO(data))

    return filename


def build_editable(
    wheel_directory: str,
    config_settings: Mapping[str, Sequence[str]] | None = None,
    metadata_directory: str | None = None,
) -> str:
    return build_wheel(wheel_directory, config_settings, metadata_directory)


def get_requires_for_build_editable(
    config_settings: Mapping[str, Sequence[str]] | None = None,
) -> list[str]:
    return []
