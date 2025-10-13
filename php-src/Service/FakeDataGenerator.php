<?php

declare(strict_types=1);

namespace Cantao\SolaxBundle\Service;

use DateTimeImmutable;
use Psr\Log\LoggerInterface;

class FakeDataGenerator
{
    public function __construct(
        private readonly SolaxConfigurationProvider $configurationProvider,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @return array<string, float|int>
     */
    public function generate(): array
    {
        $settings = $this->configurationProvider->getFakeDataSettings();
        $now = new DateTimeImmutable('now');
        $sunTimes = $this->resolveSunTimes($now, $settings['latitude'] ?? 0.0, $settings['longitude'] ?? 0.0);
        $timestamp = $now->getTimestamp();
        $sunrise = $sunTimes['sunrise'];
        $sunset = $sunTimes['sunset'];
        $daylightSeconds = max(1, $sunset - $sunrise);
        $isDaytime = $timestamp > $sunrise && $timestamp < $sunset;

        $progress = $this->calculateProgress($timestamp, $sunrise, $sunset);
        $baseIntensity = $isDaytime ? sin(M_PI * $progress) : 0.0;
        $cloudCoverage = $this->calculateCloudCoverage($timestamp, $settings['cloud_variability'] ?? 0.0);
        $cloudFactor = max(0.2, 1 - 0.7 * $cloudCoverage);
        $intensity = max(0.0, $baseIntensity * $cloudFactor);

        $peakPower = $settings['peak_power'] ?? 0.0;
        $acPower = $intensity * $peakPower;

        $energyFraction = $isDaytime ? (1 - cos(M_PI * $progress)) / 2 : 0.0;
        $daylightHours = $daylightSeconds / 3600;
        $averageIntensity = 2 / M_PI;
        $dailyPotential = ($peakPower / 1000) * $daylightHours * $averageIntensity;
        $cloudEfficiency = max(0.25, 1 - 0.5 * $cloudCoverage);
        $yieldToday = $dailyPotential * $energyFraction * $cloudEfficiency;
        $yieldTotal = ($settings['base_total_yield'] ?? 0.0) + $yieldToday;

        $householdBaseLoad = $settings['household_base_load'] ?? 0.0;
        $consumption = $this->calculateConsumption($timestamp, $householdBaseLoad, $acPower, $isDaytime, $progress);
        $feedInPower = max(0.0, $acPower - $consumption);
        $selfConsumptionPower = max(0.0, $acPower - $feedInPower);

        $secondsSinceMidnight = $timestamp - $now->setTime(0, 0)->getTimestamp();
        $baseConsumptionEnergy = ($householdBaseLoad / 1000) * ($secondsSinceMidnight / 3600);
        $selfConsumptionEnergy = $yieldToday > 0
            ? min($yieldToday, $yieldToday * min(0.7, $selfConsumptionPower > 0 ? $selfConsumptionPower / max($acPower, 1.0) : 0.3))
            : 0.0;
        $consumeEnergy = $baseConsumptionEnergy + $selfConsumptionEnergy;
        $feedInEnergy = max(0.0, $yieldToday - $selfConsumptionEnergy);

        $stateOfCharge = $this->calculateStateOfCharge($timestamp, $sunTimes, $energyFraction, $cloudCoverage);
        $batteryPower = $this->calculateBatteryPower($stateOfCharge, $consumption, $feedInPower, $peakPower, $isDaytime);

        $pvStringSplit = $this->splitPvStrings($acPower);

        $metrics = [
            'timestamp' => $timestamp,
            'acpower' => round($acPower, 2),
            'yieldtoday' => round($yieldToday, 3),
            'yieldtotal' => round($yieldTotal, 3),
            'feedinpower' => round($feedInPower, 2),
            'feedinenergy' => round($feedInEnergy, 3),
            'consumeenergy' => round($consumeEnergy, 3),
            'consumptionpower' => round($consumption, 2),
            'soc' => round($stateOfCharge, 1),
            'batterypower' => round($batteryPower, 2),
            'pvpower1' => round($pvStringSplit[0], 2),
            'pvpower2' => round($pvStringSplit[1], 2),
            'cloud_coverage' => round($cloudCoverage, 3),
            'self_consumption_power' => round($selfConsumptionPower, 2),
        ];

        $this->logger->debug('Generated fake Solax metrics.', $metrics);

        return $metrics;
    }

    /**
     * @return array{sunrise:int,sunset:int}
     */
    private function resolveSunTimes(DateTimeImmutable $now, float $latitude, float $longitude): array
    {
        $sunInfo = date_sun_info($now->getTimestamp(), $latitude, $longitude);
        $sunrise = $sunInfo['sunrise'] ?? false;
        $sunset = $sunInfo['sunset'] ?? false;

        if (!is_int($sunrise) || !is_int($sunset) || $sunrise === $sunset || $sunset < $sunrise) {
            $fallbackSunrise = $now->setTime(6, 0);
            $fallbackSunset = $now->setTime(18, 0);

            return [
                'sunrise' => $fallbackSunrise->getTimestamp(),
                'sunset' => $fallbackSunset->getTimestamp(),
            ];
        }

        return [
            'sunrise' => $sunrise,
            'sunset' => $sunset,
        ];
    }

    private function calculateProgress(int $timestamp, int $sunrise, int $sunset): float
    {
        if ($timestamp <= $sunrise) {
            return 0.0;
        }

        if ($timestamp >= $sunset) {
            return 1.0;
        }

        return ($timestamp - $sunrise) / max(1, $sunset - $sunrise);
    }

    private function calculateCloudCoverage(int $timestamp, float $variability): float
    {
        if ($variability <= 0) {
            return 0.0;
        }

        $dayOfYear = (int) date('z', $timestamp);
        $minutes = (int) date('G', $timestamp) * 60 + (int) date('i', $timestamp);

        $dailyCycle = 0.5 + 0.5 * sin(($minutes / 1440) * 2 * M_PI + ($dayOfYear / 3));
        $weatherFront = 0.5 + 0.5 * sin(($dayOfYear / 365) * 2 * M_PI);
        $shortTerm = 0.5 + 0.5 * sin(($minutes / 60) * M_PI + $dayOfYear);

        $coverage = ($dailyCycle * 0.5) + ($weatherFront * 0.3) + ($shortTerm * 0.2);
        $coverage = min(1.0, max(0.0, $coverage));

        return $coverage * $variability;
    }

    private function calculateConsumption(int $timestamp, float $baseLoad, float $acPower, bool $isDaytime, float $progress): float
    {
        $variation = 60.0 * sin(($timestamp / 900) + 1.3);
        $dayBoost = $isDaytime ? ($acPower * 0.12) + ($baseLoad * 0.1) + (140.0 * sin(M_PI * $progress)) : 0.0;
        $eveningBoost = (!$isDaytime && $progress >= 1.0) ? $baseLoad * 0.25 : 0.0;
        $morningBoost = (!$isDaytime && $progress <= 0.0) ? $baseLoad * 0.15 : 0.0;

        $consumption = $baseLoad + $variation + $dayBoost + $eveningBoost + $morningBoost;
        $consumption += max(0.0, min($acPower * 0.35, $baseLoad * 0.6));

        return max(200.0, $consumption);
    }

    /**
     * @param array{sunrise:int,sunset:int} $sunTimes
     */
    private function calculateStateOfCharge(int $timestamp, array $sunTimes, float $energyFraction, float $cloudCoverage): float
    {
        if ($timestamp <= $sunTimes['sunrise']) {
            $hoursUntilSunrise = ($sunTimes['sunrise'] - $timestamp) / 3600;

            return max(20.0, 55.0 - $hoursUntilSunrise * 5.0);
        }

        if ($timestamp >= $sunTimes['sunset']) {
            $hoursAfterSunset = ($timestamp - $sunTimes['sunset']) / 3600;

            return max(12.0, 80.0 - $hoursAfterSunset * 7.0);
        }

        $charge = 25.0 + 75.0 * $energyFraction * (1 - 0.4 * $cloudCoverage);

        return min(97.0, max(25.0, $charge));
    }

    private function calculateBatteryPower(float $stateOfCharge, float $consumption, float $feedInPower, float $peakPower, bool $isDaytime): float
    {
        if ($isDaytime) {
            if ($stateOfCharge < 95.0 && $feedInPower > 30.0) {
                return min($feedInPower, $peakPower * 0.25);
            }

            return -min($consumption * 0.1, $peakPower * 0.05);
        }

        $discharge = min($consumption * 0.6, ($stateOfCharge / 100) * $peakPower * 0.2);

        return $discharge > 0 ? -$discharge : 0.0;
    }

    /**
     * @return array{0:float,1:float}
     */
    private function splitPvStrings(float $acPower): array
    {
        $primary = $acPower * 0.58;
        $secondary = $acPower - $primary;

        return [$primary, max(0.0, $secondary)];
    }
}
