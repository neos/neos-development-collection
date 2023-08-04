<?php

namespace Neos\ContentRepository\Core\SharedModel\Experimental;

/**
 * This class can change without notice, and is meant for running experiments by the Neos team.
 *
 * NOTE: you are ONLY allowed to call "fromArray" to create this class; everything else is purely
 * internal and can change at any time.
 *
 * @internal
 */
final class ContentRepositoryExperiments
{
    private function __construct(
        private readonly string $compactCommands
    ) {
    }

    /**
     * @param array<string,mixed> $in
     * @api
     */
    public static function fromArray(array $in): self
    {
        return new self(
            compactCommands: $in['compactCommands'] ?? ''
        );
    }

    /**
     * @internal
     */
    // phpcs:ignore
    public function compactCommands_compressSimple(): bool
    {
        return $this->compactCommands === 'compress-simple';
    }
}
