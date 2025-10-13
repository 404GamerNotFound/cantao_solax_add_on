<?php

declare(strict_types=1);

namespace Cantao\SolaxBundle\Cron;

use Cantao\SolaxBundle\Repository\MetricRepository;
use Cantao\SolaxBundle\Service\MetricNormalizer;
use Cantao\SolaxBundle\Service\SolaxClient;
use Psr\Log\LoggerInterface;

class SolaxSyncCron
{
    public function __construct(
        private readonly SolaxClient $solaxClient,
        private readonly MetricNormalizer $normalizer,
        private readonly MetricRepository $repository,
        private readonly LoggerInterface $logger,
        private string $interval
    ) {
    }

    public function __invoke(): void
    {
        try {
            $raw = $this->solaxClient->fetchRealtimeData();
            $metrics = $this->normalizer->normalise($raw);
            $this->repository->storeMetrics($metrics);

            $this->logger->info('Stored {count} Solax metrics.', [
                'count' => count($metrics),
            ]);
        } catch (\Throwable $exception) {
            $this->logger->error('Failed to synchronise Solax metrics: {message}', [
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function getInterval(): string
    {
        return $this->interval;
    }
}
