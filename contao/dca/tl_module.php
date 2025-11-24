<?php

declare(strict_types=1);

use Cantao\SolaxBundle\Controller\FrontendModule\SolaxMetricsController;

$GLOBALS['TL_DCA']['tl_module']['palettes'][SolaxMetricsController::TYPE]
    = '{title_legend},name,headline,type;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';
