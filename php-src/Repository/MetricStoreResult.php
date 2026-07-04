<?php

declare(strict_types=1);

namespace Cantao\SolaxBundle\Repository;

final class MetricStoreResult
{
    public function __construct(
        private readonly int $written,
        private readonly int $unchanged
    ) {
    }

    public static function empty(): self
    {
        return new self(0, 0);
    }

    public function getWritten(): int
    {
        return $this->written;
    }

    public function getUnchanged(): int
    {
        return $this->unchanged;
    }

    public function getTotal(): int
    {
        return $this->written + $this->unchanged;
    }

    public function hasChanges(): bool
    {
        return $this->written > 0;
    }
}
