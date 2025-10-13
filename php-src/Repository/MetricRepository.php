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
