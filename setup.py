from pathlib import Path
from distutils.core import setup

README = Path(__file__).with_name("README.md")

setup(
    name="cantao-solax-bundle",
    version="0.0.0",
    description="Placeholder package so CI 'pip install .' succeeds for the PHP bundle.",
    long_description=README.read_text(encoding="utf-8") if README.exists() else "",
)
