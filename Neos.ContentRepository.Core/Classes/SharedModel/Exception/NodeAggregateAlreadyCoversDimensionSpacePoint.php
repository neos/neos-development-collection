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

namespace Neos\ContentRepository\Core\SharedModel\Exception;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\Flow\Annotations as Flow;

/**
 * The exception to be thrown if a node aggregate does currently cover a given dimension space point
 */
#[Flow\Proxy(false)]
final class NodeAggregateAlreadyCoversDimensionSpacePoint extends \DomainException
{
    public static function butWasNotSupposedTo(
        NodeAggregateId $nodeAggregateIdentifier,
        DimensionSpacePoint $coveredDimensionSpacePoint
    ): self {
        return new self(
            'Node aggregate "' . $nodeAggregateIdentifier
                . '" already covers dimension space point ' . $coveredDimensionSpacePoint,
            1659614250
        );
    }
}
