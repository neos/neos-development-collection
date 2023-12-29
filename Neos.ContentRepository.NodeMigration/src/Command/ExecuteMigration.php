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

use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * Execute a Content Repository migration (which is defined in a YAML file)
 */
final class ExecuteMigration
{
    public function __construct(
        private readonly MigrationConfiguration $migrationConfiguration,
        private readonly WorkspaceName $sourceWorkspaceName,
        private readonly WorkspaceName $targetWorkspaceName,
        private readonly bool $publishOnSuccess,
    ) {
    }

    public function getMigrationConfiguration(): MigrationConfiguration
    {
        return $this->migrationConfiguration;
    }

    public function getSourceWorkspaceName(): WorkspaceName
    {
        return $this->sourceWorkspaceName;
    }

    public function getTargetWorkspaceName(): WorkspaceName
    {
        return $this->targetWorkspaceName;
    }

    public function getPublishOnSuccess(): bool
    {
        return $this->publishOnSuccess;
    }
}
