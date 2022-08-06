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

namespace Neos\ContentRepository\Feature\Common\Exception;

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\Flow\Annotations as Flow;

/**
 * The exception to be thrown if a node aggregate does currently cover a given dimension space point
 */
#[Flow\Proxy(false)]
final class NodeAggregateAlreadyCoversDimensionSpacePoint extends \DomainException
{
    public static function butWasNotSupposedTo(
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        DimensionSpacePoint $coveredDimensionSpacePoint
    ): self {
        return new self(
            'Node aggregate "' . $nodeAggregateIdentifier
                . '" already covers dimension space point ' . $coveredDimensionSpacePoint,
            1659614250
        );
    }
}
