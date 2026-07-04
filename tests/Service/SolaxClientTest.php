<?php

declare(strict_types=1);

namespace Cantao\SolaxBundle\Tests\Service;

use Cantao\SolaxBundle\Service\SolaxClient;
use Cantao\SolaxBundle\Service\SolaxConfigurationProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class SolaxClientTest extends TestCase
{
    public function testFetchRealtimeDataReturnsPayloadAndBuildsRequest(): void
    {
        $capturedUrl = null;
        $capturedOptions = null;

        $httpClient = new MockHttpClient(function (string $method, string $url, array $options = []) use (&$capturedUrl, &$capturedOptions): MockResponse {
            $capturedUrl = $url;
            $capturedOptions = $options;

            return new MockResponse(json_encode(['result' => ['acpower' => 123]]));
        });

        $configProvider = $this->createMock(SolaxConfigurationProvider::class);
        $configProvider->method('getSolaxConfig')->willReturn([
            'base_url' => 'https://www.solaxcloud.com:9443',
            'api_version' => 'v1',
            'api_key' => 'token',
            'serial_number' => 'sn123',
            'site_id' => 'plant-1',
            'timeout' => 10,
            'retry_count' => 0,
        ]);

        $client = new SolaxClient($httpClient, new NullLogger(), $configProvider);
        $data = $client->fetchRealtimeData();

        self::assertSame(['acpower' => 123], $data);
        self::assertNotNull($capturedUrl);
        self::assertStringContainsString('/api/v1/getRealtimeInfo', $capturedUrl);
        self::assertNotNull($capturedOptions);
        self::assertSame(
            [
                'sn' => 'sn123',
                'tokenId' => 'token',
                'plantId' => 'plant-1',
            ],
            $capturedOptions['query'] ?? []
        );
    }

    public function testMissingCredentialsThrowsRuntimeException(): void
    {
        $httpClient = new MockHttpClient(new MockResponse('[]'));
        $configProvider = $this->createMock(SolaxConfigurationProvider::class);
        $configProvider->method('getSolaxConfig')->willReturn([
            'base_url' => 'https://www.solaxcloud.com:9443',
            'api_version' => 'v1',
            'api_key' => null,
            'serial_number' => null,
        ]);

        $client = new SolaxClient($httpClient, new NullLogger(), $configProvider);

        $this->expectException(\RuntimeException::class);
        $client->fetchRealtimeData();
    }
}
