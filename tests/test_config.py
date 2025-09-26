from cantao_solax_add_on.config import AppConfig, CantaoConfig, SolaxConfig, load_config


def test_solax_request_params_v1():
    config = SolaxConfig(
        base_url="https://example.com",
        api_version="v1",
        api_key="token",
        serial_number="sn",
        site_id="plant",
    )

    params = config.to_request_params()
    assert params == {"sn": "sn", "tokenId": "token", "plantId": "plant"}


def test_solax_request_params_v2():
    config = SolaxConfig(
        base_url="https://example.com",
        api_version="v2",
        api_key="token",
        serial_number="sn",
        site_id="123",
    )
    params = config.to_request_params()
    assert params == {"sn": "sn", "accessToken": "token", "uid": "123"}


def test_cantao_push_flag():
    config = CantaoConfig()
    assert not config.is_push_enabled()

    config = CantaoConfig(base_url="https://cantao.example", api_token="123")
    assert config.is_push_enabled()


def test_app_config_validation(tmp_path):
    toml_content = """
    [solax]
    base_url = "https://example.com"
    api_version = "v2"
    api_key = "token"
    serial_number = "sn"

    [cantao]
    base_url = "https://cantao.example"
    api_token = "secret"
    """
    file_path = tmp_path / "config.toml"
    file_path.write_text(toml_content)

    config = load_config(file_path)
    assert isinstance(config, AppConfig)
    assert config.solax.api_version == "v2"
