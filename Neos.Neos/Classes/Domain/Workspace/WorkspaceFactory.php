<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Domain\Workspace;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Projection\Workspace\Workspace as ContentRepositoryWorkspace;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;

/**
 * Neos' factory for its own workspace instances
 *
 * @api
 */
#[Flow\Scope('singleton')]
final class WorkspaceFactory
{
    /**
     * @var array<string, Workspace>
     */
    private array $instances;

    public function __construct(
        private ContentRepositoryRegistry $contentRepositoryRegistry
    ) {
    }

    public function createFromContentRepositoryIdAndWorkspaceName(
        ContentRepositoryId $contentRepositoryId,
        WorkspaceName $workspaceName,
    ): Workspace {
        $index = $contentRepositoryId->value . '-' . $workspaceName->value;
        if (isset($this->instances[$index])) {
            return $this->instances[$index];
        }

        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $contentRepositoryWorkspace = $this->requireContentRepositoryWorkspace($contentRepository, $workspaceName);

        return $this->instances[$index] = new Workspace(
            $workspaceName,
            $contentRepositoryWorkspace->currentContentStreamId,
            $contentRepositoryWorkspace->status,
            $contentRepositoryWorkspace->baseWorkspaceName,
            $contentRepository
        );
    }

    private function requireContentRepositoryWorkspace(
        ContentRepository $contentRepository,
        WorkspaceName $workspaceName
    ): ContentRepositoryWorkspace {
        $workspace = $contentRepository->getWorkspaceFinder()->findOneByName($workspaceName);
        if (!$workspace instanceof ContentRepositoryWorkspace) {
            throw new \DomainException('Workspace "' . $workspaceName->value . '" is missing', 1710967842);
        }

        // @todo: access control goes here

        return $workspace;
    }
}
