<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Ordering;

/**
 * Sort order of a given {@see OrderingField}
 *
 * @api This enum is used for the {@see ContentSubgraphInterface} ordering
 */
enum OrderingDirection: string
{
    case ASCENDING = 'ASCENDING';
    case DESCENDING = 'DESCENDING';
}
