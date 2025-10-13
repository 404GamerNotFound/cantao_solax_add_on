<?php

declare(strict_types=1);

use Cantao\SolaxBundle\Cron\SolaxSyncCron;
use Cantao\SolaxBundle\Repository\MetricRepository;
use Cantao\SolaxBundle\Service\MetricNormalizer;
use Cantao\SolaxBundle\Service\SolaxClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();

    $services
        ->set(SolaxClient::class)
        ->args([
            service('http_client'),
            service(LoggerInterface::class),
            [],
        ]);

    $services
        ->set(MetricNormalizer::class)
        ->args([
            [],
            'solax',
        ]);

    $services
        ->set(MetricRepository::class)
        ->args([
            service('database_connection'),
            'tl_solax_metric',
        ]);

    $services
        ->set(SolaxSyncCron::class)
        ->args([
            service(SolaxClient::class),
            service(MetricNormalizer::class),
            service(MetricRepository::class),
            service(LoggerInterface::class),
            'hourly',
        ])
        ->tag('contao.cronjob', ['interval' => 'hourly']);
};
