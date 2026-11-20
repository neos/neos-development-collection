<?php

/*
 * This file is part of the Neos.ContentRepository.Core package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\Common;

/**
 * Common (marker) interface for all **commands** that affect the target location of a node in a subgraph, e.g.
 * - where to create it
 * - where to vary it to
 * - where to move it to
 *
 * @internal used internally for resolution of interdimensional siblings
 */
interface AffectsLocationInSubgraph
{
    /** @codingStandardsIgnoreStart */
    public IntendedTargetLocationInSubgraph $targetLocationInSubgraph { get; }
    /** @codingStandardsIgnoreEnd */
}
