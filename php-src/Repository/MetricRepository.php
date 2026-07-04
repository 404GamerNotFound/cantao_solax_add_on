<?php

declare(strict_types=1);

namespace Cantao\SolaxBundle\Repository;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

class MetricRepository
{
    public function __construct(
        private readonly Connection $connection,
        private string $tableName
    ) {
    }

    /**
     * @return array{metrics: array<string, float|int|bool|string>, timestamp: int|null}
     */
    public function fetchAllMetrics(): array
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder
            ->select('metric_key', 'metric_value', 'tstamp')
            ->from($this->tableName)
            ->orderBy('metric_key');

        $rows = $queryBuilder->fetchAllAssociative();

        $metrics = [];
        $timestamp = null;

        foreach ($rows as $row) {
            if (!isset($row['metric_key'], $row['metric_value'])) {
                continue;
            }

            $metrics[(string) $row['metric_key']] = $this->castStoredValue((string) $row['metric_value']);

            if (isset($row['tstamp'])) {
                $timestamp = max((int) $row['tstamp'], $timestamp ?? 0);
            }
        }

        return [
            'metrics' => $metrics,
            'timestamp' => $timestamp,
        ];
    }

    /**
     * @param array<string, float|int|bool> $metrics
     */
    public function storeMetrics(array $metrics): MetricStoreResult
    {
        if ($metrics === []) {
            return MetricStoreResult::empty();
        }

        $timestamp = time();
        $stringifiedValues = $this->stringifyMetrics($metrics);
        $existing = $this->fetchExistingValues(array_keys($stringifiedValues));

        return $this->connection->transactional(function (Connection $connection) use ($stringifiedValues, $existing, $timestamp): MetricStoreResult {
            $written = 0;

            foreach ($stringifiedValues as $key => $value) {
                if (isset($existing[$key]) && $existing[$key] === $value) {
                    continue;
                }

                if (isset($existing[$key])) {
                    $connection->update(
                        $this->tableName,
                        [
                            'tstamp' => $timestamp,
                            'metric_value' => $value,
                        ],
                        ['metric_key' => $key],
                        [
                            'tstamp' => ParameterType::INTEGER,
                            'metric_value' => ParameterType::STRING,
                            'metric_key' => ParameterType::STRING,
                        ]
                    );
                } else {
                    $connection->insert(
                        $this->tableName,
                        [
                            'tstamp' => $timestamp,
                            'metric_key' => $key,
                            'metric_value' => $value,
                        ],
                        [
                            ParameterType::INTEGER,
                            ParameterType::STRING,
                            ParameterType::STRING,
                        ]
                    );
                }

                ++$written;
            }

            return new MetricStoreResult($written, count($stringifiedValues) - $written);
        });
    }

    /**
     * @param array<string, float|int|bool> $metrics
     * @return array<string, string>
     */
    private function stringifyMetrics(array $metrics): array
    {
        $stringified = [];

        foreach ($metrics as $key => $value) {
            if (is_bool($value)) {
                $stringified[$key] = $value ? '1' : '0';
            } else {
                $stringified[$key] = (string) $value;
            }
        }

        return $stringified;
    }

    private function castStoredValue(string $value): float|int|bool|string
    {
        if ($value === '1' || $value === '0') {
            return $value === '1';
        }

        if (is_numeric($value)) {
            $value = str_replace(',', '.', $value);

            return preg_match('/^-?\d+$/', $value) === 1 ? (int) $value : (float) $value;
        }

        return $value;
    }

    /**
     * @param string[] $keys
     * @return array<string, string>
     */
    private function fetchExistingValues(array $keys): array
    {
        if ($keys === []) {
            return [];
        }

        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder
            ->select('metric_key', 'metric_value')
            ->from($this->tableName)
            ->where($queryBuilder->expr()->in('metric_key', ':keys'))
            ->setParameter('keys', $keys, ArrayParameterType::STRING);

        $rows = $queryBuilder->fetchAllAssociative();

        $existing = [];

        foreach ($rows as $row) {
            if (isset($row['metric_key'], $row['metric_value'])) {
                $existing[(string) $row['metric_key']] = (string) $row['metric_value'];
            }
        }

        return $existing;
    }
}
