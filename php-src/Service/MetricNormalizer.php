<?php

declare(strict_types=1);

namespace Cantao\SolaxBundle\Service;

class MetricNormalizer
{
    /**
     * @param array<string, string> $mapping
     * @param string[]              $ignoredFields
     */
    public function __construct(
        private array $mapping,
        private string $prefix,
        private array $ignoredFields = [],
        private readonly ?int $decimalPrecision = null
    ) {
        $this->ignoredFields = array_map(static fn (string $field): string => strtolower($field), $this->ignoredFields);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, float|int|bool>
     */
    public function normalise(array $payload): array
    {
        $metrics = [];

        foreach ($payload as $field => $value) {
            $fieldName = (string) $field;

            if ($value === null || $value === '') {
                continue;
            }

            if (in_array(strtolower($fieldName), $this->ignoredFields, true)) {
                continue;
            }

            if (is_bool($value) || is_int($value) || is_float($value)) {
                $normalised = $value;
            } elseif (is_string($value) && $this->isBooleanString($value)) {
                $normalised = $this->castBooleanString($value);
            } elseif (is_numeric($value)) {
                $normalised = $this->castNumericValue($value);
            } else {
                continue;
            }

            if (is_float($normalised) && $this->decimalPrecision !== null) {
                $normalised = $this->applyPrecision($normalised);
            }

            $metricKey = $this->mapping[$fieldName] ?? sprintf('%s.%s', $this->prefix, $fieldName);
            $metrics[$metricKey] = $normalised;
        }

        return $metrics;
    }

    private function isBooleanString(string $value): bool
    {
        $lowerValue = strtolower($value);

        return in_array($lowerValue, ['true', 'false', '1', '0'], true);
    }

    private function castBooleanString(string $value): bool
    {
        return in_array(strtolower($value), ['true', '1'], true);
    }

    /**
     * @param float|int|string $value
     */
    private function castNumericValue(float|int|string $value): float|int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_float($value)) {
            return $value;
        }

        $value = str_replace(',', '.', (string) $value);

        if (preg_match('/^-?\d+$/', $value) === 1) {
            return (int) $value;
        }

        return (float) $value;
    }

    private function applyPrecision(float $value): float|int
    {
        $rounded = round($value, max(0, $this->decimalPrecision));

        if ($this->decimalPrecision === 0) {
            return (int) $rounded;
        }

        return $rounded;
    }
}
