<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Context\Node;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\GraphProjector;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\ContentDimensionZookeeper;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\ContentRepository\Exception\NodeConstraintException;
use Neos\ContentRepository\Exception\NodeExistsException;
use Neos\ContentRepository\Exception\NodeTypeNotFoundException;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamEventStreamName;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\CreateNodeAggregateWithNode;
use Neos\EventSourcedContentRepository\Domain\Context\Node\Command\HideNode;
use Neos\EventSourcedContentRepository\Domain\Context\Node\Command\MoveNode;
use Neos\EventSourcedContentRepository\Domain\Context\Node\Command\RemoveNodeAggregate;
use Neos\EventSourcedContentRepository\Domain\Context\Node\Command\RemoveNodesFromAggregate;
use Neos\EventSourcedContentRepository\Domain\Context\Node\Command\SetNodeProperty;
use Neos\EventSourcedContentRepository\Domain\Context\Node\Command\SetNodeReferences;
use Neos\EventSourcedContentRepository\Domain\Context\Node\Command\ShowNode;
use Neos\EventSourcedContentRepository\Domain\Context\Node\Event\NodeAggregateWasRemoved;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeAggregateWithNodeWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\Node\Event\NodePropertyWasSet;
use Neos\EventSourcedContentRepository\Domain\Context\Node\Event\NodeReferencesWereSet;
use Neos\EventSourcedContentRepository\Domain\Context\Node\Event\NodesWereMoved;
use Neos\EventSourcedContentRepository\Domain\Context\Node\Event\NodesWereRemovedFromAggregate;
use Neos\EventSourcedContentRepository\Domain\Context\Node\Event\NodeWasHidden;
use Neos\EventSourcedContentRepository\Domain\Context\Node\Event\NodeWasShown;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\EventSourcedContentRepository\Domain\ValueObject\CommandResult;
use Neos\EventSourcedContentRepository\Domain\ValueObject\NodeMoveMapping;
use Neos\EventSourcedContentRepository\Domain\ValueObject\NodeMoveMappings;
use Neos\EventSourcedContentRepository\Domain\ValueObject\PropertyValue;
use Neos\EventSourcedContentRepository\Domain\ValueObject\PropertyValues;
use Neos\EventSourcedContentRepository\Exception;
use Neos\EventSourcedContentRepository\Exception\DimensionSpacePointNotFound;
use Neos\EventSourcedContentRepository\Exception\NodeNotFoundException;
use Neos\EventSourcedContentRepository\Service\Infrastructure\ReadSideMemoryCacheManager;
use Neos\EventSourcing\Event\Decorator\EventWithIdentifier;
use Neos\EventSourcing\Event\DomainEvents;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
final class NodeCommandHandler
{
    /**
     * @Flow\Inject
     * @var NodeEventPublisher
     */
    protected $nodeEventPublisher;

    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @Flow\Inject
     * @var InterDimensionalVariationGraph
     */
    protected $interDimensionalVariationGraph;

    /**
     * @Flow\Inject
     * @var ContentDimensionZookeeper
     */
    protected $contentDimensionZookeeper;

    /**
     * @Flow\Inject
     * @var \Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface
     */
    protected $contentGraph;

    /**
     * @Flow\Inject
     * @var GraphProjector
     */
    protected $graphProjector;

    /**
     * @Flow\Inject
     * @var ReadSideMemoryCacheManager
     */
    protected $readSideMemoryCacheManager;

