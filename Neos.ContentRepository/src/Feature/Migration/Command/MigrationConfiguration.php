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

namespace Neos\ContentRepository\Feature\Migration\Command;

/**
 * Migration configuration for a specific direction.
 */
class MigrationConfiguration
{
    /**
     * @var string
     */
    protected $comments;

    /**
     * @var string
     */
    protected $warnings;

    /**
     * @var array<int,mixed>
     */
    protected $migration;

    /**
     * @param array<string,mixed> $configuration
     */
    public function __construct(array $configuration = [])
    {
        $this->comments = $configuration['comments'] ?? null;
        $this->warnings = $configuration['warnings'] ?? null;
        $this->migration = $configuration['migration'] ?? null;
    }

    /**
     * @return string
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * @return boolean
     */
    public function hasComments()
    {
        return ($this->comments !== null);
    }

    /**
     * @return array<int,mixed>
     */
    public function getMigration(): array
    {
        return $this->migration;
    }

    /**
     * @return string
     */
    public function getWarnings()
    {
        return $this->warnings;
    }

    /**
     * @return boolean
     */
    public function hasWarnings()
    {
        return ($this->warnings !== null);
    }
}
