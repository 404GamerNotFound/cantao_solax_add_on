<?php

declare(strict_types=1);

namespace Cantao\SolaxBundle\Tests\Service;

use Cantao\SolaxBundle\Service\FakeDataGenerator;
use Cantao\SolaxBundle\Service\SolaxConfigurationProvider;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class FakeDataGeneratorTest extends TestCase
{
    public function testGeneratesDaytimeMetricsWithClouds(): void
    {
        $configProvider = $this->createMock(SolaxConfigurationProvider::class);
        $configProvider->method('getFakeDataSettings')->willReturn([
            'latitude' => 0.0,
            'longitude' => 0.0,
            'peak_power' => 5000.0,
            'base_total_yield' => 2000.0,
            'cloud_variability' => 0.5,
            'household_base_load' => 600.0,
        ]);

        $generator = new FakeDataGenerator($configProvider, new NullLogger());
        $noon = new DateTimeImmutable('2024-06-21 12:00:00', new DateTimeZone('UTC'));

        $metrics = $generator->generate($noon);

        self::assertArrayHasKey('acpower', $metrics);
        self::assertArrayHasKey('cloud_coverage', $metrics);
        self::assertGreaterThan(0.0, $metrics['acpower']);
        self::assertGreaterThan(0.0, $metrics['yieldtoday']);
        self::assertGreaterThanOrEqual(0.0, $metrics['cloud_coverage']);
        self::assertLessThanOrEqual(0.5, $metrics['cloud_coverage']);
    }

    public function testGeneratesNighttimeMetricsWithoutProduction(): void
    {
        $configProvider = $this->createMock(SolaxConfigurationProvider::class);
        $configProvider->method('getFakeDataSettings')->willReturn([
            'latitude' => 0.0,
            'longitude' => 0.0,
            'peak_power' => 5000.0,
            'base_total_yield' => 2000.0,
            'cloud_variability' => 0.5,
            'household_base_load' => 600.0,
        ]);

        $generator = new FakeDataGenerator($configProvider, new NullLogger());
        $night = new DateTimeImmutable('2024-06-21 23:30:00', new DateTimeZone('UTC'));

        $metrics = $generator->generate($night);

        self::assertEqualsWithDelta(0.0, $metrics['acpower'], 0.01);
        self::assertSame(0.0, $metrics['yieldtoday']);
        self::assertGreaterThan(0.0, $metrics['consumeenergy']);
    }
}
