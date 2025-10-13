<?php

declare(strict_types=1);

namespace Cantao\SolaxBundle\Service;

class MetricNormalizer
{
    /**
     * @param array<string, string> $mapping
     */
    public function __construct(
        private array $mapping,
        private string $prefix
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, float|int|bool>
     */
    public function normalise(array $payload): array
    {
        $metrics = [];

        foreach ($payload as $field => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if (is_bool($value) || is_int($value) || is_float($value)) {
                $normalised = $value;
            } else {
                if (is_numeric($value)) {
                    $normalised = (float) $value;
                } else {
                    continue;
                }
            }

            $metricKey = $this->mapping[$field] ?? sprintf('%s.%s', $this->prefix, $field);
            $metrics[$metricKey] = $normalised;
        }

        return $metrics;
    }
}
