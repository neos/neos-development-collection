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
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStream;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreams;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspace;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspaces;

/**
 * Create implementations of ContentGraphs bound to a specific Workspace and/or ContentStream
 *
 * @internal This is just an implementation detail to delegate creating the specific implementations of a ContentGraph.
 */
interface ContentRepositoryReadModelAdapterInterface
{
    public function buildContentGraph(WorkspaceName $workspaceName, ContentStreamId $contentStreamId): ContentGraphInterface;

    public function findWorkspaceByName(WorkspaceName $workspaceName): ?Workspace;

    public function findWorkspaces(): Workspaces;

    public function findContentStreamById(ContentStreamId $contentStreamId): ?ContentStream;

    public function findContentStreams(): ContentStreams;

    /**
     * @return iterable<ContentStreamId>
     * @internal This is currently only used by the {@see ContentStreamPruner} and might be removed in the future!
     */
    public function findUnusedAndRemovedContentStreamIds(): iterable;
}