    /**
     * Create events for adding a node aggregate with node, including all auto-created child node aggregates with nodes (recursively)
     *
     * @param CreateNodeAggregateWithNode $command
     * @param bool $checkParent
     * @return DomainEvents
     * @throws Exception
     * @throws NodeNotFoundException
     * @throws DimensionSpacePointNotFound
     */
    /*
    private function nodeAggregateWithNodeWasCreatedFromCommand(CreateNodeAggregateWithNode $command, bool $checkParent = true): array
    private function nodeAggregateWithNodeWasCreatedFromCommand(CreateNodeAggregateWithNode $command, bool $checkParent = true): DomainEvents
    {
        $nodeType = $this->getNodeType($command->getNodeTypeName());

        $propertyDefaultValuesAndTypes = [];
        foreach ($nodeType->getDefaultValuesForProperties() as $propertyName => $propertyValue) {
            $propertyDefaultValuesAndTypes[$propertyName] = new PropertyValue(
                $propertyValue,
                $nodeType->getPropertyType($propertyName)
            );
        }
        $defaultPropertyValues = new PropertyValues($propertyDefaultValuesAndTypes);
        $initialPropertyValues = $defaultPropertyValues->merge($command->getInitialPropertyValues());

        $events = DomainEvents::createEmpty();

        $dimensionSpacePoint = $command->getOriginDimensionSpacePoint();
        $contentStreamIdentifier = $command->getContentStreamIdentifier();
        $parentNodeIdentifier = $command->getParentNodeAggregateIdentifier();
        $nodeAggregateIdentifier = $command->getNodeAggregateIdentifier();

        if ($checkParent) {
            $contentSubgraph = $this->contentGraph->getSubgraphByIdentifier($contentStreamIdentifier,
                $dimensionSpacePoint, VisibilityConstraints::withoutRestrictions());
            if ($contentSubgraph === null) {
                throw new Exception(sprintf('Content subgraph not found for content stream %s, %s',
                    $contentStreamIdentifier, $dimensionSpacePoint), 1506440320);
            }
            $parentNode = $contentSubgraph->findNodeByIdentifier($parentNodeIdentifier);
            if ($parentNode === null) {
                throw new NodeNotFoundException(sprintf('Parent node %s not found for content stream %s, %s',
                    $parentNodeIdentifier, $contentStreamIdentifier, $dimensionSpacePoint),
                    1506440451);
            }
        }

        $visibleInDimensionSpacePoints = $this->getVisibleInDimensionSpacePoints($dimensionSpacePoint);

        $events = $events->appendEvent(EventWithIdentifier::create(
            new NodeAggregateWithNodeWasCreated(
                $contentStreamIdentifier,
                $nodeAggregateIdentifier,
                $command->getNodeTypeName(),
                $dimensionSpacePoint,
                $visibleInDimensionSpacePoints,
                $command->getNodeIdentifier(),
                $parentNodeIdentifier,
                $command->getNodeName(),
                PropertyValues::fromArray($propertyDefaultValuesAndTypes)
            )
        ));

        foreach ($nodeType->getAutoCreatedChildNodes() as $childNodeNameStr => $childNodeType) {
            $childNodeName = NodeName::fromString($childNodeNameStr);
            $childNodeAggregateIdentifier = NodeAggregateIdentifier::forAutoCreatedChildNode($childNodeName, $nodeAggregateIdentifier);
            $childNodeIdentifier = NodeIdentifier::create();
            $childParentNodeIdentifier = $command->getNodeIdentifier();

            $events = $events->appendEvents($this->nodeAggregateWithNodeWasCreatedFromCommand(new CreateNodeAggregateWithNode(
                $contentStreamIdentifier,
                $childNodeAggregateIdentifier,
                NodeTypeName::fromString($childNodeType->getName()),
                $dimensionSpacePoint,
                $childNodeIdentifier,
                $childParentNodeIdentifier,
                $childNodeName
            ), false)
            );
        }

        return $events;
    }*/

    /**
     * CreateRootNode
     *
     * @param CreateRootNode $command
     * @return CommandResult
     */
    public function handleCreateRootNode(CreateRootNode $command): CommandResult
    {
        $this->readSideMemoryCacheManager->disableCache();

        $events = null;
        $this->nodeEventPublisher->withCommand($command, function () use ($command, &$events) {
            $contentStreamIdentifier = $command->getContentStreamIdentifier();

            $dimensionSpacePointSet = $this->contentDimensionZookeeper->getAllowedDimensionSubspace();

            $events = DomainEvents::withSingleEvent(
                EventWithIdentifier::create(
                    new RootNodeWasCreated(
                        $contentStreamIdentifier,
                        $command->getNodeIdentifier(),
                        RootNodeIdentifiers::rootNodeAggregateIdentifier(),
                        $command->getNodeTypeName(),
                        $dimensionSpacePointSet,
                        $command->getInitiatingUserIdentifier()
                    )
                )
            );
            $this->nodeEventPublisher->publishMany(
                ContentStreamEventStreamName::fromContentStreamIdentifier($contentStreamIdentifier)->getEventStreamName(),
                $events
            );
        });
        return CommandResult::fromPublishedEvents($events);
    }

