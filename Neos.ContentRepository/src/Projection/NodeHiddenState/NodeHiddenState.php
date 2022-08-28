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

namespace Neos\ContentRepository\Projection\NodeHiddenState;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;

/**
 * Node Hidden State Read Model.
 *
 * This model can be used to answer the question if a certain node has the "hidden" flag set or not.
 *
 * It can NOT answer the question whether a Node is hidden because some node above it has been hidden - for that,
 * use the Content Subgraph.
 *
 * @api
 */
class NodeHiddenState
{
    public function __construct(
        public bool $isHidden
    ) {
    }
}
