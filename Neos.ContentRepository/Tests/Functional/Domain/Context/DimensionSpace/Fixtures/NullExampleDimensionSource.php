<?php
namespace Neos\ContentRepository\Tests\Functional\Domain\Context\DimensionSpace\Fixtures;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Context\Dimension;
use Neos\Flow\Annotations as Flow;

/**
 * The dimension source fixture with no dimensions defined
 *
 * @Flow\Scope("singleton")
 */
class NullExampleDimensionSource implements Dimension\ContentDimensionSourceInterface
{
    /**
     * @var array|Dimension\ContentDimension[]
     */
    protected $dimensions = [];


    /**
     * @param Dimension\ContentDimensionIdentifier $dimensionIdentifier
     * @return Dimension\ContentDimension|null
     */
    public function getDimension(Dimension\ContentDimensionIdentifier $dimensionIdentifier): ?Dimension\ContentDimension
    {
        return null;
    }

    /**
     * @return array|Dimension\ContentDimension[]
     */
    public function getContentDimensionsOrderedByPriority(): array
    {
        return $this->dimensions;
    }
}
