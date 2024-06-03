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

use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * Execute a Content Repository migration (which is defined in a YAML file)
 */
final class ExecuteMigration
{
    public function __construct(
        public readonly MigrationConfiguration $migrationConfiguration,
        public readonly WorkspaceName $sourceWorkspaceName,
        public readonly WorkspaceName $targetWorkspaceName,
        public readonly bool $publishOnSuccess,
        public readonly ContentStreamId $contentStreamId,
    ) {
    }
}
