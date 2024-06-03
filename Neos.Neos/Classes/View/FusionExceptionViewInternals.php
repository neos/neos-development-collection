<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\View;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;

/**
 * @deprecated really un-nice :D
 */
class FusionExceptionViewInternals implements ContentRepositoryServiceInterface
{
    public function __construct(
        private readonly InterDimensionalVariationGraph $interDimensionalVariationGraph,
    ) {
    }

    public function getArbitraryDimensionSpacePoint(): DimensionSpacePoint
    {
        $rootDimensionSpacePoints = $this->interDimensionalVariationGraph->getRootGeneralizations();
        $arbitraryRootDimensionSpacePoint = array_shift($rootDimensionSpacePoints);
        return $arbitraryRootDimensionSpacePoint;
    }
}
