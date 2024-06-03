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

namespace Neos\ContentRepository\NodeMigration\Transformation;

use Neos\ContentRepository\Core\CommandHandler\CommandResult;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * A node-specific transformation, like setting node properties.
 *
 * Settings given to a transformation will be passed to accordingly named setters.
 */
interface NodeBasedTransformationInterface
{
    public function execute(
        Node $node,
        DimensionSpacePointSet $coveredDimensionSpacePoints,
        WorkspaceName $workspaceNameForWriting,
        ContentStreamId $contentStreamForWriting
    ): ?CommandResult;
}
