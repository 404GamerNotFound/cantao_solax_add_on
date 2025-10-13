<?php

declare(strict_types=1);

namespace Cantao\SolaxBundle\DependencyInjection;

use Cantao\SolaxBundle\Cron\SolaxSyncCron;
use Cantao\SolaxBundle\Repository\MetricRepository;
use Cantao\SolaxBundle\Service\MetricNormalizer;
use Cantao\SolaxBundle\Service\SolaxClient;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class CantaoSolaxExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('cantao_solax.solax_config', $config['solax']);
        $container->setParameter('cantao_solax.metric_mapping', $config['cantao']['metric_mapping']);
        $container->setParameter('cantao_solax.metric_prefix', $config['cantao']['metric_prefix']);
        $container->setParameter('cantao_solax.ignored_fields', $config['cantao']['ignore_fields']);
        $container->setParameter('cantao_solax.decimal_precision', $config['cantao']['decimal_precision']);
        $container->setParameter('cantao_solax.cron_interval', $config['cron']['interval']);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.php');

        $container->getDefinition(SolaxClient::class)
            ->setArgument('$config', '%cantao_solax.solax_config%');

        $container->getDefinition(MetricNormalizer::class)
            ->setArgument('$mapping', '%cantao_solax.metric_mapping%')
            ->setArgument('$prefix', '%cantao_solax.metric_prefix%')
            ->setArgument('$ignoredFields', '%cantao_solax.ignored_fields%')
            ->setArgument('$decimalPrecision', '%cantao_solax.decimal_precision%');

        $cronDefinition = $container->getDefinition(SolaxSyncCron::class);
        $cronDefinition
            ->setArgument('$interval', '%cantao_solax.cron_interval%');
        $cronDefinition->clearTag('contao.cronjob');
        $cronDefinition->addTag('contao.cronjob', ['interval' => $config['cron']['interval']]);

        if ($container->hasDefinition(MetricRepository::class)) {
            $container->getDefinition(MetricRepository::class)
                ->setArgument('$tableName', $config['storage']['table']);
        }
    }
}
