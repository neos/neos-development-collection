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

use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;

/**
 * The exception to be thrown if a node aggregate does currently not occupy a given dimension space point
 * but is supposed to be
 *
 * @api because exception is thrown during invariant checks on command execution
 */
final class NodeAggregateDoesCurrentlyNotOccupyDimensionSpacePoint extends \DomainException
{
    public static function butWasSupposedTo(
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        OriginDimensionSpacePoint $occupiedDimensionSpacePoint
    ): self {
        return new self(
            'Node aggregate "' . $nodeAggregateIdentifier
                . '" does currently not occupy dimension space point ' . $occupiedDimensionSpacePoint,
            1554902613
        );
    }
}
