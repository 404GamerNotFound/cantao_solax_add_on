"""Minimal PEP 517 backend so CI can run ``pip install .`` without external deps."""

from __future__ import annotations

import io
import tarfile
import tempfile
import zipfile
from dataclasses import dataclass
from pathlib import Path
from typing import Iterable, Mapping, Sequence

ROOT = Path(__file__).resolve().parent
PACKAGE_ROOT = ROOT / "cantao_solax_placeholder"
EXTRA_FILES = [
    ROOT / "pyproject.toml",
    ROOT / "cantao_build_backend.py",
    ROOT / "setup.py",
    ROOT / "README.md",
]


@dataclass(frozen=True)
class Project:
    name: str
    version: str
    summary: str
    license: str

    @property
    def dist_info(self) -> str:
        return f"{self.name.replace('-', '_')}-{self.version}.dist-info"

    def metadata_text(self) -> str:
        return (
            "Metadata-Version: 2.1\n"
            f"Name: {self.name}\n"
            f"Version: {self.version}\n"
            f"Summary: {self.summary}\n"
            f"License: {self.license}\n"
        )

    def wheel_text(self) -> str:
        return (
            "Wheel-Version: 1.0\n"
            "Generator: cantao-solax-backend (custom backend)\n"
            "Root-Is-Purelib: true\n"
            "Tag: py3-none-any\n"
        )


PROJECT = Project(
    name="cantao-solax-bundle",
    version="0.0.0",
    summary="Placeholder package so CI 'pip install .' succeeds for the PHP bundle.",
    license="MIT",
)


def _package_files() -> Iterable[tuple[Path, str]]:
    for path in PACKAGE_ROOT.rglob("*"):
        if path.is_file():
            yield path, str(path.relative_to(ROOT))

    for path in EXTRA_FILES:
        if path.exists() and path.is_file():
            yield path, path.name


def _write_dist_info(base: Path) -> list[str]:
    dist_info = base / PROJECT.dist_info
    dist_info.mkdir(parents=True, exist_ok=True)
    (dist_info / "METADATA").write_text(PROJECT.metadata_text(), encoding="utf-8")
    (dist_info / "WHEEL").write_text(PROJECT.wheel_text(), encoding="utf-8")
    (dist_info / "RECORD").write_text("", encoding="utf-8")
    return [
        f"{PROJECT.dist_info}/METADATA",
        f"{PROJECT.dist_info}/WHEEL",
        f"{PROJECT.dist_info}/RECORD",
    ]


def get_requires_for_build_wheel(config_settings: Mapping[str, Sequence[str]] | None = None) -> list[str]:
    return []


def get_requires_for_build_sdist(config_settings: Mapping[str, Sequence[str]] | None = None) -> list[str]:
    return []


def prepare_metadata_for_build_wheel(
    metadata_directory: str, config_settings: Mapping[str, Sequence[str]] | None = None
) -> str:
    base = Path(metadata_directory)
    _write_dist_info(base)
    return PROJECT.dist_info


def build_wheel(
    wheel_directory: str,
    config_settings: Mapping[str, Sequence[str]] | None = None,
    metadata_directory: str | None = None,
) -> str:
    wheel_dir = Path(wheel_directory)
    wheel_dir.mkdir(parents=True, exist_ok=True)
    filename = f"{PROJECT.name.replace('-', '_')}-{PROJECT.version}-py3-none-any.whl"
    archive_path = wheel_dir / filename

    with zipfile.ZipFile(archive_path, "w") as archive, tempfile.TemporaryDirectory() as tmp:
        tmp_path = Path(tmp)
        record_entries: list[str] = []

        for file_path, arcname in _package_files():
            archive.write(file_path, arcname)
            record_entries.append(f"{arcname},,\n")

        dist_info_dir = tmp_path / PROJECT.dist_info
        _write_dist_info(tmp_path)
        for file_path in dist_info_dir.rglob("*"):
            if file_path.is_file():
                arcname = f"{PROJECT.dist_info}/{file_path.name}"
                archive.write(file_path, arcname)
                record_entries.append(f"{arcname},,\n")

        archive.writestr(f"{PROJECT.dist_info}/RECORD", "".join(record_entries))

    return filename


def build_sdist(
    sdist_directory: str,
    config_settings: Mapping[str, Sequence[str]] | None = None,
) -> str:
    sdist_dir = Path(sdist_directory)
    sdist_dir.mkdir(parents=True, exist_ok=True)
    filename = f"{PROJECT.name}-{PROJECT.version}.tar.gz"
    archive_path = sdist_dir / filename

    with tarfile.open(archive_path, "w:gz") as archive:
        base_folder = Path(f"{PROJECT.name}-{PROJECT.version}")
        for file_path, arcname in _package_files():
            target = base_folder / arcname
            info = archive.gettarinfo(str(file_path), arcname=str(target))
            with file_path.open("rb") as handle:
                archive.addfile(info, handle)

        metadata = PROJECT.metadata_text().encode("utf-8")
        info = tarfile.TarInfo(str(base_folder / "PKG-INFO"))
        info.size = len(metadata)
        archive.addfile(info, io.BytesIO(metadata))

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
