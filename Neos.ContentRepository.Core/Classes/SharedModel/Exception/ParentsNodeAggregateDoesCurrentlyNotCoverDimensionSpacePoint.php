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
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * The exception to be thrown if a node's parent node aggregate does currently not cover a given dimension space point
 * but is supposed to be
 *
 * @api because exception is thrown during invariant checks on command execution
 */
final class ParentsNodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint extends \DomainException
{
    public static function butWasSupposedTo(
        NodeAggregateId $childNodeAggregateIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        ContentStreamId $contentStreamIdentifier
    ): self {
        return new self(
            'No parent node aggregate for ' . $childNodeAggregateIdentifier
            . ' does currently cover dimension space point ' . json_encode($dimensionSpacePoint)
            . ' in content stream ' . $contentStreamIdentifier,
            1659906376
        );
    }
}