    /**
     * @param SetNodeProperty $command
     * @return CommandResult
     */
    public function handleSetNodeProperty(SetNodeProperty $command): CommandResult
    {
        $this->readSideMemoryCacheManager->disableCache();

        $events = null;
        $this->nodeEventPublisher->withCommand($command, function () use ($command, &$events) {
            $contentStreamIdentifier = $command->getContentStreamIdentifier();

            // Check if node exists
            // @todo: this must also work when creating a copy on write
            #$this->assertNodeWithOriginDimensionSpacePointExists($contentStreamIdentifier, $command->getNodeAggregateIdentifier(), $command->getOriginDimensionSpacePoint());

            $events = DomainEvents::withSingleEvent(
                EventWithIdentifier::create(
                    new NodePropertyWasSet(
                        $contentStreamIdentifier,
                        $command->getNodeAggregateIdentifier(),
                        $command->getOriginDimensionSpacePoint(),
                        $command->getPropertyName(),
                        $command->getValue()
                    )
                )
            );

            $this->nodeEventPublisher->publishMany(
                ContentStreamEventStreamName::fromContentStreamIdentifier($contentStreamIdentifier)->getEventStreamName(),
                $events
            );
        });
        return CommandResult::fromPublishedEvents($events);
    }

    /**
     * @param SetNodeReferences $command
     * @return CommandResult
     */
    public function handleSetNodeReferences(SetNodeReferences $command): CommandResult
    {
        $this->readSideMemoryCacheManager->disableCache();

        $events = null;
        $this->nodeEventPublisher->withCommand($command, function () use ($command, &$events) {
            $events = DomainEvents::withSingleEvent(
                EventWithIdentifier::create(
                    new NodeReferencesWereSet(
                        $command->getContentStreamIdentifier(),
                        $command->getSourceNodeAggregateIdentifier(),
                        $command->getSourceOriginDimensionSpacePoint(),
                        $command->getDestinationNodeAggregateIdentifiers(),
                        $command->getReferenceName()
                    )
                )
            );
            $this->nodeEventPublisher->publishMany(
                ContentStreamEventStreamName::fromContentStreamIdentifier($command->getContentStreamIdentifier())->getEventStreamName(),
                $events
            );
        });

        return CommandResult::fromPublishedEvents($events);
    }

    /**
     * @param HideNode $command
     * @return CommandResult
     */
    public function handleHideNode(HideNode $command): CommandResult
    {
        $this->readSideMemoryCacheManager->disableCache();

        $events = null;
        $this->nodeEventPublisher->withCommand($command, function () use ($command, &$events) {
            $contentStreamIdentifier = $command->getContentStreamIdentifier();

            // Soft constraint check: Check if node exists in *all* given DimensionSpacePoints
            foreach ($command->getAffectedDimensionSpacePoints() as $dimensionSpacePoint) {
                $this->assertNodeWithOriginDimensionSpacePointExists($contentStreamIdentifier, $command->getNodeAggregateIdentifier(), $dimensionSpacePoint);
            }


            $events = DomainEvents::withSingleEvent(
                EventWithIdentifier::create(
                    new NodeWasHidden(
                        $contentStreamIdentifier,
                        $command->getNodeAggregateIdentifier(),
                        $command->getAffectedDimensionSpacePoints()
                    )
                )
            );

            $this->nodeEventPublisher->publishMany(
                ContentStreamEventStreamName::fromContentStreamIdentifier($contentStreamIdentifier)->getEventStreamName(),
                $events
            );
        });
        return CommandResult::fromPublishedEvents($events);
    }

    /**
     * @param ShowNode $command
     * @return CommandResult
     */
    public function handleShowNode(ShowNode $command): CommandResult
    {
        $this->readSideMemoryCacheManager->disableCache();

        $events = null;
        $this->nodeEventPublisher->withCommand($command, function () use ($command, &$events) {
            $contentStreamIdentifier = $command->getContentStreamIdentifier();

            // Soft constraint check: Check if node exists in *all* given DimensionSpacePoints
            foreach ($command->getAffectedDimensionSpacePoints() as $dimensionSpacePoint) {
                $this->assertNodeWithOriginDimensionSpacePointExists($contentStreamIdentifier, $command->getNodeAggregateIdentifier(), $dimensionSpacePoint);
            }

            $events = DomainEvents::withSingleEvent(
                EventWithIdentifier::create(
                    new NodeWasShown(
                        $contentStreamIdentifier,
                        $command->getNodeAggregateIdentifier(),
                        $command->getAffectedDimensionSpacePoints()
                    )
                )
            );

            $this->nodeEventPublisher->publishMany(
                ContentStreamEventStreamName::fromContentStreamIdentifier($contentStreamIdentifier)->getEventStreamName(),
                $events
            );
        });
        return CommandResult::fromPublishedEvents($events);
    }

