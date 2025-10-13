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
            if ($metrics === []) {
                $this->logger->notice('Solax API responded without usable metric values. Nothing was written to the database.');

                return;
            }

            $result = $this->repository->storeMetrics($metrics);

            if ($result->hasChanges()) {
                $this->logger->info('Stored {written} Solax metrics ({unchanged} unchanged).', [
                    'written' => $result->getWritten(),
                    'unchanged' => $result->getUnchanged(),
                ]);
            } else {
                $this->logger->notice('No Solax metric values changed since the last run. Skipped database write.');
            }
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
