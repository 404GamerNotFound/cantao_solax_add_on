<?php

declare(strict_types=1);

$GLOBALS['TL_LANG']['tl_settings']['solax_fake_legend'] = 'Solax Fake-Daten';
$GLOBALS['TL_LANG']['tl_settings']['solax_credentials_legend'] = 'Solax Zugangsdaten';

$GLOBALS['TL_LANG']['tl_settings']['solax_fake_data_mode'] = ['Fake-Daten aktivieren', 'Erzeugt simulierte PV-Werte anstelle eines Abrufs der Solax-Cloud.'];
$GLOBALS['TL_LANG']['tl_settings']['solax_fake_latitude'] = ['Breitengrad', 'Breitengrad zur Berechnung von Sonnenauf- und -untergang.'];
$GLOBALS['TL_LANG']['tl_settings']['solax_fake_longitude'] = ['Längengrad', 'Längengrad zur Berechnung von Sonnenauf- und -untergang.'];
$GLOBALS['TL_LANG']['tl_settings']['solax_fake_peak_power'] = ['Peakleistung (W)', 'Nennleistung der simulierten PV-Anlage in Watt.'];
$GLOBALS['TL_LANG']['tl_settings']['solax_fake_base_total'] = ['Basis-Gesamtertrag (kWh)', 'Startwert für den simulierten Gesamtertrag.'];
$GLOBALS['TL_LANG']['tl_settings']['solax_fake_cloud_variability'] = ['Wolkenvariabilität', 'Steuert, wie stark Wolken die simulierte PV-Leistung reduzieren.'];
$GLOBALS['TL_LANG']['tl_settings']['solax_fake_cloud_variability_options'] = [
    '0' => 'Keine Wolken (0 %)',
    '0.2' => 'Leichte Schwankung (~20 %)',
    '0.35' => 'Mittlere Schwankung (~35 %)',
    '0.5' => 'Wechselhaft (~50 %)',
    '0.75' => 'Überwiegend bewölkt (~75 %)',
    '1' => 'Sehr bewölkt (100 %)',
];
$GLOBALS['TL_LANG']['tl_settings']['solax_fake_household_load'] = ['Grundlast Haushalt (W)', 'Durchschnittliche Dauerlast, die stets gedeckt werden soll.'];

$GLOBALS['TL_LANG']['tl_settings']['solax_base_url'] = ['Solax API-Basis-URL', 'Basisadresse der Solax-Cloud-API.'];
$GLOBALS['TL_LANG']['tl_settings']['solax_api_version'] = ['API-Version', 'Auswahl zwischen Solax Cloud API v1 und v2.'];
$GLOBALS['TL_LANG']['tl_settings']['solax_api_key'] = ['API-Schlüssel', 'Persönlicher API-Schlüssel für Ihr Solax-Cloud-Konto (wird verschlüsselt gespeichert).'];
$GLOBALS['TL_LANG']['tl_settings']['solax_serial_number'] = ['Wechselrichter-Seriennummer', 'Seriennummer des Solax-Wechselrichters (wird verschlüsselt gespeichert).'];
$GLOBALS['TL_LANG']['tl_settings']['solax_site_id'] = ['Anlagen-/UID', 'Optionale Anlagen-ID bzw. UID (wird verschlüsselt gespeichert).'];
$GLOBALS['TL_LANG']['tl_settings']['solax_timeout'] = ['Timeout (s)', 'Maximale Wartezeit in Sekunden auf eine Antwort der Solax-API.'];
$GLOBALS['TL_LANG']['tl_settings']['solax_retry_count'] = ['Anzahl Wiederholungen', 'Wie oft fehlgeschlagene Solax-Anfragen erneut versucht werden.'];
$GLOBALS['TL_LANG']['tl_settings']['solax_retry_delay'] = ['Wartezeit zwischen Versuchen (ms)', 'Pause in Millisekunden zwischen den Wiederholungsversuchen.'];
