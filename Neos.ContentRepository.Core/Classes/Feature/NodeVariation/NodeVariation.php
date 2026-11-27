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

namespace Neos\ContentRepository\Core\Feature\NodeVariation;

use Neos\ContentRepository\Core\DimensionSpace\Exception\DimensionSpacePointNotFound;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Feature\Common\ConstraintChecks;
use Neos\ContentRepository\Core\Feature\Common\NodeVariationInternals;
use Neos\ContentRepository\Core\Feature\Common\TargetLocationInSubgraph;
use Neos\ContentRepository\Core\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Core\Feature\NodeVariation\Command\CreateNodeVariant;
use Neos\ContentRepository\Core\Feature\NodeVariation\Exception\DimensionSpacePointIsAlreadyOccupied;
use Neos\ContentRepository\Core\Feature\RebaseableCommand;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindPrecedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSucceedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Exception\DimensionSpacePointIsNotYetOccupied;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateCurrentlyExists;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateIsTethered;

/**
 * @internal implementation detail of Command Handlers
 */
trait NodeVariation
{
    use NodeVariationInternals;
    use ConstraintChecks;

    /**
     * @throws NodeAggregateCurrentlyExists
     * @throws DimensionSpacePointNotFound
     * @throws DimensionSpacePointIsNotYetOccupied
     * @throws DimensionSpacePointIsAlreadyOccupied
     * @throws NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint
     */
    private function handleCreateNodeVariant(
        CreateNodeVariant $command,
    ): EventsToPublish {
        $contentGraph = $this->commandHandlingDependencies->getContentGraph($command->workspaceName);
        $expectedVersion = $this->getExpectedVersionOfContentStream($contentGraph->getContentStreamId());
        $nodeAggregate = $this->requireProjectedNodeAggregate(
            $contentGraph,
            $command->nodeAggregateId
        );
        // we do this check first, because it gives a more meaningful error message on what you need to do.
        // we cannot use sentences with "." because the UI will only print the 1st sentence :/
        $this->requireNodeAggregateToNotBeRoot($nodeAggregate, 'and Root Node Aggregates cannot be varied; If this error happens, you most likely need to run a node migration "UpdateRootNodeAggregateDimensions" to update the root node dimensions.');
        $this->requireDimensionSpacePointToExist($command->sourceOrigin->toDimensionSpacePoint());
        $this->requireDimensionSpacePointToExist($command->targetOrigin->toDimensionSpacePoint());
        if ($nodeAggregate->classification->isTethered()) {
            // there should be only one parent node aggregate as the given one is tethered, but anyways
            foreach ($contentGraph->findParentNodeAggregates($nodeAggregate->nodeAggregateId) as $parentNodeAggregate) {
                if (!$parentNodeAggregate->classification->isRoot()) {
                    throw new NodeAggregateIsTethered(
                        'Node aggregate "' . $nodeAggregate->nodeAggregateId->value
                            . '" is classified as tethered and cannot be varied since its parent "' . $parentNodeAggregate->nodeAggregateId->value . '" is not root.',
                        1742295155
                    );
                }
            }
        }
        $this->requireNodeAggregateToOccupyDimensionSpacePoint($nodeAggregate, $command->sourceOrigin);
        $this->requireNodeAggregateToNotOccupyDimensionSpacePoint($nodeAggregate, $command->targetOrigin);
        $parentNodeAggregate = $this->requireProjectedParentNodeAggregate(
            $contentGraph,
            $command->nodeAggregateId,
            $command->sourceOrigin
        );
        $this->requireNodeAggregateToCoverDimensionSpacePoint(
            $parentNodeAggregate,
            $command->targetOrigin->toDimensionSpacePoint()
        );
        $succeedingSiblingNodeAggregateId = $contentGraph->getSubgraph($command->sourceOrigin->toDimensionSpacePoint(), VisibilityConstraints::createEmpty())
            ->findSucceedingSiblingNodes($command->nodeAggregateId, FindSucceedingSiblingNodesFilter::create())->first()?->aggregateId;
        $targetLocation = TargetLocationInSubgraph::create(
            nodeAggregateId: $command->nodeAggregateId,
            dimensionSpacePoint: $command->sourceOrigin->toDimensionSpacePoint(),
            parentNodeAggregateId: $parentNodeAggregate->nodeAggregateId,
            succeedingSiblingNodeAggregateId: $succeedingSiblingNodeAggregateId,
            /**
             * By convention, we only try to resolve a succeeding sibling from the preceding siblings
             * only if a succeeding sibling exists.
             * This is a deliberate decision matching the tests and to be replaced by explicitly setting the siblings
             * once the command allows specific targets.
             */
            precedingSiblingNodeAggregateId: $succeedingSiblingNodeAggregateId ?
                $contentGraph->getSubgraph($command->sourceOrigin->toDimensionSpacePoint(), VisibilityConstraints::createEmpty())
                    ->findPrecedingSiblingNodes($command->nodeAggregateId, FindPrecedingSiblingNodesFilter::create())->first()?->aggregateId
                : null,
            contentGraph: $contentGraph,
        );

        $events = $this->createEventsForVariations(
            $targetLocation,
            $contentGraph,
            $command->sourceOrigin,
            $command->targetOrigin,
            $nodeAggregate,
            $parentNodeAggregate,
        );

        return EventsToPublish::createEventsForStreamAndExpectedVersion(
            ContentStreamEventStreamName::fromContentStreamId($contentGraph->getContentStreamId())->getEventStreamName(),
            RebaseableCommand::enrichWithCommand(
                $command,
                $events
            ),
            $expectedVersion
        );
    }
}
