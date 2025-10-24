<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph\Projection;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;

/**
 * The active record for assigning parent nodes to child nodes in-memory
 *
 * @extends \SplObjectStorage<InMemoryReferenceRecords,DimensionSpacePoint>
 * @internal
 */
final class InMemoryReferenceHyperrelation extends \SplObjectStorage
{
}
