from pathlib import Path

from setuptools import setup

BASE_DIR = Path(__file__).resolve().parent
README = BASE_DIR / "README.md"

setup(
    name="cantao-solax-bundle",
    version="0.0.0",
    description="Placeholder package so CI 'pip install .' succeeds for the PHP bundle.",
    long_description=README.read_text(encoding="utf-8") if README.exists() else "",
    long_description_content_type="text/markdown",
    license="MIT",
    packages=["cantao_solax_placeholder"],
    python_requires=">=3.10",
)
