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
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * Create implementations of ContentGraphs bound to a specific Workspace and/or ContentStream
 *
 * @internal This is just an implementation detail to delegate creating the specific implementations of a ContentGraph.
 */
interface ContentGraphFactoryInterface
{
    /**
     * @throws WorkspaceDoesNotExist if the workspace does not exist
     */
    public function buildForWorkspace(WorkspaceName $workspaceName): ContentGraphInterface;

    public function buildForWorkspaceAndContentStream(WorkspaceName $workspaceName, ContentStreamId $contentStreamId): ContentGraphInterface;
}
