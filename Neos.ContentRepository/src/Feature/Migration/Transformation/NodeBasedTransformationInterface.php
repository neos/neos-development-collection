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

namespace Neos\ContentRepository\Feature\Migration\Transformation;

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\Projection\ContentGraph\NodeInterface;
use Neos\ContentRepository\Infrastructure\Projection\CommandResult;

/**
 * A node-specific transformation, like setting node properties.
 *
 * Settings given to a transformation will be passed to accordingly named setters.
 */
interface NodeBasedTransformationInterface
{
    public function execute(
        NodeInterface $node,
        DimensionSpacePointSet $coveredDimensionSpacePoints,
        ContentStreamIdentifier $contentStreamForWriting
    ): CommandResult;
}
