import pathlib


def test_repository_root_exists():
    # Basic sanity check to ensure pytest discovers at least one test.
    assert pathlib.Path(__file__).resolve().parent.parent.exists()
