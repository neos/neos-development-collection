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

namespace Neos\ContentRepository\Core\Projection\ContentGraph;

use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Event\RootNodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

final class InMemoryContentGraph
{
    /**
     * @param array<string,array<string,Node>> $parentsByDimensionSpacePointAndChild
     * @param array<string,array<string,Nodes>> $childrenByDimensionSpacePointAndParent
     */
    public function __construct(
        private readonly WorkspaceName $workspaceName,
        private readonly ContentStreamId $contentStreamId,
        private NodeAggregates $rootNodeAggregates,
        private array $parentsByDimensionSpacePointAndChild,
        private array $childrenByDimensionSpacePointAndParent,
    ) {
    }

    public function toMinimalConstitutingEvents(): Events
    {
        $events = [];
        foreach ($this->rootNodeAggregates as $rootNodeAggregate) {
            $events[] = new RootNodeAggregateWithNodeWasCreated(
                $this->workspaceName,
                $this->contentStreamId,
                $rootNodeAggregate->nodeAggregateId,
                $rootNodeAggregate->nodeTypeName,
                $rootNodeAggregate->coveredDimensionSpacePoints,
                NodeAggregateClassification::CLASSIFICATION_ROOT
            );
        }

        return Events::fromArray($events);
    }
}
