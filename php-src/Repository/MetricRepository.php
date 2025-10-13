<?php

declare(strict_types=1);

namespace Cantao\SolaxBundle\Repository;

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
    public function storeMetrics(array $metrics): void
    {
        $timestamp = time();

        foreach ($metrics as $key => $value) {
            $this->connection->delete($this->tableName, ['metric_key' => $key]);

            $this->connection->insert(
                $this->tableName,
                [
                    'tstamp' => $timestamp,
                    'metric_key' => $key,
                    'metric_value' => (string) $value,
                ],
                [
                    ParameterType::INTEGER,
                    ParameterType::STRING,
                    ParameterType::STRING,
                ]
            );
        }
    }
}
