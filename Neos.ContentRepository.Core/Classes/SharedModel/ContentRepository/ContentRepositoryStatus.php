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

namespace Neos\ContentRepository\Core\SharedModel\ContentRepository;

use Neos\ContentRepository\Core\Projection\ProjectionStatuses;
use Neos\EventStore\Model\EventStore\Status as EventStoreStatus;

/**
 * @api
 */
final readonly class ContentRepositoryStatus
{
    public function __construct(
        public EventStoreStatus $eventStoreStatus,
        public ProjectionStatuses $projectionStatuses,
    ) {
    }
}
