<?php

declare(strict_types=1);

$GLOBALS['TL_LANG']['tl_settings']['solax_fake_legend'] = 'Solax fake data';
$GLOBALS['TL_LANG']['tl_settings']['solax_credentials_legend'] = 'Solax credentials';

$GLOBALS['TL_LANG']['tl_settings']['solax_fake_data_mode'] = ['Enable fake data mode', 'Generate synthetic PV data instead of requesting the Solax Cloud API.'];
$GLOBALS['TL_LANG']['tl_settings']['solax_fake_latitude'] = ['Latitude', 'Latitude used to calculate sunrise and sunset times.'];
$GLOBALS['TL_LANG']['tl_settings']['solax_fake_longitude'] = ['Longitude', 'Longitude used to calculate sunrise and sunset times.'];
$GLOBALS['TL_LANG']['tl_settings']['solax_fake_peak_power'] = ['Peak power (W)', 'Nominal peak power of the simulated PV system in watts.'];
$GLOBALS['TL_LANG']['tl_settings']['solax_fake_base_total'] = ['Base total yield (kWh)', 'Starting point for the simulated lifetime energy yield.'];
$GLOBALS['TL_LANG']['tl_settings']['solax_fake_cloud_variability'] = ['Cloud variability', 'Controls how strongly clouds reduce the simulated solar output.'];
$GLOBALS['TL_LANG']['tl_settings']['solax_fake_cloud_variability_options'] = [
    '0' => 'No clouds (0%)',
    '0.2' => 'Light variation (~20%)',
    '0.35' => 'Moderate variation (~35%)',
    '0.5' => 'Changeable (~50%)',
    '0.75' => 'Mostly cloudy (~75%)',
    '1' => 'Very cloudy (100%)',
];
$GLOBALS['TL_LANG']['tl_settings']['solax_fake_household_load'] = ['Base household load (W)', 'Average continuous consumption that should always be covered.'];

$GLOBALS['TL_LANG']['tl_settings']['solax_base_url'] = ['Solax API base URL', 'Endpoint of the Solax Cloud API.'];
$GLOBALS['TL_LANG']['tl_settings']['solax_api_version'] = ['API version', 'Choose between Solax Cloud API v1 or v2.'];
$GLOBALS['TL_LANG']['tl_settings']['solax_api_key'] = ['API key', 'Personal API key for your Solax Cloud account.'];
$GLOBALS['TL_LANG']['tl_settings']['solax_serial_number'] = ['Inverter serial number', 'Serial number of the Solax inverter.'];
$GLOBALS['TL_LANG']['tl_settings']['solax_site_id'] = ['Site/Plant ID or UID', 'Optional plant identifier required by some API versions.'];
$GLOBALS['TL_LANG']['tl_settings']['solax_timeout'] = ['Timeout (s)', 'Maximum time in seconds to wait for the Solax API response.'];
$GLOBALS['TL_LANG']['tl_settings']['solax_retry_count'] = ['Retry count', 'Number of retries after a failed Solax API request.'];
$GLOBALS['TL_LANG']['tl_settings']['solax_retry_delay'] = ['Retry delay (ms)', 'Delay in milliseconds between retry attempts.'];
