<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\NodeMigration\Command;

/**
 * Migration configuration for a specific direction.
 */
class MigrationConfiguration
{
    protected ?string $comments;

    protected ?string $warnings;

    /**
     * @var array<int,mixed>
     */
    protected ?array $migration;

    /**
     * @param array<string,mixed> $configuration
     */
    public function __construct(array $configuration = [])
    {
        $this->comments = $configuration['comments'] ?? null;
        $this->warnings = $configuration['warnings'] ?? null;
        $this->migration = $configuration['migration'] ?? null;
    }

    public function getComments(): ?string
    {
        return $this->comments;
    }

    public function hasComments(): bool
    {
        return ($this->comments !== null);
    }

    /**
     * @return array<int,mixed>
     */
    public function getMigration(): ?array
    {
        return $this->migration;
    }

    public function getWarnings(): ?string
    {
        return $this->warnings;
    }

    public function hasWarnings(): bool
    {
        return ($this->warnings !== null);
    }
}
