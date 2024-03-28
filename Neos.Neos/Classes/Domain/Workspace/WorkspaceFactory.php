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
final readonly class WorkspaceFactory
{
    public function __construct(
        private ContentRepositoryRegistry $contentRepositoryRegistry
    ) {
    }

    public function create(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName): Workspace
    {
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $contentRepositoryWorkspace = $this->requireContentRepositoryWorkspace($contentRepository, $workspaceName);

        return new Workspace(
            $contentRepositoryWorkspace->currentContentStreamId,
            $workspaceName,
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