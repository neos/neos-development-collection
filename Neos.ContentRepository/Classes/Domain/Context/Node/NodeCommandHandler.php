<?php
namespace Neos\ContentRepository\Domain\Context\Node;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Context\ContentStream\ContentStreamCommandHandler;
use Neos\ContentRepository\Domain\Context\DimensionSpace\DimensionSpacePointIsNoGeneralization;
use Neos\ContentRepository\Domain\Context\DimensionSpace\DimensionSpacePointIsNoSpecialization;
use Neos\ContentRepository\Domain\Context\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Domain\Context\Importing\Command\FinalizeImportingSession;
use Neos\ContentRepository\Domain\Context\Importing\Command\StartImportingSession;
use Neos\ContentRepository\Domain\Context\Importing\Event\ImportingSessionWasFinalized;
use Neos\ContentRepository\Domain\Context\Importing\Event\ImportingSessionWasStarted;
use Neos\ContentRepository\Domain\Context\Node\Command\AddNodeToAggregate;
use Neos\ContentRepository\Domain\Context\Node\Command\CreateNodeSpecialization;
use Neos\ContentRepository\Domain\Context\Node\Command\HideNode;
use Neos\ContentRepository\Domain\Context\Node\Command\ShowNode;
use Neos\ContentRepository\Domain\Context\Node\Command\SetNodeReferences;
use Neos\ContentRepository\Domain\Context\Node\Command\CreateNodeGeneralization;
use Neos\ContentRepository\Domain\Context\Node\Command\TranslateNodeInAggregate;
use Neos\ContentRepository\Domain\Context\Node\Command\CreateNodeAggregateWithNode;
use Neos\ContentRepository\Domain\Context\Node\Command\CreateRootNode;
use Neos\ContentRepository\Domain\Context\Importing\Command\ImportNode;
use Neos\ContentRepository\Domain\Context\Importing\Event\NodeWasImported;
use Neos\ContentRepository\Domain\Context\Node\Command\MoveNode;
use Neos\ContentRepository\Domain\Context\Node\Command\ChangeNodeName;
use Neos\ContentRepository\Domain\Context\Node\Command\SetNodeProperty;
use Neos\ContentRepository\Domain\Context\Node\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Domain\Context\Node\Event\NodeGeneralizationWasCreated;
use Neos\ContentRepository\Domain\Context\Node\Event\NodeMoveMapping;
use Neos\ContentRepository\Domain\Context\Node\Event\NodeNameWasChanged;
use Neos\ContentRepository\Domain\Context\Node\Event\NodePropertyWasSet;
use Neos\ContentRepository\Domain\Context\Node\Event\NodeReferencesWereSet;
use Neos\ContentRepository\Domain\Context\Node\Event\NodesWereMoved;
use Neos\ContentRepository\Domain\Context\Node\Event\NodeWasAddedToAggregate;
use Neos\ContentRepository\Domain\Context\Node\Event\NodeWasHidden;
use Neos\ContentRepository\Domain\Context\Node\Event\NodeInAggregateWasTranslated;
use Neos\ContentRepository\Domain\Context\Node\Event\NodeWasShown;
use Neos\ContentRepository\Domain\Context\Node\Event\NodeSpecializationWasCreated;
use Neos\ContentRepository\Domain\Context\Node\Event\RootNodeWasCreated;
use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ValueObject\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeName;
use Neos\ContentRepository\Domain\ValueObject\PropertyValue;
use Neos\ContentRepository\Exception;
use Neos\ContentRepository\Exception\DimensionSpacePointNotFound;
use Neos\ContentRepository\Exception\NodeConstraintException;
use Neos\ContentRepository\Exception\NodeExistsException;
use Neos\ContentRepository\Exception\NodeNotFoundException;
use Neos\EventSourcing\EventStore\ExpectedVersion;
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
     * @var \Neos\ContentRepository\Domain\Projection\Content\ContentGraphInterface
     */
    protected $contentGraph;

    /**
     * @param CreateNodeAggregateWithNode $command
     */
    public function handleCreateNodeAggregateWithNode(CreateNodeAggregateWithNode $command): void
    {
        $this->nodeEventPublisher->withCommand($command, function () use ($command) {
            $contentStreamStreamName = ContentStreamCommandHandler::getStreamNameForContentStream($command->getContentStreamIdentifier());

            $events = $this->nodeAggregateWithNodeWasCreatedFromCommand($command);

            /** @var NodeAggregateWithNodeWasCreated $event */
            foreach ($events as $event) {
                // TODO Use a node aggregate aggregate and let that one publish the events
                $this->nodeEventPublisher->publish($contentStreamStreamName . ':NodeAggregate:' . $event->getNodeAggregateIdentifier(), $event, ExpectedVersion::NO_STREAM);
            }
        });
    }

    /**
     * Create events for adding a node aggregate with node, including all auto-created child node aggregates with nodes (recursively)
     *
     * @param CreateNodeAggregateWithNode $command
     * @param bool $checkParent
     * @return array array of events
     * @throws Exception
     * @throws NodeNotFoundException
     */
    private function nodeAggregateWithNodeWasCreatedFromCommand(CreateNodeAggregateWithNode $command, bool $checkParent = true): array
    {
        $nodeType = $this->getNodeType($command->getNodeTypeName());

        $propertyDefaultValuesAndTypes = [];
        foreach ($nodeType->getDefaultValuesForProperties() as $propertyName => $propertyValue) {
            $propertyDefaultValuesAndTypes[$propertyName] = new PropertyValue($propertyValue,
                $nodeType->getPropertyType($propertyName));
        }

        $events = [];

        $dimensionSpacePoint = $command->getDimensionSpacePoint();
        $contentStreamIdentifier = $command->getContentStreamIdentifier();
        $parentNodeIdentifier = $command->getParentNodeIdentifier();
        $nodeAggregateIdentifier = $command->getNodeAggregateIdentifier();

        if ($checkParent) {
            $contentSubgraph = $this->contentGraph->getSubgraphByIdentifier($contentStreamIdentifier,
                $dimensionSpacePoint);
            if ($contentSubgraph === null) {
                throw new Exception(sprintf('Content subgraph not found for content stream %s, %s',
                    $contentStreamIdentifier, $dimensionSpacePoint), 1506440320);
            }
            $parentNode = $contentSubgraph->findNodeByIdentifier($parentNodeIdentifier);
            if ($parentNode === null) {
                throw new NodeNotFoundException(sprintf('Parent node %s not found for content stream %s, %s',
                    $parentNodeIdentifier, $contentStreamIdentifier, $dimensionSpacePoint),
                    1506440451, $parentNodeIdentifier);
            }
        }

        $visibleDimensionSpacePoints = $this->getVisibleDimensionSpacePoints($dimensionSpacePoint);

        $events[] = new NodeAggregateWithNodeWasCreated(
            $contentStreamIdentifier,
            $nodeAggregateIdentifier,
            $command->getNodeTypeName(),
            $dimensionSpacePoint,
            $visibleDimensionSpacePoints,
            $command->getNodeIdentifier(),
            $parentNodeIdentifier,
            $command->getNodeName(),
            $propertyDefaultValuesAndTypes
        );

        foreach ($nodeType->getAutoCreatedChildNodes() as $childNodeNameStr => $childNodeType) {
            $childNodeName = new NodeName($childNodeNameStr);
            $childNodeAggregateIdentifier = NodeAggregateIdentifier::forAutoCreatedChildNode($childNodeName,
                $nodeAggregateIdentifier);
            $childNodeIdentifier = new NodeIdentifier();
            $childParentNodeIdentifier = $command->getNodeIdentifier();

            $events = array_merge($events,
                $this->nodeAggregateWithNodeWasCreatedFromCommand(new CreateNodeAggregateWithNode(
                    $contentStreamIdentifier,
                    $childNodeAggregateIdentifier,
                    new NodeTypeName($childNodeType),
                    $dimensionSpacePoint,
                    $childNodeIdentifier,
                    $childParentNodeIdentifier,
                    $childNodeName
                ), false));
        }

        return $events;
    }

    /**
     * @param AddNodeToAggregate $command
     */
    public function handleAddNodeToAggregate(AddNodeToAggregate $command): void
    {
        $this->nodeEventPublisher->withCommand($command, function () use ($command) {
            $contentStreamStreamName = ContentStreamCommandHandler::getStreamNameForContentStream($command->getContentStreamIdentifier());

            $events = $this->nodeWasAddedToAggregateFromCommand($command);

            /** @var NodeAggregateWithNodeWasCreated $event */
            foreach ($events as $event) {
                // TODO Use a node aggregate aggregate and let that one publish the events
                $this->nodeEventPublisher->publish($contentStreamStreamName . ':NodeAggregate:' . $event->getNodeAggregateIdentifier(), $event);
            }
        });
    }

    /**
     * @param AddNodeToAggregate $command
     * @param bool $checkParent
     * @return array
     * @throws Exception
     * @throws NodeNotFoundException
     */
    private function nodeWasAddedToAggregateFromCommand(AddNodeToAggregate $command, bool $checkParent = true): array
    {
        $dimensionSpacePoint = $command->getDimensionSpacePoint();
        $contentStreamIdentifier = $command->getContentStreamIdentifier();
        $parentNodeIdentifier = $command->getParentNodeIdentifier();
        $nodeAggregateIdentifier = $command->getNodeAggregateIdentifier();
        $nodeIdentifier = $command->getNodeIdentifier();

        $nodeAggregate = $this->contentGraph->findNodeAggregateByIdentifier($contentStreamIdentifier, $nodeAggregateIdentifier);
        if ($nodeAggregate === null) {
            throw new Exception(sprintf('Node aggregate with identifier %s not found in %s',
                $nodeAggregateIdentifier, $contentStreamIdentifier), 1506587828);
        }

        $propertyDefaultValuesAndTypes = [];

        $nodeTypeName = $nodeAggregate->getNodeTypeName();
        $nodeType = $this->getNodeType($nodeTypeName);

        foreach ($nodeType->getDefaultValuesForProperties() as $propertyName => $propertyValue) {
            $propertyDefaultValuesAndTypes[$propertyName] = new PropertyValue($propertyValue,
                $nodeType->getPropertyType($propertyName));
        }

        $events = [];

        if ($checkParent) {
            $contentSubgraph = $this->contentGraph->getSubgraphByIdentifier($contentStreamIdentifier, $dimensionSpacePoint);
            if ($contentSubgraph === null) {
                throw new Exception(sprintf('Content subgraph not found for content stream %s, %s',
                    $contentStreamIdentifier, $dimensionSpacePoint), 1506440320);
            }
            $parentNode = $contentSubgraph->findNodeByIdentifier($parentNodeIdentifier);
            if ($parentNode === null) {
                throw new NodeNotFoundException(sprintf('Parent node %s not found for content stream %s, %s',
                    $parentNodeIdentifier, $contentStreamIdentifier, $dimensionSpacePoint),
                    1506440451, $parentNodeIdentifier);
            }
        }

        $visibleDimensionSpacePoints = $this->calculateVisibilityForNewNodeInNodeAggregate(
            $contentStreamIdentifier,
            $nodeAggregateIdentifier,
            $dimensionSpacePoint
        );

        $events[] = new NodeWasAddedToAggregate(
            $contentStreamIdentifier,
            $nodeAggregateIdentifier,
            $nodeTypeName,
            $dimensionSpacePoint,
            $visibleDimensionSpacePoints,
            $nodeIdentifier,
            $parentNodeIdentifier,
            $command->getNodeName(),
            $propertyDefaultValuesAndTypes
        );

        foreach ($nodeType->getAutoCreatedChildNodes() as $childNodeNameStr => $childNodeType) {
            $childNodeName = new NodeName($childNodeNameStr);
            // TODO Check if it is okay to "guess" the existing node aggregate identifier, should already be handled by a soft constraint check above
            $childNodeAggregateIdentifier = NodeAggregateIdentifier::forAutoCreatedChildNode($childNodeName, $nodeAggregateIdentifier);
            $childNodeIdentifier = new NodeIdentifier();
            $childParentNodeIdentifier = $nodeIdentifier;

            $events = array_merge($events,
                $this->nodeWasAddedToAggregateFromCommand(new AddNodeToAggregate(
                    $contentStreamIdentifier,
                    $childNodeAggregateIdentifier,
                    $dimensionSpacePoint,
                    $childNodeIdentifier,
                    $childParentNodeIdentifier,
                    $childNodeName
                ), false));
        }

        return $events;
    }

    /**
     * @param StartImportingSession $command
     */
    public function handleStartImportingSession(StartImportingSession $command): void
    {
        // TODO: wrap with $this->nodeEventPublisher->withCommand($command, function() use ($command) {
        $streamName = 'Neos.ContentRepository:Importing:' . $command->getImportingSessionIdentifier();
        $this->nodeEventPublisher->publish(
            $streamName,
            new ImportingSessionWasStarted($command->getImportingSessionIdentifier()),
            ExpectedVersion::NO_STREAM
        );
    }

    /**
     * @param ImportNode $command
     */
    public function handleImportNode(ImportNode $command): void
    {
        // TODO: wrap with $this->nodeEventPublisher->withCommand($command, function() use ($command) {
        $this->validateNodeTypeName($command->getNodeTypeName());

        $streamName = 'Neos.ContentRepository:Importing:' . $command->getImportingSessionIdentifier();
        $this->nodeEventPublisher->publish(
            $streamName,
            new NodeWasImported(
                $command->getImportingSessionIdentifier(),
                $command->getParentNodeIdentifier(),
                $command->getNodeIdentifier(),
                $command->getNodeName(),
                $command->getNodeTypeName(),
                $command->getDimensionSpacePoint(),
                $command->getPropertyValues()
            )
        );
    }

    /**
     * @param FinalizeImportingSession $command
     */
    public function handleFinalizeImportingSession(FinalizeImportingSession $command): void
    {
        // TODO: wrap with $this->nodeEventPublisher->withCommand($command, function() use ($command) {
        $streamName = 'Neos.ContentRepository:Importing:' . $command->getImportingSessionIdentifier();
        $this->nodeEventPublisher->publish(
            $streamName,
            new ImportingSessionWasFinalized($command->getImportingSessionIdentifier())
        );
    }

    /**
     * CreateRootNode
     *
     * @param CreateRootNode $command
     */
    public function handleCreateRootNode(CreateRootNode $command): void
    {
        $this->nodeEventPublisher->withCommand($command, function () use ($command) {
            $contentStreamIdentifier = $command->getContentStreamIdentifier();

            $event = new RootNodeWasCreated(
                $contentStreamIdentifier,
                $command->getNodeIdentifier(),
                $command->getNodeTypeName(),
                $command->getInitiatingUserIdentifier()
            );

            $this->nodeEventPublisher->publish(
                ContentStreamCommandHandler::getStreamNameForContentStream($contentStreamIdentifier),
                $event
            );
        });
    }

    /**
     * @param SetNodeProperty $command
     */
    public function handleSetNodeProperty(SetNodeProperty $command): void
    {
        $this->nodeEventPublisher->withCommand($command, function () use ($command) {
            $contentStreamIdentifier = $command->getContentStreamIdentifier();

            // Check if node exists
            $this->getNode($contentStreamIdentifier, $command->getNodeIdentifier());

            $event = new NodePropertyWasSet(
                $contentStreamIdentifier,
                $command->getNodeIdentifier(),
                $command->getPropertyName(),
                $command->getValue()
            );

            $this->nodeEventPublisher->publish(
                ContentStreamCommandHandler::getStreamNameForContentStream($contentStreamIdentifier),
                $event
            );
        });
    }

    /**
     * @param SetNodeReferences $command
     * @throws Exception\NodeException
     */
    public function handleSetNodeReferences(SetNodeReferences $command): void
    {
        $this->nodeEventPublisher->withCommand($command, function () use ($command) {
            $contentStreamIdentifier = $command->getContentStreamIdentifier();
            $nodeIdentifier = $command->getNodeIdentifier();

            $node = $this->getNode($contentStreamIdentifier, $nodeIdentifier);

            $dimensionSpacePointsSet = $this->calculateVisibilityForNewNodeInNodeAggregate(
                $contentStreamIdentifier,
                $node->getNodeAggregateIdentifier(),
                $node->getDimensionSpacePoint()
            );

            $events[] = new NodeReferencesWereSet(
                $contentStreamIdentifier,
                $dimensionSpacePointsSet,
                $command->getNodeIdentifier(),
                $command->getPropertyName(),
                $command->getDestinationtNodeAggregateIdentifiers()
            );

            $this->nodeEventPublisher->publishMany(
                ContentStreamCommandHandler::getStreamNameForContentStream($contentStreamIdentifier),
                $events
            );
        });
    }

    /**
     * @param HideNode $command
     */
    public function handleHideNode(HideNode $command): void
    {
        $this->nodeEventPublisher->withCommand($command, function () use ($command) {
            $contentStreamIdentifier = $command->getContentStreamIdentifier();

            // Check if node exists
            $this->getNode($contentStreamIdentifier, $command->getNodeIdentifier());

            $event = new NodeWasHidden(
                $contentStreamIdentifier,
                $command->getNodeIdentifier()
            );

            $this->nodeEventPublisher->publish(
                ContentStreamCommandHandler::getStreamNameForContentStream($contentStreamIdentifier),
                $event
            );
        });
    }

    /**
     * @param ShowNode $command
     */
    public function handleShowNode(ShowNode $command): void
    {
        $this->nodeEventPublisher->withCommand($command, function () use ($command) {
            $contentStreamIdentifier = $command->getContentStreamIdentifier();

            // Check if node exists
            $this->getNode($contentStreamIdentifier, $command->getNodeIdentifier());

            $event = new NodeWasShown(
                $contentStreamIdentifier,
                $command->getNodeIdentifier()
            );

            $this->nodeEventPublisher->publish(
                ContentStreamCommandHandler::getStreamNameForContentStream($contentStreamIdentifier),
                $event
            );
        });
    }

    /**
     * @param MoveNode $command
     */
    public function handleMoveNode(MoveNode $command): void
    {
        $this->nodeEventPublisher->withCommand($command, function () use ($command) {
            $contentSubgraph = $this->contentGraph->getSubgraphByIdentifier($command->getContentStreamIdentifier(), $command->getDimensionSpacePoint());
            if ($contentSubgraph === null) {
                throw new Exception(sprintf('Content subgraph not found for content stream %s, %s', $command->getContentStreamIdentifier(), $command->getDimensionSpacePoint()), 1506074858);
            }
            // this excludes root nodes as they do not have node aggregate identifiers
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
                    throw new NodeConstraintException('Cannot move node "' . $command->getNodeAggregateIdentifier() . '" into node "' . $command->getNewParentNodeAggregateIdentifier() . '"', 1404648100);
                }

                $oldParentAggregates = $this->contentGraph->findParentAggregates($command->getContentStreamIdentifier(), $command->getNodeAggregateIdentifier());
                foreach ($oldParentAggregates as $oldParentAggregate) {
                    $oldParentAggregatesNodeType = $this->nodeTypeManager->getNodeType((string)$oldParentAggregate->getNodeTypeName());
                    if (isset($oldParentAggregatesNodeType->getAutoCreatedChildNodes()[(string) $node->getNodeName()])) {
                        throw new NodeConstraintException('Cannot move auto-generated node "' . $command->getNodeAggregateIdentifier() . '" into new parent "' . $newParentAggregate->getNodeAggregateIdentifier() . '"', 1519920594);
                    }
                }
                foreach ($this->contentGraph->findParentAggregates($command->getContentStreamIdentifier(), $command->getNewParentNodeAggregateIdentifier()) as $grandParentAggregate) {
                    $grandParentsNodeType = $this->nodeTypeManager->getNodeType((string)$grandParentAggregate->getNodeTypeName());
                    if (isset($grandParentsNodeType->getAutoCreatedChildNodes()[(string)$newParentAggregate->getNodeName()]) && !$grandParentsNodeType->allowsGrandchildNodeType((string)$newParentAggregate->getNodeName(), $nodesNodeType)) {
                        throw new NodeConstraintException('Cannot move node "' . $command->getNodeAggregateIdentifier() . '" into grand parent node "' . $grandParentAggregate->getNodeAggregateIdentifier() . '"', 1519828263);
                    }
                }
            }
            if ($command->getNewSucceedingSiblingNodeAggregateIdentifier()) {
                if (!$this->contentGraph->findNodeAggregateByIdentifier($command->getContentStreamIdentifier(), $command->getNewSucceedingSiblingNodeAggregateIdentifier())) {
                    throw new NodeAggregateNotFound('Succeeding sibling node aggregate "' . $command->getNewParentNodeAggregateIdentifier() . '" not found.', 1519900842);
                }
            }

            $nodeMoveMappings = [];
            switch ($command->getRelationDistributionStrategy()->getStrategy()) {
                case RelationDistributionStrategy::STRATEGY_SCATTER:
                    $nodeMoveMappings = $this->getMoveNodeMappings($node, $command);
                    break;
                case RelationDistributionStrategy::STRATEGY_GATHER_SPECIALIZATIONS:
                    $specializationSet = $this->interDimensionalVariationGraph->getSpecializationSet($command->getDimensionSpacePoint());
                    $nodesInSpecializationSet = $this->contentGraph->findNodesByNodeAggregateIdentifier(
                        $command->getContentStreamIdentifier(),
                        $command->getNodeAggregateIdentifier(),
                        $specializationSet
                    );

                    foreach ($nodesInSpecializationSet as $nodeInSpecializationSet) {
                        $nodeMoveMappings = array_merge($nodeMoveMappings, $this->getMoveNodeMappings($nodeInSpecializationSet, $command));
                    }
                    break;
                case RelationDistributionStrategy::STRATEGY_GATHER_ALL:
                default:
                    $nodesInSpecializationSet = $this->contentGraph->findNodesByNodeAggregateIdentifier(
                        $command->getContentStreamIdentifier(),
                        $command->getNodeAggregateIdentifier()
                    );

                    foreach ($nodesInSpecializationSet as $nodeInSpecializationSet) {
                        $nodeMoveMappings = array_merge($nodeMoveMappings, $this->getMoveNodeMappings($nodeInSpecializationSet, $command));
                    }
            }

            $nodesWereMoved = new NodesWereMoved(
                $command->getContentStreamIdentifier(),
                $nodeMoveMappings
            );

            $this->nodeEventPublisher->publish(
                ContentStreamCommandHandler::getStreamNameForContentStream($command->getContentStreamIdentifier()),
                $nodesWereMoved
            );
        });
    }

    /**
     * @param ReadOnlyNodeInterface $node
     * @param MoveNode $command
     * @return array|NodeMoveMapping[]
     */
    protected function getMoveNodeMappings(ReadOnlyNodeInterface $node, MoveNode $command): array
    {
        $nodeMoveMappings = [];
        $visibleDimensionSpacePoints = $this->contentGraph->findVisibleDimensionSpacePointsOfNode($node);
        foreach ($visibleDimensionSpacePoints->getPoints() as $visibleDimensionSpacePoint) {
            $variantSubgraph = $this->contentGraph->getSubgraphByIdentifier($command->getContentStreamIdentifier(), $visibleDimensionSpacePoint);

            $newParentVariant = $command->getNewParentNodeAggregateIdentifier() ? $variantSubgraph->findNodeByNodeAggregateIdentifier($command->getNewParentNodeAggregateIdentifier()) : null;
            $newSucceedingSiblingVariant = $command->getNewSucceedingSiblingNodeAggregateIdentifier() ? $variantSubgraph->findNodeByNodeAggregateIdentifier($command->getNewSucceedingSiblingNodeAggregateIdentifier()) : null;
            $mappingDimensionSpacePointSet = $newParentVariant ? $this->contentGraph->findVisibleDimensionSpacePointsOfNode($newParentVariant) : $visibleDimensionSpacePoints;

            $nodeMoveMappings[] = new NodeMoveMapping(
                $node->getNodeIdentifier(),
                $newParentVariant ? $newParentVariant->getNodeIdentifier() : null,
                $newSucceedingSiblingVariant ? $newSucceedingSiblingVariant->getNodeIdentifier() : null,
                $mappingDimensionSpacePointSet
            );
        }

        /**
        $reassignmentMappings = [];
        foreach ($this->interDimensionalVariationGraph->getSpecializationSet($command->getTargetDimensionSpacePoint())->getPoints() as $specializedPoint)
        {
        $specializedSubgraph = $this->contentGraph->getSubgraphByIdentifier($command->getContentStreamIdentifier(), $specializedPoint);
        $succeedingSpecializationSibling = $succeedingSourceSibling ? $specializedSubgraph->findNodeByNodeAggregateIdentifier($succeedingSourceSibling->getNodeAggregateIdentifier()) : null;
        if (!$succeedingSpecializationSibling) {
        $precedingSpecializationSibling = $precedingSourceSibling ? $specializedSubgraph->findNodeByNodeAggregateIdentifier($precedingSourceSibling->getNodeAggregateIdentifier()) : null;
        if ($precedingSpecializationSibling) {
        $succeedingSpecializationSibling = $specializedSubgraph->findSucceedingSibling($precedingSpecializationSibling->getNodeIdentifier());
        }
        }
        $reassignmentMappings[] = new NodeReassignmentMapping(
        $command->getSpecializationIdentifier(),
        $sourceParentNode->getNodeIdentifier(),
        $succeedingSpecializationSibling->getNodeIdentifier(),
        $specializedPoint
        );
        }
         */

        return $nodeMoveMappings;
    }



    /**
     * @param ChangeNodeName $command
     * @throws Exception\NodeException
     */
    public function handleChangeNodeName(ChangeNodeName $command)
    {
        $this->nodeEventPublisher->withCommand($command, function () use ($command) {
            $contentStreamIdentifier = $command->getContentStreamIdentifier();
            /** @var Node $node */
            $node = $this->getNode($contentStreamIdentifier, $command->getNodeIdentifier());

            if ($node->getNodeType()->getName() === 'Neos.ContentRepository:Root') {
                throw new Exception\NodeException('The root node cannot be renamed.', 1346778388);
            }

            $event = new NodeNameWasChanged(
                $contentStreamIdentifier,
                $command->getNodeIdentifier(),
                $command->getNewNodeName()
            );

            $this->nodeEventPublisher->publish(
                ContentStreamCommandHandler::getStreamNameForContentStream($contentStreamIdentifier),
                $event
            );
        });
    }

    /**
     * @param CreateNodeSpecialization $command
     */
    public function handleCreateNodeSpecialization(CreateNodeSpecialization $command): void
    {
        $this->nodeEventPublisher->withCommand($command, function () use ($command) {
            $sourceNode = $this->contentGraph->findNodeByIdentifierInContentStream($command->getContentStreamIdentifier(), $command->getNodeIdentifier());
            if (!$this->interDimensionalVariationGraph->getSpecializationSet($sourceNode->getDimensionSpacePoint())->contains($command->getTargetDimensionSpacePoint())) {
                throw new DimensionSpacePointIsNoSpecialization($command->getTargetDimensionSpacePoint() . ' is no specialization of ' . $sourceNode->getDimensionSpacePoint(), 1519931770);
            }
            // @todo hard constraint
            $targetSubgraph = $this->contentGraph->getSubgraphByIdentifier($command->getContentStreamIdentifier(), $command->getTargetDimensionSpacePoint());
            $occupant = $targetSubgraph->findNodeByNodeAggregateIdentifier($sourceNode->getNodeAggregateIdentifier());
            if ($occupant->getDimensionSpacePoint()->equals($command->getTargetDimensionSpacePoint())) {
                throw new DimensionSpacePointIsAlreadyOccupiedInNodeAggregate($command->getTargetDimensionSpacePoint() . ' is already occupied by node ' . $occupant->getNodeIdentifier(), 1519986184);
            }
            // @todo filter specialization set with occupied DSPs
            $event = new NodeSpecializationWasCreated(
                $command->getContentStreamIdentifier(),
                $command->getNodeIdentifier(),
                $command->getSpecializationIdentifier(),
                $command->getTargetDimensionSpacePoint(),
                $this->interDimensionalVariationGraph->getSpecializationSet($command->getTargetDimensionSpacePoint())
            );

            $this->nodeEventPublisher->publish(
                ContentStreamCommandHandler::getStreamNameForContentStream($command->getContentStreamIdentifier()),
                $event
            );
        });
    }

    /**
     * @param CreateNodeGeneralization $command
     */
    public function handleCreateNodeGeneralization(CreateNodeGeneralization $command): void
    {
        $this->nodeEventPublisher->withCommand($command, function () use ($command) {
            $sourceNode = $this->contentGraph->findNodeByIdentifierInContentStream($command->getContentStreamIdentifier(), $command->getNodeIdentifier());
            if (!$this->interDimensionalVariationGraph->getSpecializationSet($command->getTargetDimensionSpacePoint())->contains($sourceNode->getDimensionSpacePoint())) {
                throw new DimensionSpacePointIsNoGeneralization($command->getTargetDimensionSpacePoint() . ' is no generalization of ' . $sourceNode->getDimensionSpacePoint(), 1519989009);
            }
            // @todo hard constraint
            $targetSubgraph = $this->contentGraph->getSubgraphByIdentifier($command->getContentStreamIdentifier(), $command->getTargetDimensionSpacePoint());
            $occupant = $targetSubgraph->findNodeByNodeAggregateIdentifier($sourceNode->getNodeAggregateIdentifier());
            if ($occupant && $occupant->getDimensionSpacePoint()->equals($command->getTargetDimensionSpacePoint())) {
                throw new DimensionSpacePointIsAlreadyOccupiedInNodeAggregate($command->getTargetDimensionSpacePoint() . ' is already occupied by node ' . $occupant->getNodeIdentifier(), 1519986184);
            }

            $event = new NodeGeneralizationWasCreated(
                $command->getContentStreamIdentifier(),
                $command->getNodeIdentifier(),
                $sourceNode->getDimensionSpacePoint(),
                $command->getGeneralizationIdentifier(),
                $command->getTargetDimensionSpacePoint(),
                $this->interDimensionalVariationGraph->getSpecializationSet(
                    $command->getTargetDimensionSpacePoint(),
                    true,
                    $this->contentGraph->findVisibleDimensionSpacePointsOfNodeAggregate($command->getContentStreamIdentifier(), $sourceNode->getNodeAggregateIdentifier())
                )
            );

            $this->nodeEventPublisher->publish(
                ContentStreamCommandHandler::getStreamNameForContentStream($command->getContentStreamIdentifier()),
                $event
            );
        });
    }

    /**
     * @param TranslateNodeInAggregate $command
     * @throws Exception\NodeException
     */
    public function handleTranslateNodeInAggregate(TranslateNodeInAggregate $command): void
    {
        $this->nodeEventPublisher->withCommand($command, function () use ($command) {
            $contentStreamIdentifier = $command->getContentStreamIdentifier();

            $events = $this->nodeInAggregateWasTranslatedFromCommand($command);
            $this->nodeEventPublisher->publishMany(
                ContentStreamCommandHandler::getStreamNameForContentStream($contentStreamIdentifier),
                $events
            );
        });
    }

    private function nodeInAggregateWasTranslatedFromCommand(TranslateNodeInAggregate $command): array
    {
        $sourceNodeIdentifier = $command->getSourceNodeIdentifier();
        $contentStreamIdentifier = $command->getContentStreamIdentifier();
        $dimensionSpacePoint = $command->getDimensionSpacePoint();
        $destinationNodeIdentifier = $command->getDestinationNodeIdentifier();

        $sourceNode = $this->getNode($contentStreamIdentifier, $sourceNodeIdentifier);

        // TODO Check that command->dimensionSpacePoint is not a generalization or specialization of sourceNode->dimensionSpacePoint!!! (translation)

        $sourceContentSubgraph = $this->contentGraph->getSubgraphByIdentifier($contentStreamIdentifier, $sourceNode->getDimensionSpacePoint());
        /** @var Node $sourceParentNode */
        $sourceParentNode = $sourceContentSubgraph->findParentNode($sourceNodeIdentifier);
        if ($sourceParentNode === null) {
            throw new Exception\NodeException(sprintf('Parent node for %s in %s not found',
                $sourceNodeIdentifier, $sourceNode->getDimensionSpacePoint()), 1506354274);
        }

        if ($command->getDestinationParentNodeIdentifier() !== null) {
            $destinationParentNodeIdentifier = $command->getDestinationParentNodeIdentifier();
        } else {
            $parentNodeAggregateIdentifier = $sourceParentNode->getNodeAggregateIdentifier();
            $destinationContentSubgraph = $this->contentGraph->getSubgraphByIdentifier($contentStreamIdentifier,
                $dimensionSpacePoint);
            /** @var Node $destinationParentNode */
            $destinationParentNode = $destinationContentSubgraph->findNodeByNodeAggregateIdentifier($parentNodeAggregateIdentifier);
            if ($destinationParentNode === null) {
                throw new Exception\NodeException(sprintf('Could not find suitable parent node for %s in %s',
                    $sourceNodeIdentifier, $destinationContentSubgraph->getDimensionSpacePoint()), 1506354275);
            }
            $destinationParentNodeIdentifier = $destinationParentNode->getNodeIdentifier();
        }

        $dimensionSpacePointSet = $this->getVisibleDimensionSpacePoints($dimensionSpacePoint);

        $events[] = new NodeInAggregateWasTranslated(
            $contentStreamIdentifier,
            $sourceNodeIdentifier,
            $destinationNodeIdentifier,
            $destinationParentNodeIdentifier,
            $dimensionSpacePoint,
            $dimensionSpacePointSet
        );

        // TODO Add a recursive flag and translate _all_ child nodes in this case
        foreach ($sourceNode->getNodeType()->getAutoCreatedChildNodes() as $childNodeNameStr => $childNodeType) {
            /** @var Node $childNode */
            $childNode = $sourceContentSubgraph->findChildNodeConnectedThroughEdgeName($sourceNodeIdentifier, new NodeName($childNodeNameStr));
            if ($childNode === null) {
                throw new Exception\NodeException(sprintf('Could not find auto-created child node with name %s for %s in %s',
                    $childNodeNameStr, $sourceNodeIdentifier, $sourceNode->getDimensionSpacePoint()), 1506506170);
            }

            $childDestinationNodeIdentifier = new NodeIdentifier();
            $childDestinationParentNodeIdentifier = $destinationNodeIdentifier;
            $events = array_merge($events,
                $this->nodeInAggregateWasTranslatedFromCommand(new TranslateNodeInAggregate(
                    $contentStreamIdentifier,
                    $childNode->getNodeIdentifier(),
                    $childDestinationNodeIdentifier,
                    $dimensionSpacePoint,
                    $childDestinationParentNodeIdentifier
                )));
        }

        return $events;
    }


    /**
     * @param NodeTypeName $nodeTypeName
     * @return NodeType
     */
    private function getNodeType(NodeTypeName $nodeTypeName): NodeType
    {
        $this->validateNodeTypeName($nodeTypeName);

        $nodeType = $this->nodeTypeManager->getNodeType((string)$nodeTypeName);

        return $nodeType;
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
    private function getVisibleDimensionSpacePoints($dimensionSpacePoint): DimensionSpacePointSet
    {
        return $this->interDimensionalVariationGraph->getSpecializationSet($dimensionSpacePoint);
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeIdentifier $nodeIdentifier
     * @return Node
     * @throws NodeNotFoundException
     */
    private function getNode(ContentStreamIdentifier $contentStreamIdentifier, NodeIdentifier $nodeIdentifier): NodeInterface
    {
        /** @var Node $node */
        $node = $this->contentGraph->findNodeByIdentifierInContentStream($contentStreamIdentifier, $nodeIdentifier);
        if ($node === null) {
            throw new NodeNotFoundException(sprintf('Node %s not found', $nodeIdentifier), 1506074496, $nodeIdentifier);
        }

        return $node;
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @return DimensionSpacePointSet
     * @todo take parent node's visibility into account
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
            $dimensionSpacePoints[] = $node->getDimensionSpacePoint();
        }
        $occupiedDimensionSpacePoints = new DimensionSpacePointSet($dimensionSpacePoints);

        return $this->interDimensionalVariationGraph->getSpecializationSet(
            $dimensionSpacePoint,
            true,
            $occupiedDimensionSpacePoints
        );
    }
}