    /**
     * @param MoveNode $command
     * @return CommandResult
     */
    public function handleMoveNode(MoveNode $command): CommandResult
    {
        $this->readSideMemoryCacheManager->disableCache();

        $events = null;
        $this->nodeEventPublisher->withCommand($command, function () use ($command, &$events) {
            $contentSubgraph = $this->contentGraph->getSubgraphByIdentifier($command->getContentStreamIdentifier(), $command->getDimensionSpacePoint(), VisibilityConstraints::withoutRestrictions());
            if ($contentSubgraph === null) {
                throw new Exception(sprintf('Content subgraph not found for content stream %s, %s', $command->getContentStreamIdentifier(), $command->getDimensionSpacePoint()), 1506074858);
            }
            $node = $contentSubgraph->findNodeByNodeAggregateIdentifier($command->getNodeAggregateIdentifier());
            $nodeAggregate = $this->contentGraph->findNodeAggregateByIdentifier($command->getContentStreamIdentifier(), $command->getNodeAggregateIdentifier());
            if (!$nodeAggregate) {
                throw new NodeAggregateNotFound('Node aggregate "' . $command->getNodeAggregateIdentifier() . '" not found.', 1519822991);
            }

            if ($command->getNewParentNodeAggregateIdentifier()) {
                $newParentAggregate = $this->contentGraph->findNodeAggregateByIdentifier($command->getContentStreamIdentifier(), $command->getNewParentNodeAggregateIdentifier());
                if (!$newParentAggregate) {
                    throw new NodeAggregateNotFound('Parent node aggregate "' . $command->getNewParentNodeAggregateIdentifier() . '" not found.', 1519822625);
                }
                if ($contentSubgraph->findChildNodeByNodeAggregateIdentifierConnectedThroughEdgeName($command->getNewParentNodeAggregateIdentifier(), $node->getNodeName())) {
                    throw new NodeExistsException('Node with name "' . $node->getNodeName() . '" already exists in parent "' . $command->getNewParentNodeAggregateIdentifier() . '".', 1292503469);
                }
                $newParentsNodeType = $this->nodeTypeManager->getNodeType((string)$newParentAggregate->getNodeTypeName());
                $nodesNodeType = $this->nodeTypeManager->getNodeType((string)$nodeAggregate->getNodeTypeName());
                if (!$newParentsNodeType->allowsChildNodeType($nodesNodeType)) {
                    throw new NodeConstraintException('Cannot move node "' . $command->getNodeAggregateIdentifier() . '" into node "' . $command->getNewParentNodeAggregateIdentifier() . '"',
                        1404648100);
                }

                $oldParentAggregates = $this->contentGraph->findParentAggregates($command->getContentStreamIdentifier(), $command->getNodeAggregateIdentifier());
                foreach ($oldParentAggregates as $oldParentAggregate) {
                    $oldParentAggregatesNodeType = $this->nodeTypeManager->getNodeType((string)$oldParentAggregate->getNodeTypeName());
                    if (isset($oldParentAggregatesNodeType->getAutoCreatedChildNodes()[(string)$node->getNodeName()])) {
                        throw new NodeConstraintException('Cannot move auto-generated node "' . $command->getNodeAggregateIdentifier() . '" into new parent "' . $newParentAggregate->getNodeAggregateIdentifier() . '"', 1519920594);
                    }
                }
                foreach ($this->contentGraph->findParentAggregates($command->getContentStreamIdentifier(), $command->getNewParentNodeAggregateIdentifier()) as $grandParentAggregate) {
                    $grandParentsNodeType = $this->nodeTypeManager->getNodeType((string)$grandParentAggregate->getNodeTypeName());
                    if (isset($grandParentsNodeType->getAutoCreatedChildNodes()[(string)$newParentAggregate->getNodeName()]) && !$grandParentsNodeType->allowsGrandchildNodeType((string)$newParentAggregate->getNodeName(), $nodesNodeType)) {
                        throw new NodeConstraintException('Cannot move node "' . $command->getNodeAggregateIdentifier() . '" into grand parent node "' . $grandParentAggregate->getNodeAggregateIdentifier() . '"',
                            1519828263);
                    }
                }
            }
            if ($command->getNewSucceedingSiblingNodeAggregateIdentifier()) {
                if (!$this->contentGraph->findNodeAggregateByIdentifier($command->getContentStreamIdentifier(), $command->getNewSucceedingSiblingNodeAggregateIdentifier())) {
                    throw new NodeAggregateNotFound('Succeeding sibling node aggregate "' . $command->getNewParentNodeAggregateIdentifier() . '" not found.', 1519900842);
                }
            }

            $nodeMoveMappings = NodeMoveMappings::createEmpty();
            switch ($command->getRelationDistributionStrategy()->getStrategy()) {
                case RelationDistributionStrategy::STRATEGY_SCATTER:
                    $nodeMoveMappings = $nodeMoveMappings->merge($this->getMoveNodeMappings($node, $command));
                    break;
                case RelationDistributionStrategy::STRATEGY_GATHER_SPECIALIZATIONS:
                    $specializationSet = $this->interDimensionalVariationGraph->getSpecializationSet($command->getDimensionSpacePoint());
                    $nodesInSpecializationSet = $this->contentGraph->findNodesByNodeAggregateIdentifier(
                        $command->getContentStreamIdentifier(),
                        $command->getNodeAggregateIdentifier(),
                        $specializationSet
                    );

                    foreach ($nodesInSpecializationSet as $nodeInSpecializationSet) {
                        $nodeMoveMappings = $nodeMoveMappings->merge($this->getMoveNodeMappings($nodeInSpecializationSet, $command));
                    }
                    break;
                case RelationDistributionStrategy::STRATEGY_GATHER_ALL:
                default:
                    $nodesInSpecializationSet = $this->contentGraph->findNodesByNodeAggregateIdentifier(
                        $command->getContentStreamIdentifier(),
                        $command->getNodeAggregateIdentifier()
                    );

                    foreach ($nodesInSpecializationSet as $nodeInSpecializationSet) {
                        $nodeMoveMappings = $nodeMoveMappings->merge($this->getMoveNodeMappings($nodeInSpecializationSet, $command));
                    }
            }

            $events = DomainEvents::withSingleEvent(
                EventWithIdentifier::create(
                    new NodesWereMoved(
                        $command->getContentStreamIdentifier(),
                        $nodeMoveMappings
                    )
                )
            );

            $this->nodeEventPublisher->publishMany(
                ContentStreamEventStreamName::fromContentStreamIdentifier($command->getContentStreamIdentifier())->getEventStreamName(),
                $events
            );
        });
        return CommandResult::fromPublishedEvents($events);
    }

