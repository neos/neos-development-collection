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

namespace Neos\ContentRepository\Core\Projection\ContentGraph;

use Neos\ContentRepository\Core\Projection\ProjectionStateInterface;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStream;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreams;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspace;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspaces;

/**
 * @api for creating a custom content repository graph projection implementation, **not for users of the CR**
 */
interface ContentGraphReadModelInterface extends ProjectionStateInterface
{
    public function buildContentGraph(WorkspaceName $workspaceName, ContentStreamId $contentStreamId): ContentGraphInterface;

    public function findWorkspaceByName(WorkspaceName $workspaceName): ?Workspace;

    public function findWorkspaces(): Workspaces;

    public function findContentStreamById(ContentStreamId $contentStreamId): ?ContentStream;

    public function findContentStreams(): ContentStreams;

    /**
     * Provides the total number of projected nodes regardless of workspace or content stream.
     *
     * @internal only for consumption in testcases
     */
    public function countNodes(): int;
}
