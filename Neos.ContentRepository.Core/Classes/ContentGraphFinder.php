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

namespace Neos\ContentRepository\Core;

use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ProjectionStateInterface;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamDoesNotExistYet;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * A finder for ContentGraphInterface bound to contentStream/Workspace
 *
 * Userland code should not use this directly. You should get a ContentGraph
 * via ContentRepository::getContentGraph()
 *
 * @api Not for userland code, only for read access during write operations and in services
 * @see ContentRepository::getContentGraph()
 */
final class ContentGraphFinder implements ProjectionStateInterface
{
    /**
     * @var array<string, ContentGraphInterface>
     */
    private array $contentGraphInstances = [];

    public function __construct(
        private readonly ContentGraphFactoryInterface $contentGraphFactory
    ) {
    }

    /**
     * The default way to get a content graph to operate on.
     * The currently assigned ContentStreamId for the given Workspace is resolved internally.
     *
     * @throws WorkspaceDoesNotExist if there is no workspace with the provided name
     * @throws ContentStreamDoesNotExistYet if the provided workspace does not resolve to an existing content stream
     * @api
     * @see ContentRepository::getContentGraph()
     */
    public function getByWorkspaceName(WorkspaceName $workspaceName): ContentGraphInterface
    {
        if (isset($this->contentGraphInstances[$workspaceName->value])) {
            return $this->contentGraphInstances[$workspaceName->value];
        }

        $this->contentGraphInstances[$workspaceName->value] = $this->contentGraphFactory->buildForWorkspace($workspaceName);
        return $this->contentGraphInstances[$workspaceName->value];
    }

   /**
     * To release all held instances, in case a workspace/content stream relation needs to be reset
     *
     * @internal Should only be needed after write operations (which should take care on their own)
     */
    public function forgetInstances(): void
    {
        $this->contentGraphInstances = [];
    }

    /**
     * For testing we allow getting an instance set by both parameters, effectively overriding the relationship at will
     *
     * @param WorkspaceName $workspaceName
     * @param ContentStreamId $contentStreamId
     * @internal Only for testing
     */
    public function getByWorkspaceNameAndContentStreamId(WorkspaceName $workspaceName, ContentStreamId $contentStreamId): ContentGraphInterface
    {
        if (isset($this->contentGraphInstances[$workspaceName->value]) && $this->contentGraphInstances[$workspaceName->value]->getContentStreamId() === $contentStreamId) {
            return $this->contentGraphInstances[$workspaceName->value];
        }

        return $this->contentGraphFactory->buildForWorkspaceAndContentStream($workspaceName, $contentStreamId);
    }

    /**
     * Stateful (dirty) override of the chosen ContentStreamId for a given workspace, it applies within the given closure.
     * Implementations must ensure that requesting the contentStreamId for this workspace will resolve to the given
     * override ContentStreamId and vice versa resolving the WorkspaceName from this ContentStreamId should result in the
     * given WorkspaceName within the closure.
     *
     * @internal Used in write operations applying commands to a contentstream that will have WorkspaceName in the future
     * but doesn't have one yet.
     */
    public function overrideContentStreamId(WorkspaceName $workspaceName, ContentStreamId $contentStreamId, \Closure $fn): void
    {
        $contentGraph = $this->contentGraphFactory->buildForWorkspaceAndContentStream($workspaceName, $contentStreamId);
        $replacedAdapter = $this->contentGraphInstances[$workspaceName->value] ?? null;
        $this->contentGraphInstances[$workspaceName->value] = $contentGraph;

        try {
            $fn();
        } finally {
            unset($this->contentGraphInstances[$workspaceName->value]);
            if ($replacedAdapter) {
                $this->contentGraphInstances[$workspaceName->value] = $replacedAdapter;
            }
        }
    }
}