    /**
     * @param NodeInterface $node
     * @param MoveNode $command
     * @return NodeMoveMappings
     */
    private function getMoveNodeMappings(NodeInterface $node, MoveNode $command): NodeMoveMappings
    {
        $nodeMoveMappings = NodeMoveMappings::createEmpty();
        $visibleInDimensionSpacePoints = $this->contentGraph->findVisibleDimensionSpacePointsOfNode($node);
        foreach ($visibleInDimensionSpacePoints->getPoints() as $visibleDimensionSpacePoint) {
            $variantSubgraph = $this->contentGraph->getSubgraphByIdentifier($command->getContentStreamIdentifier(), $visibleDimensionSpacePoint, VisibilityConstraints::withoutRestrictions());

            $newParentVariant = $command->getNewParentNodeAggregateIdentifier() ? $variantSubgraph->findNodeByNodeAggregateIdentifier($command->getNewParentNodeAggregateIdentifier()) : null;
            $newSucceedingSiblingVariant = $command->getNewSucceedingSiblingNodeAggregateIdentifier() ? $variantSubgraph->findNodeByNodeAggregateIdentifier($command->getNewSucceedingSiblingNodeAggregateIdentifier()) : null;
            $mappingDimensionSpacePointSet = $newParentVariant ? $this->contentGraph->findVisibleDimensionSpacePointsOfNode($newParentVariant) : $visibleInDimensionSpacePoints;

            $nodeMoveMappings = $nodeMoveMappings->appendMapping(new NodeMoveMapping(
                $node->getNodeIdentifier(),
                $newParentVariant ? $newParentVariant->getNodeIdentifier() : null,
                $newSucceedingSiblingVariant ? $newSucceedingSiblingVariant->getNodeIdentifier() : null,
                $mappingDimensionSpacePointSet
            ));
        }

        /**
         * $reassignmentMappings = [];
         * foreach ($this->interDimensionalVariationGraph->getSpecializationSet($command->getTargetDimensionSpacePoint())->getPoints() as $specializedPoint)
         * {
         * $specializedSubgraph = $this->contentGraph->getSubgraphByIdentifier($command->getContentStreamIdentifier(), $specializedPoint);
         * $succeedingSpecializationSibling = $succeedingSourceSibling ? $specializedSubgraph->findNodeByNodeAggregateIdentifier($succeedingSourceSibling->getNodeAggregateIdentifier()) : null;
         * if (!$succeedingSpecializationSibling) {
         * $precedingSpecializationSibling = $precedingSourceSibling ? $specializedSubgraph->findNodeByNodeAggregateIdentifier($precedingSourceSibling->getNodeAggregateIdentifier()) : null;
         * if ($precedingSpecializationSibling) {
         * $succeedingSpecializationSibling = $specializedSubgraph->findSucceedingSibling($precedingSpecializationSibling->getNodeIdentifier());
         * }
         * }
         * $reassignmentMappings[] = new NodeReassignmentMapping(
         * $command->getSpecializationIdentifier(),
         * $sourceParentNode->getNodeIdentifier(),
         * $succeedingSpecializationSibling->getNodeIdentifier(),
         * $specializedPoint
         * );
         * }
         */

        return $nodeMoveMappings;
    }

