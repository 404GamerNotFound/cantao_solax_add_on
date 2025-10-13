<?php

declare(strict_types=1);

namespace Cantao\SolaxBundle\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SolaxClient
{
    private array $config;
    private readonly int $retryCount;
    private readonly int $retryDelayMs;

    private const MAX_BACKOFF_DELAY_MS = 30000;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        array $config
    ) {
        $this->config = $config;
        $this->retryCount = max(0, (int) ($this->config['retry_count'] ?? 2));
        $this->retryDelayMs = max(100, (int) ($this->config['retry_delay'] ?? 1000));
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchRealtimeData(): array
    {
        $endpoint = 'getRealtimeInfo';
        $url = rtrim((string) $this->config['base_url'], '/') . '/api/' . trim((string) $this->config['api_version'], '/') . '/' . $endpoint;

        $options = $this->buildRequestOptions();

        $attempts = $this->retryCount + 1;
        $lastException = null;

        for ($attempt = 1; $attempt <= $attempts; ++$attempt) {
            try {
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

                $this->waitBeforeRetry($attempt);
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
    private function buildQueryParameters(): array
    {
        $params = [
            'sn' => $this->config['serial_number'],
        ];

        if (($this->config['api_version'] ?? 'v1') === 'v1') {
            $params['tokenId'] = $this->config['api_key'];
            if (!empty($this->config['site_id'])) {
                $params['plantId'] = $this->config['site_id'];
            }
        } else {
            $params['accessToken'] = $this->config['api_key'];
            if (!empty($this->config['site_id'])) {
                $params['uid'] = $this->config['site_id'];
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
    private function buildRequestOptions(): array
    {
        return [
            'query' => $this->buildQueryParameters(),
            'timeout' => (int) ($this->config['timeout'] ?? 10),
            'headers' => [
                'Accept' => 'application/json',
            ],
        ];
    }

    private function waitBeforeRetry(int $attempt): void
    {
        $delayMs = min($this->retryDelayMs * $attempt, self::MAX_BACKOFF_DELAY_MS);

        usleep($delayMs * 1000);
    }
}
