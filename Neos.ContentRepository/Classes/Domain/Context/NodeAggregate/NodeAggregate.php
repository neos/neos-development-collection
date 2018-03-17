<?php
namespace Neos\ContentRepository\Domain\Context\NodeAggregate;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Context\Node\Command\CreateNodeSpecialization;
use Neos\ContentRepository\Domain\Context\Node\NodeEventPublisher;
use Neos\ContentRepository\Domain\ValueObject\DimensionSpacePoint;
use Neos\EventSourcing\EventStore\EventStore;

/**
 * The node aggregate
 *
 * Aggregates all nodes with a shared external identity that are varied across the Dimension Space.
 * An example would be a product node that is translated into different languages but uses a shared identifier,
 * e.g. MPN or GTIN
 *
 * The aggregate enforces that each dimension space point can only ever be occupied by one of its nodes.
 */
final class NodeAggregate
{
    /**
     * @var NodeAggregateIdentifier
     */
    protected $identifier;

    /**
     * @var EventStore
     */
    protected $eventStore;

    /**
     * @var string
     */
    protected $streamName;

    /**
     * @var NodeEventPublisher
     */
    protected $nodeEventPublisher;


    public function __construct(NodeAggregateIdentifier $identifier, EventStore $eventStore, string $streamName, NodeEventPublisher $nodeEventPublisher)
    {
        $this->identifier = $identifier;
        $this->eventStore = $eventStore;
        $this->streamName = $streamName;
        $this->nodeEventPublisher = $nodeEventPublisher;
    }


    public function handleCreateNodeSpecialization(CreateNodeSpecialization $command)
    {
        $this->nodeEventPublisher->withCommand($command, function () use ($command) {
            $this->requireDimensionSpacePointToBeOccupied($command->getSourceDimensionSpacePoint());
            $this->requireDimensionSpacePointToBeUnoccupied($command->getTargetDimensionSpacePoint());
        });
    }


    protected function requireDimensionSpacePointToBeOccupied(DimensionSpacePoint $dimensionSpacePoint)
    {

    }

    protected function requireDimensionSpacePointToBeUnoccupied(DimensionSpacePoint $dimensionSpacePoint)
    {

    }
}
