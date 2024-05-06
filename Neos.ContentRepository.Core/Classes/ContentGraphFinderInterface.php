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
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * A finder for ContentGraphInterface bound to a Workspace
 *
 * @see ContentGraphInterface
 * @internal
 */
interface ContentGraphFinderInterface extends ProjectionStateInterface
{
    /**
     * @throws WorkspaceDoesNotExist if there is no workspace with the provided name
     * @throws ContentStreamDoesNotExistYet if the provided workspace does not resolve to an existing content stream
     */
    public function fromWorkspaceName(WorkspaceName $workspaceName): ContentGraphInterface;
}
