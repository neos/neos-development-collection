<?php

/*
 * This file is part of the Neos.ContentRepository.Core package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\CommandHandler;

use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphReadModelInterface;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamDoesNotExistYet;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspace;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspaces;
use Neos\EventStore\Model\Event\Version;

/**
 * Encapsulates the access to the read model which is used by the command handlers for soft constraints
 *
 * @internal
 */
final readonly class CommandHandlingDependencies
{
    public function __construct(
        private ContentGraphReadModelInterface $contentGraphReadModel
    ) {
    }

    public function getContentStreamVersion(ContentStreamId $contentStreamId): Version
    {
        $contentStream = $this->contentGraphReadModel->findContentStreamById($contentStreamId);
        if ($contentStream === null) {
            throw new \InvalidArgumentException(sprintf('Failed to find content stream with id "%s"', $contentStreamId->value), 1716902051);
        }
        return $contentStream->version;
    }

    public function contentStreamExists(ContentStreamId $contentStreamId): bool
    {
        return $this->contentGraphReadModel->findContentStreamById($contentStreamId) !== null;
    }

    /**
     * @throws ContentStreamDoesNotExistYet if there is no matching content stream
     */
    public function isContentStreamClosed(ContentStreamId $contentStreamId): bool
    {
        $contentStream = $this->contentGraphReadModel->findContentStreamById($contentStreamId);
        if ($contentStream === null) {
            throw new ContentStreamDoesNotExistYet(
                'Content stream "' . $contentStreamId->value . '" does not exist.',
                1521386692
            );
        }
        return $contentStream->isClosed;
    }

    public function findWorkspaceByName(WorkspaceName $workspaceName): ?Workspace
    {
        return $this->contentGraphReadModel->findWorkspaceByName($workspaceName);
    }

    /**
     * @throws WorkspaceDoesNotExist if the workspace does not exist
     */
    public function getContentGraph(WorkspaceName $workspaceName): ContentGraphInterface
    {
        return $this->contentGraphReadModel->getContentGraph($workspaceName);
    }

    public function findAllWorkspaces(): Workspaces
    {
        return $this->contentGraphReadModel->findWorkspaces();
    }
}
