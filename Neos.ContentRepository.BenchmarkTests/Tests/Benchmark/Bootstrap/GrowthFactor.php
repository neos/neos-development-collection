<?php

declare(strict_types=1);

/**
 * The expected growth factor when writing from or reading to the CR:
 * * linear for writing
 * * logarithmic with base 2 for reading (we expect databases to use binary trees)
 * with a grace factor of 1.2 to compensate execution time fluctuations
 */
final readonly class GrowthFactor
{
    private const GRACE_FACTOR = 1.2;

    private function __construct(
        /** Raw factor */
        private int $value,
    ) {
    }

    public static function tryFrom(int $value): ?self
    {
        if ($value === 0) {
            return null;
        }

        return new self($value);
    }

    public function getLinear(): float
    {
        return self::GRACE_FACTOR * $this->value;
    }

    /**
     * @param int $depthDifference The difference in depth for two query results, to take CTE query time into account
     */
    public function getLogarithmic(int $depthDifference = 0): float
    {
        return ($depthDifference + 1) * log(self::GRACE_FACTOR * $this->value, 2);
    }
}
