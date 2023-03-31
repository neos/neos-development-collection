<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Ordering;

/**
 * One of the node {@see Timestamps}
 *
 * @see OrderingField
 * @api This enum is used for the {@see ContentSubgraphInterface} ordering
 */
enum TimestampField: string
{
    case CREATED = 'CREATED';
    case ORIGINAL_CREATED = 'ORIGINAL_CREATED';
    case LAST_MODIFIED = 'LAST_MODIFIED';
    case ORIGINAL_LAST_MODIFIED = 'ORIGINAL_LAST_MODIFIED';
}
