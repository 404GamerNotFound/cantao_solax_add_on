<?php

declare(strict_types=1);

namespace Cantao\SolaxBundle\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SolaxClient
{
    private const MAX_BACKOFF_DELAY_MS = 30000;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly SolaxConfigurationProvider $configurationProvider
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchRealtimeData(): array
    {
        $config = $this->configurationProvider->getSolaxConfig();
        $this->assertCredentials($config);

        $retryCount = max(0, (int) ($config['retry_count'] ?? 2));
        $retryDelayMs = max(100, (int) ($config['retry_delay'] ?? 1000));
        $endpoint = 'getRealtimeInfo';
        $url = rtrim((string) ($config['base_url'] ?? ''), '/') . '/api/' . trim((string) ($config['api_version'] ?? 'v1'), '/') . '/' . $endpoint;

        $attempts = $retryCount + 1;
        $lastException = null;

        for ($attempt = 1; $attempt <= $attempts; ++$attempt) {
            try {
                $options = $this->buildRequestOptions($config);
                $data = $this->httpClient->request('GET', $url, $options)->toArray(false);

                return $this->extractPayload($data);
            } catch (\Throwable $exception) {
                $lastException = $exception;

                if ($attempt >= $attempts) {
                    break;
                }

                $this->logger->warning('Attempt {attempt}/{attempts} to fetch Solax data failed: {message}', [
                    'attempt' => $attempt,
                    'attempts' => $attempts,
                    'message' => $exception->getMessage(),
                ]);

                $this->waitBeforeRetry($attempt, $retryDelayMs);
            }
        }

        $this->logger->error('Could not fetch realtime data from Solax after {attempts} attempts: {message}', [
            'attempts' => $attempts,
            'message' => $lastException?->getMessage(),
        ]);

        throw new \RuntimeException('Solax API request failed', 0, $lastException);
    }

    /**
     * @return array<string, string>
     */
    private function buildQueryParameters(array $config): array
    {
        $params = [
            'sn' => $config['serial_number'],
        ];

        if (($config['api_version'] ?? 'v1') === 'v1') {
            $params['tokenId'] = $config['api_key'];
            if (!empty($config['site_id'])) {
                $params['plantId'] = $config['site_id'];
            }
        } else {
            $params['accessToken'] = $config['api_key'];
            if (!empty($config['site_id'])) {
                $params['uid'] = $config['site_id'];
            }
        }

        return array_map(static fn ($value): string => (string) $value, $params);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function extractPayload(array $data): array
    {
        if (isset($data['success']) && in_array($data['success'], [false, 0, 'false', '0'], true)) {
            $message = (string) ($data['exception'] ?? $data['message'] ?? 'Solax API reported an error.');
            throw new \RuntimeException($message);
        }

        foreach (['result', 'data'] as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                return $data[$key];
            }
        }

        if (is_array($data)) {
            return $data;
        }

        throw new \RuntimeException('Unexpected response format from Solax API.');
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRequestOptions(array $config): array
    {
        return [
            'query' => $this->buildQueryParameters($config),
            'timeout' => (int) ($config['timeout'] ?? 10),
            'headers' => [
                'Accept' => 'application/json',
            ],
        ];
    }

    private function waitBeforeRetry(int $attempt, int $retryDelayMs): void
    {
        $delayMs = min($retryDelayMs * $attempt, self::MAX_BACKOFF_DELAY_MS);

        usleep($delayMs * 1000);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function assertCredentials(array $config): void
    {
        if (empty($config['api_key']) || empty($config['serial_number'])) {
            throw new \RuntimeException('Solax credentials are not configured.');
        }
    }
}
