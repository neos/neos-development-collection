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
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * A finder for a ContentGraph bound to contentStream / workspaceName
 *
 * The API way of accessing a ContentGraph is via ContentRepository::getContentGraph()
 *
 * @internal User land code should not use this directly.
 * @see ContentRepository::getContentGraph()
 */
final class ContentGraphFinder implements ProjectionStateInterface
{
    public function __construct(
        private readonly ContentGraphFactoryInterface $contentGraphFactory
    ) {
    }

    /**
     * The default way to get a content graph to operate on.
     * The currently assigned ContentStreamId for the given Workspace is resolved internally.
     *
     * @throws WorkspaceDoesNotExist if the provided workspace does not resolve to an existing content stream
     * @see ContentRepository::getContentGraph()
     */
    public function getByWorkspaceName(WorkspaceName $workspaceName): ContentGraphInterface
    {
        return $this->contentGraphFactory->buildForWorkspace($workspaceName);
    }

    /**
     * For testing we allow getting an instance set by both parameters, effectively overriding the relationship at will
     *
     * @param WorkspaceName $workspaceName
     * @param ContentStreamId $contentStreamId
     * @internal Only for the write side during publishing {@see \Neos\ContentRepository\Core\CommandHandlingDependencies::overrideContentStreamId}
     */
    public function getByWorkspaceNameAndContentStreamId(WorkspaceName $workspaceName, ContentStreamId $contentStreamId): ContentGraphInterface
    {
        return $this->contentGraphFactory->buildForWorkspaceAndContentStream($workspaceName, $contentStreamId);
    }
}
