<?php

declare(strict_types=1);

$GLOBALS['BE_MOD']['system']['solax_metrics'] = [
    'tables' => ['tl_solax_metric'],
];

// Frontend module registration
$GLOBALS['FE_MOD']['application'][\Cantao\SolaxBundle\Controller\FrontendModule\SolaxMetricsController::TYPE]
    = \Cantao\SolaxBundle\Controller\FrontendModule\SolaxMetricsController::class;
