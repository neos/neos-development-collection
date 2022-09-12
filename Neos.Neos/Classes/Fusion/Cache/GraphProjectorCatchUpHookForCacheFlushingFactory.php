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

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Projection\CatchUpHookFactoryInterface;

class GraphProjectorCatchUpHookForCacheFlushingFactory implements CatchUpHookFactoryInterface
{
    public function __construct(
        private readonly ContentCacheFlusher $contentCacheFlusher
    ) {
    }

    public function build(ContentRepository $contentRepository): GraphProjectorCatchUpHookForCacheFlushing
    {
        return new GraphProjectorCatchUpHookForCacheFlushing(
            $contentRepository,
            $this->contentCacheFlusher
        );
    }
}
