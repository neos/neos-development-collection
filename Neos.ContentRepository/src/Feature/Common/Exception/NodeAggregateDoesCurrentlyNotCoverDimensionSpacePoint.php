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

use Neos\ContentRepository\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;

/**
 * The exception to be thrown if a node aggregate does currently not cover a given dimension space point
 * but is supposed to be
 *
 * @api because exception is thrown during invariant checks on command execution
 */
final class NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint extends \DomainException
{
    public static function butWasSupposedTo(
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        DimensionSpacePoint $coveredDimensionSpacePoint
    ): self {
        return new self(
            'Node aggregate "' . $nodeAggregateIdentifier
                . '" does currently not cover dimension space point ' . $coveredDimensionSpacePoint,
            1554902892
        );
    }
}