    /**
     * @param RemoveNodeAggregate $command
     * @return CommandResult
     */
    public function handleRemoveNodeAggregate(RemoveNodeAggregate $command): CommandResult
    {
        $this->readSideMemoryCacheManager->disableCache();

        $events = null;
        $this->nodeEventPublisher->withCommand($command, function () use ($command, &$events) {
            $contentStreamIdentifier = $command->getContentStreamIdentifier();

            // Check if node aggregate exists
            $nodeAggregate = $this->contentGraph->findNodeAggregateByIdentifier($contentStreamIdentifier, $command->getNodeAggregateIdentifier());
            if ($nodeAggregate === null) {
                throw new NodeAggregateNotFound('Node aggregate ' . $command->getNodeAggregateIdentifier() . ' not found', 1532026858);
            }

            $events = DomainEvents::withSingleEvent(
                EventWithIdentifier::create(
                    new NodeAggregateWasRemoved(
                        $contentStreamIdentifier,
                        $command->getNodeAggregateIdentifier()
                    )
                )
            );

            $this->nodeEventPublisher->publishMany(
                ContentStreamEventStreamName::fromContentStreamIdentifier($contentStreamIdentifier)->getEventStreamName(),
                $events
            );
        });
        return CommandResult::fromPublishedEvents($events);
    }

    /**
     * @param RemoveNodesFromAggregate $command
     * @return CommandResult
     * @throws SpecializedDimensionsMustBePartOfDimensionSpacePointSet
     */
    public function handleRemoveNodesFromAggregate(RemoveNodesFromAggregate $command): CommandResult
    {
        $this->readSideMemoryCacheManager->disableCache();

        foreach ($command->getDimensionSpacePointSet()->getPoints() as $point) {
            $specializations = $this->interDimensionalVariationGraph->getSpecializationSet($point, false);
            foreach ($specializations->getPoints() as $specialization) {
                if (!$command->getDimensionSpacePointSet()->contains($specialization)) {
                    throw new SpecializedDimensionsMustBePartOfDimensionSpacePointSet('The parent dimension ' . json_encode($point->getCoordinates()) . ' is in the given DimensionSpacePointSet, but its specialization ' . json_encode($specialization->getCoordinates()) . ' is not. This is currently not supported; and we might need to think through the implications of this case more before allowing it. There is no "technical hard reason" to prevent it; but to me (SK) it feels that it will lead to inconsistent behavior otherwise.',
                        1532154238);
                }
            }
        }

        $events = null;
        $this->nodeEventPublisher->withCommand($command, function () use ($command, &$events) {
            $contentStreamIdentifier = $command->getContentStreamIdentifier();

            // Check if node aggregate exists
            $nodeAggregate = $this->contentGraph->findNodeAggregateByIdentifier($contentStreamIdentifier, $command->getNodeAggregateIdentifier());
            if ($nodeAggregate === null) {
                throw new NodeAggregateNotFound('Node aggregate ' . $command->getNodeAggregateIdentifier() . ' not found', 1532026858);
            }

            $events = DomainEvents::withSingleEvent(
                EventWithIdentifier::create(
                    new NodesWereRemovedFromAggregate(
                        $contentStreamIdentifier,
                        $command->getNodeAggregateIdentifier(),
                        $command->getDimensionSpacePointSet()
                    )
                )
            );

            $this->nodeEventPublisher->publishMany(
                ContentStreamEventStreamName::fromContentStreamIdentifier($contentStreamIdentifier)->getEventStreamName(),
                $events
            );
        });
        return CommandResult::fromPublishedEvents($events);
    }

