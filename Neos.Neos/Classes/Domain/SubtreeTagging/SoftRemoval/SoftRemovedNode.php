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

/** @internal */
final readonly class SoftRemovedNode
{
    private function __construct(
        public NodeAggregateId $nodeAggregateId,
        public DimensionSpacePointSet $removedDimensionSpacePoints,
        public DimensionSpacePointSet $conflictingDimensionSpacePoints
    ) {
    }

    public static function create(
        NodeAggregateId $nodeAggregateId,
        DimensionSpacePointSet $removedDimensionSpacePoints,
    ): self {
        return new self($nodeAggregateId, $removedDimensionSpacePoints, DimensionSpacePointSet::fromArray([]));
    }

    public function withConflictingDimensionSpacePoints(DimensionSpacePointSet $conflictingDimensionSpacePoints): self
    {
        return new self($this->nodeAggregateId, $this->removedDimensionSpacePoints, $conflictingDimensionSpacePoints);
    }
}
