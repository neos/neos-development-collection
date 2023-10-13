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
    /**
     * This property exists mostly for testing, to make the command handler fully deterministic.
     *
     * A migration file is structured like this:
     * migrations: [
     *   {filters: ... transformations: ...},
     *   {filters: ... transformations: ...}
     * ]
     * For every "submigration" (i.e. every "line" from above), we fork a new content stream,
     * to make the migration roll-back-able.
     * In the first "submigration", we use the base content stream identifier (of $workspaceName) for *reading*, and
     * use the first content stream identifier of this list for writing.
     * In the second "submigration", we use the content stream of the *first* submigration for reading, and the next one
     * from this list for writing.
     *
     * This effectively makes all changes of the first submigration visible in the next submigration.
     *
     * @var ContentStreamId[]
     */
    private $contentStreamIdsForWriting;

    /**
     * ExecuteMigration constructor.
     * @param ContentStreamId[] $contentStreamIdsForWriting
     */
    public function __construct(
        private readonly MigrationConfiguration $migrationConfiguration,
        private readonly WorkspaceName $workspaceName,
        private readonly ?WorkspaceName $targetWorkspaceName,
        array $contentStreamIdsForWriting = []
    ) {
        $this->contentStreamIdsForWriting = array_values($contentStreamIdsForWriting);
    }

    public function getMigrationConfiguration(): MigrationConfiguration
    {
        return $this->migrationConfiguration;
    }

    public function getWorkspaceName(): WorkspaceName
    {
        return $this->workspaceName;
    }

    public function getTargetWorkspaceName(): ?WorkspaceName
    {
        return $this->targetWorkspaceName;
    }
}
