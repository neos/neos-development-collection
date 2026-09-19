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

namespace Neos\Neos\Domain\SubtreeTagging\SoftRemoval;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;

/** @internal only to be used for hard removal conflict handling */
final readonly class ImpendingHardRemovalConflict
{
    private function __construct(
        public NodeAggregateId $nodeAggregateId,
        public DimensionSpacePointSet $dimensionSpacePointSet
    ) {
    }

    public static function create(
        NodeAggregateId $nodeAggregateId,
        DimensionSpacePointSet $dimensionSpacePointSet
    ): self {
        return new self($nodeAggregateId, $dimensionSpacePointSet);
    }
}
