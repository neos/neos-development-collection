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
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;

final class ParentsNodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint extends \DomainException
{
    public static function butWasSupposedTo(
        NodeAggregateIdentifier $childNodeAggregateIdentifier,
        DimensionSpacePoint $dimensionSpacePoint,
        ContentStreamIdentifier $contentStreamIdentifier
    ): self {
        return new self(
            'No parent node aggregate for ' . $childNodeAggregateIdentifier
            . ' does currently cover dimension space point ' . json_encode($dimensionSpacePoint)
            . ' in content stream ' . $contentStreamIdentifier,
            1659906376
        );
    }
}
