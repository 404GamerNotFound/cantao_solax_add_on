<?php

declare(strict_types=1);

namespace Cantao\SolaxBundle\Service;

use Cantao\SolaxBundle\Repository\MetricRepository;
use Psr\Log\LoggerInterface;
use Throwable;

class SolaxDataResolver
{
    public function __construct(
        private readonly SolaxConfigurationProvider $configurationProvider,
        private readonly FakeDataGenerator $fakeDataGenerator,
        private readonly SolaxClient $client,
        private readonly MetricNormalizer $normalizer,
        private readonly MetricRepository $repository,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @return array{metrics: array<string, float|int|bool>, source: string, timestamp: int|null}
     */
    public function resolve(): array
    {
        $source = 'storage';
        $timestamp = null;
        $metrics = [];

        if ($this->configurationProvider->isFakeModeEnabled()) {
            $metrics = $this->normalizer->normalise($this->fakeDataGenerator->generate());
            $timestamp = time();
            $source = 'fake';
            $this->repository->storeMetrics($metrics);

            return [
                'metrics' => $metrics,
                'source' => $source,
                'timestamp' => $timestamp,
            ];
        }

        if ($this->configurationProvider->hasCredentials()) {
            try {
                $metrics = $this->normalizer->normalise($this->client->fetchRealtimeData());
                $timestamp = time();
                $source = 'api';
                if ($metrics !== []) {
                    $this->repository->storeMetrics($metrics);
                }
            } catch (Throwable $exception) {
                $this->logger->error('Failed to fetch Solax API metrics for frontend module: {message}', [
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        if ($metrics === []) {
            $stored = $this->repository->fetchAllMetrics();
            if ($stored['metrics'] !== []) {
                $metrics = $stored['metrics'];
                $timestamp = $stored['timestamp'];
                $source = 'storage';
            }
        }

        if ($metrics === []) {
            $metrics = $this->normalizer->normalise($this->fakeDataGenerator->generate());
            $timestamp = time();
            $source = 'fallback_fake';
        }

        return [
            'metrics' => $metrics,
            'source' => $source,
            'timestamp' => $timestamp,
        ];
    }
}
