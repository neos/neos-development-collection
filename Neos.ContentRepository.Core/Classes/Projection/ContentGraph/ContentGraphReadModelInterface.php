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
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStream;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspace;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspaces;

/**
 * This low level interface gives access to the content graph and workspaces
 *
 * Generally this is not accessible for users of the CR, except for registering a catchup-hook on the content graph
 *
 * @api as dependency in catchup hooks and for creating a custom content repository graph projection implementation
 */
interface ContentGraphReadModelInterface extends ProjectionStateInterface
{
    /**
     * @throws WorkspaceDoesNotExist if the workspace does not exist
     */
    public function getContentGraph(WorkspaceName $workspaceName): ContentGraphInterface;

    public function findWorkspaceByName(WorkspaceName $workspaceName): ?Workspace;

    public function findWorkspaces(): Workspaces;

    /**
     * @internal only used for constraint checks and in testcases, the public API must only use workspaces {@see findWorkspaceByName}.
     */
    public function findContentStreamById(ContentStreamId $contentStreamId): ?ContentStream;

    /**
     * Provides the total number of projected nodes regardless of workspace or content stream.
     *
     * @internal only for consumption in testcases
     */
    public function countNodes(): int;
}
