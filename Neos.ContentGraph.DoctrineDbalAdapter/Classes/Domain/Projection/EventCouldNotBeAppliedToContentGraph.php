<?php

/*
 * This file is part of the Neos.ContentGraph.DoctrineDbalAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Exception;

use Neos\Flow\Annotations as Flow;

/**
 * The exception to be thrown if an event could not be applied to the content graph
 */
#[Flow\Proxy(false)]
final class EventCouldNotBeAppliedToContentGraph extends \DomainException
{
    public static function becauseTheSourceNodeIsMissing(string $eventClassName): self
    {
        return new self(
            'Event ' . $eventClassName . ' could not be applied: Source node not found.',
            1645315210
        );
    }

    public static function becauseTheSourceParentNodeIsMissing(string $eventClassName): self
    {
        return new self(
            'Event ' . $eventClassName . ' could not be applied: Source parent node not found.',
            1645315229
        );
    }

    public static function becauseTheTargetParentNodeIsMissing(string $eventClassName): self
    {
        return new self(
            'Event ' . $eventClassName . ' could not be applied: Target parent node not found.',
            1645315274
        );
    }

    public static function becauseTheIngoingSourceHierarchyRelationIsMissing(string $eventClassName): self
    {
        return new self(
            'Event ' . $eventClassName . ' could not be applied: Ingoing source hierarchy relation not found.',
            1645317567
        );
    }
}
