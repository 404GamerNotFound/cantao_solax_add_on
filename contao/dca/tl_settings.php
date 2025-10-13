<?php

declare(strict_types=1);

use Contao\CoreBundle\DataContainer\PaletteManipulator;

PaletteManipulator::create()
    ->addLegend('solax_fake_legend', 'cantao_legend', PaletteManipulator::POSITION_AFTER)
    ->addField('solax_fake_data_mode', 'solax_fake_legend', PaletteManipulator::POSITION_APPEND)
    ->addLegend('solax_credentials_legend', 'solax_fake_legend', PaletteManipulator::POSITION_AFTER)
    ->addField('solax_base_url', 'solax_credentials_legend', PaletteManipulator::POSITION_APPEND)
    ->addField('solax_api_version', 'solax_credentials_legend', PaletteManipulator::POSITION_APPEND)
    ->addField('solax_api_key', 'solax_credentials_legend', PaletteManipulator::POSITION_APPEND)
    ->addField('solax_serial_number', 'solax_credentials_legend', PaletteManipulator::POSITION_APPEND)
    ->addField('solax_site_id', 'solax_credentials_legend', PaletteManipulator::POSITION_APPEND)
    ->addField('solax_timeout', 'solax_credentials_legend', PaletteManipulator::POSITION_APPEND)
    ->addField('solax_retry_count', 'solax_credentials_legend', PaletteManipulator::POSITION_APPEND)
    ->addField('solax_retry_delay', 'solax_credentials_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_settings');

$GLOBALS['TL_DCA']['tl_settings']['palettes']['__selector__'][] = 'solax_fake_data_mode';
$GLOBALS['TL_DCA']['tl_settings']['subpalettes']['solax_fake_data_mode'] = 'solax_fake_latitude,solax_fake_longitude,solax_fake_peak_power,solax_fake_base_total,solax_fake_cloud_variability,solax_fake_household_load';

$GLOBALS['TL_DCA']['tl_settings']['fields']['solax_fake_data_mode'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_settings']['solax_fake_data_mode'],
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 m12', 'submitOnChange' => true],
    'sql' => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_settings']['fields']['solax_fake_latitude'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_settings']['solax_fake_latitude'],
    'inputType' => 'text',
    'default' => '52.52',
    'eval' => ['rgxp' => 'number', 'maxlength' => 16, 'tl_class' => 'w50'],
    'sql' => "varchar(16) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_settings']['fields']['solax_fake_longitude'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_settings']['solax_fake_longitude'],
    'inputType' => 'text',
    'default' => '13.405',
    'eval' => ['rgxp' => 'number', 'maxlength' => 16, 'tl_class' => 'w50'],
    'sql' => "varchar(16) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_settings']['fields']['solax_fake_peak_power'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_settings']['solax_fake_peak_power'],
    'inputType' => 'text',
    'default' => '5000',
    'eval' => ['rgxp' => 'number', 'maxlength' => 16, 'tl_class' => 'w50'],
    'sql' => "varchar(16) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_settings']['fields']['solax_fake_base_total'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_settings']['solax_fake_base_total'],
    'inputType' => 'text',
    'default' => '2500',
    'eval' => ['rgxp' => 'number', 'maxlength' => 16, 'tl_class' => 'w50'],
    'sql' => "varchar(16) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_settings']['fields']['solax_fake_cloud_variability'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_settings']['solax_fake_cloud_variability'],
    'inputType' => 'select',
    'default' => '0.35',
    'options' => ['0', '0.2', '0.35', '0.5', '0.75', '1'],
    'reference' => &$GLOBALS['TL_LANG']['tl_settings']['solax_fake_cloud_variability_options'],
    'eval' => ['tl_class' => 'w50', 'includeBlankOption' => true, 'chosen' => true],
    'sql' => "varchar(8) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_settings']['fields']['solax_fake_household_load'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_settings']['solax_fake_household_load'],
    'inputType' => 'text',
    'default' => '600',
    'eval' => ['rgxp' => 'digit', 'maxlength' => 16, 'tl_class' => 'w50'],
    'sql' => "varchar(16) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_settings']['fields']['solax_base_url'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_settings']['solax_base_url'],
    'default' => 'https://www.solaxcloud.com:9443',
    'inputType' => 'text',
    'eval' => ['rgxp' => 'url', 'maxlength' => 255, 'decodeEntities' => true, 'tl_class' => 'w50 clr'],
    'sql' => "varchar(255) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_settings']['fields']['solax_api_version'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_settings']['solax_api_version'],
    'default' => 'v1',
    'inputType' => 'select',
    'options' => ['v1', 'v2'],
    'eval' => ['includeBlankOption' => true, 'tl_class' => 'w50'],
    'sql' => "varchar(16) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_settings']['fields']['solax_api_key'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_settings']['solax_api_key'],
    'inputType' => 'text',
    'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
    'sql' => "varchar(255) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_settings']['fields']['solax_serial_number'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_settings']['solax_serial_number'],
    'inputType' => 'text',
    'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
    'sql' => "varchar(255) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_settings']['fields']['solax_site_id'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_settings']['solax_site_id'],
    'inputType' => 'text',
    'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
    'sql' => "varchar(255) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_settings']['fields']['solax_timeout'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_settings']['solax_timeout'],
    'default' => '10',
    'inputType' => 'text',
    'eval' => ['rgxp' => 'digit', 'tl_class' => 'w50'],
    'sql' => "int(10) unsigned NOT NULL default '0'",
];

$GLOBALS['TL_DCA']['tl_settings']['fields']['solax_retry_count'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_settings']['solax_retry_count'],
    'default' => '2',
    'inputType' => 'text',
    'eval' => ['rgxp' => 'digit', 'tl_class' => 'w50'],
    'sql' => "int(10) unsigned NOT NULL default '0'",
];

$GLOBALS['TL_DCA']['tl_settings']['fields']['solax_retry_delay'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_settings']['solax_retry_delay'],
    'default' => '1000',
    'inputType' => 'text',
    'eval' => ['rgxp' => 'digit', 'tl_class' => 'w50'],
    'sql' => "int(10) unsigned NOT NULL default '0'",
];
