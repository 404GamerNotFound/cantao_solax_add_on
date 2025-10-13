<?php

declare(strict_types=1);

namespace Cantao\SolaxBundle\ContaoManager;

use Cantao\SolaxBundle\CantaoSolaxBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\CoreBundle\ContaoCoreBundle;

class Plugin implements BundlePluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(CantaoSolaxBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }
}
