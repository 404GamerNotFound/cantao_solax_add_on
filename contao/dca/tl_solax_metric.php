<?php

declare(strict_types=1);

$GLOBALS['TL_DCA']['tl_solax_metric'] = [
    'config' => [
        'dataContainer' => 'Table',
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'metric_key' => 'unique',
            ],
        ],
    ],
    'list' => [
        'sorting' => [
            'mode' => 1,
            'fields' => ['metric_key'],
            'flag' => 1,
        ],
        'label' => [
            'fields' => ['metric_key', 'metric_value'],
            'format' => '%s: %s',
        ],
        'global_operations' => [
            'all' => [
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset();" accesskey="e"',
            ],
        ],
        'operations' => [
            'edit' => [
                'href' => 'act=edit',
                'icon' => 'edit.svg',
            ],
            'delete' => [
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\'TL_CONFIRM\'))return false;Backend.getScrollOffset();"',
            ],
        ],
    ],
    'palettes' => [
        'default' => '{metric_legend},metric_key,metric_value,tstamp',
    ],
    'fields' => [
        'id' => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'tstamp' => [
            'sorting' => true,
            'flag' => 6,
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'metric_key' => [
            'label' => ['Metric Key', 'Unique identifier of the metric'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'readonly' => true],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'metric_value' => [
            'label' => ['Value', 'Latest metric value'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
    ],
];
