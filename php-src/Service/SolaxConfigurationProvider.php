<?php

declare(strict_types=1);

namespace Cantao\SolaxBundle\Service;

use Contao\Config;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;

class SolaxConfigurationProvider
{
    /**
     * @param array<string, mixed> $solaxDefaults
     * @param array<string, mixed> $fakeDefaults
     */
    public function __construct(
        private readonly ContaoFramework $framework,
        private array $solaxDefaults = [],
        private array $fakeDefaults = []
    ) {
    }

    public function isFakeModeEnabled(): bool
    {
        $value = $this->getConfigAdapter()->get('solax_fake_data_mode');

        return $this->normalizeBoolean($value, (bool) ($this->fakeDefaults['enabled'] ?? false));
    }

    public function hasCredentials(): bool
    {
        $config = $this->getSolaxConfig();

        return !empty($config['api_key']) && !empty($config['serial_number']);
    }

    /**
     * @return array<string, mixed>
     */
    public function getSolaxConfig(): array
    {
        $config = $this->solaxDefaults;
        $adapter = $this->getConfigAdapter();

        $mapping = [
            'base_url' => 'solax_base_url',
            'api_version' => 'solax_api_version',
            'api_key' => 'solax_api_key',
            'serial_number' => 'solax_serial_number',
            'site_id' => 'solax_site_id',
            'timeout' => 'solax_timeout',
            'retry_count' => 'solax_retry_count',
            'retry_delay' => 'solax_retry_delay',
        ];

        foreach ($mapping as $key => $settingKey) {
            $value = $adapter->get($settingKey);

            if ($value === null || $value === '') {
                continue;
            }

            if (in_array($key, ['timeout', 'retry_count', 'retry_delay'], true)) {
                $config[$key] = (int) $value;
            } else {
                $config[$key] = (string) $value;
            }
        }

        if (!isset($config['api_version']) || !in_array($config['api_version'], ['v1', 'v2'], true)) {
            $config['api_version'] = 'v1';
        }

        return $config;
    }

    /**
     * @return array<string, float>
     */
    public function getFakeDataSettings(): array
    {
        $settings = $this->fakeDefaults;
        $adapter = $this->getConfigAdapter();

        $mapping = [
            'latitude' => 'solax_fake_latitude',
            'longitude' => 'solax_fake_longitude',
            'peak_power' => 'solax_fake_peak_power',
            'base_total_yield' => 'solax_fake_base_total',
            'cloud_variability' => 'solax_fake_cloud_variability',
            'household_base_load' => 'solax_fake_household_load',
        ];

        foreach ($mapping as $key => $settingKey) {
            $value = $adapter->get($settingKey);

            if ($value === null || $value === '') {
                continue;
            }

            $settings[$key] = (float) $value;
        }

        $settings['latitude'] = $this->clamp((float) ($settings['latitude'] ?? 0.0), -90.0, 90.0);
        $settings['longitude'] = $this->clamp((float) ($settings['longitude'] ?? 0.0), -180.0, 180.0);
        $settings['peak_power'] = max(0.0, (float) ($settings['peak_power'] ?? 0.0));
        $settings['base_total_yield'] = max(0.0, (float) ($settings['base_total_yield'] ?? 0.0));
        $settings['cloud_variability'] = $this->clamp((float) ($settings['cloud_variability'] ?? 0.0), 0.0, 1.0);
        $settings['household_base_load'] = max(0.0, (float) ($settings['household_base_load'] ?? 0.0));

        return $settings;
    }

    private function normalizeBoolean(mixed $value, bool $default = false): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        $value = strtolower((string) $value);

        return in_array($value, ['1', 'true', 'on', 'yes'], true);
    }

    private function getConfigAdapter(): Adapter
    {
        $this->framework->initialize();

        return $this->framework->getAdapter(Config::class);
    }

    private function clamp(float $value, float $min, float $max): float
    {
        return min($max, max($min, $value));
    }
}
