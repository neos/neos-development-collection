<?php

/*
 * This file is part of the Neos.ContentRepository.DimensionSpace package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

namespace Neos\ContentRepository\Tests\Unit\DimensionSpace\Fixtures;

use Neos\ContentRepository\Dimension;

/**
 * A dimension source fixture with no dimensions defined
 */
class NullExampleDimensionSource implements Dimension\ContentDimensionSourceInterface
{
    public function getDimension(Dimension\ContentDimensionIdentifier $dimensionIdentifier): ?Dimension\ContentDimension
    {
        return null;
    }

    /**
     * @return array<string,Dimension\ContentDimension>
     */
    public function getContentDimensionsOrderedByPriority(): array
    {
        return [];
    }
}
