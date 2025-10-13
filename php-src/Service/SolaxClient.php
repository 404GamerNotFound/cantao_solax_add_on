<?php

declare(strict_types=1);

namespace Cantao\SolaxBundle\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SolaxClient
{
    private array $config;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        array $config
    ) {
        $this->config = $config;
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchRealtimeData(): array
    {
        $endpoint = 'getRealtimeInfo';
        $url = rtrim((string) $this->config['base_url'], '/') . '/api/' . trim((string) $this->config['api_version'], '/') . '/' . $endpoint;

        $options = [
            'query' => $this->buildQueryParameters(),
            'timeout' => (int) ($this->config['timeout'] ?? 10),
            'headers' => [
                'Accept' => 'application/json',
            ],
        ];

        try {
            $response = $this->httpClient->request('GET', $url, $options);
            $data = $response->toArray(false);
        } catch (ClientExceptionInterface | RedirectionExceptionInterface | ServerExceptionInterface | TransportExceptionInterface $exception) {
            $this->logger->error('Could not fetch realtime data from Solax: {message}', [
                'message' => $exception->getMessage(),
            ]);

            throw new \RuntimeException('Solax API request failed', 0, $exception);
        }

        if (isset($data['success']) && in_array($data['success'], [false, 0, 'false', '0'], true)) {
            $message = $data['exception'] ?? 'Solax API reported an error.';
            $this->logger->error('Solax API returned an error: {message}', ['message' => $message]);
            throw new \RuntimeException($message);
        }

        if (isset($data['result']) && is_array($data['result'])) {
            return $data['result'];
        }

        if (isset($data['data']) && is_array($data['data'])) {
            return $data['data'];
        }

        if (is_array($data)) {
            return $data;
        }

        $this->logger->error('Solax API responded with an unexpected payload.');
        throw new \RuntimeException('Unexpected response format from Solax API.');
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
}
