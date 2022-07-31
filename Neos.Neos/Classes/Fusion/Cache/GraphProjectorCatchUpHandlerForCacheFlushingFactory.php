<?php

namespace Neos\Neos\Fusion\Cache;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Projection\CatchUpHandlerFactoryInterface;
use Neos\ContentRepository\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Projection\ProjectionStateInterface;


class GraphProjectorCatchUpHandlerForCacheFlushingFactory implements CatchUpHandlerFactoryInterface
{

    public function build(ProjectionStateInterface $contentGraph): GraphProjectorCatchUpHandlerForCacheFlushing
    {
        assert($contentGraph instanceof ContentGraphInterface);
        return new GraphProjectorCatchUpHandlerForCacheFlushing(
            $contentGraph
        );
    }
}