    /**
     * @param NodeTypeName $nodeTypeName
     * @return NodeType
     * @throws NodeTypeNotFoundException
     */
    private function getNodeType(NodeTypeName $nodeTypeName): NodeType
    {
        $this->validateNodeTypeName($nodeTypeName);

        return $this->nodeTypeManager->getNodeType((string)$nodeTypeName);
    }

    /**
     * @param NodeTypeName $nodeTypeName
     */
    private function validateNodeTypeName(NodeTypeName $nodeTypeName): void
    {
        if (!$this->nodeTypeManager->hasNodeType((string)$nodeTypeName)) {
            throw new \InvalidArgumentException('TODO: Node type ' . $nodeTypeName . ' not found.');
        }
    }

    /**
     * @param $dimensionSpacePoint
     * @return DimensionSpacePointSet
     * @throws DimensionSpacePointNotFound
     */
    private function getVisibleInDimensionSpacePoints($dimensionSpacePoint): DimensionSpacePointSet
    {
        return $this->interDimensionalVariationGraph->getSpecializationSet($dimensionSpacePoint);
    }

    private function assertNodeWithOriginDimensionSpacePointExists(ContentStreamIdentifier $contentStreamIdentifier, NodeAggregateIdentifier $nodeAggregateIdentifier, DimensionSpacePoint $originDimensionSpacePoint): NodeInterface
    {
        $subgraph = $this->contentGraph->getSubgraphByIdentifier($contentStreamIdentifier, $originDimensionSpacePoint, VisibilityConstraints::withoutRestrictions());
        $node = $subgraph->findNodeByNodeAggregateIdentifier($nodeAggregateIdentifier);
        if ($node === null) {
            throw new NodeNotFoundException(sprintf('Node %s not found in dimension %s', $nodeAggregateIdentifier, $originDimensionSpacePoint), 1541070463);
        }

        if (!$node->getOriginDimensionSpacePoint()->equals($originDimensionSpacePoint)) {
            throw new Exception\NodeNotOriginatingInCorrectDimensionSpacePointException(sprintf('Node %s has origin dimension space point %s, but you requested OriginDimensionSpacePoint %s.', $nodeAggregateIdentifier,
                $node->getOriginDimensionSpacePoint(), $originDimensionSpacePoint), 1541070670);
        }

        return $node;
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @return DimensionSpacePointSet
     * @todo take parent node's visibility into account
     * @todo use node aggregate
     *
     * A node in an aggregate should be visible in all points that fulfill all of the following criteria
     * - any node of the parent node aggregate is visible there
     * - they are specializations of the node's original point
     * - they are not occupied by specializations of the node
     */
    private function calculateVisibilityForNewNodeInNodeAggregate(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        DimensionSpacePoint $dimensionSpacePoint
    ): DimensionSpacePointSet {
        $existingNodes = $this->contentGraph->findNodesByNodeAggregateIdentifier(
            $contentStreamIdentifier,
            $nodeAggregateIdentifier
        );
        $dimensionSpacePoints = [];
        foreach ($existingNodes as $node) {
            $dimensionSpacePoints[] = $node->getOriginDimensionSpacePoint();
        }
        $occupiedDimensionSpacePoints = new DimensionSpacePointSet($dimensionSpacePoints);

        return $this->interDimensionalVariationGraph->getSpecializationSet(
            $dimensionSpacePoint,
            true,
            $occupiedDimensionSpacePoints
        );
    }
}
