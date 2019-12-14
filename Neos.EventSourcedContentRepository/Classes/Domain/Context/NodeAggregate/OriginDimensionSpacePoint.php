<?php
namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;

/**
 * A node's origin dimension space point. Defines in which point in the dimension space the node originates.
 *
 * Example:
 * In a setup with dimension "language", a node that originates in English has English content,
 * but might be visible in other languages via fallback mechanisms.
 *
 * @Flow\Proxy(false)
 */
final class OriginDimensionSpacePoint extends DimensionSpacePoint
{
    public static function fromDimensionSpacePoint(DimensionSpacePoint $dimensionSpacePoint): self
    {
        return new static($dimensionSpacePoint->getCoordinates());
    }

    public function toDimensionSpacePoint(): DimensionSpacePoint
    {
        return new DimensionSpacePoint($this->getCoordinates());
    }
}
