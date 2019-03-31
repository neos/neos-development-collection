<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\Exception\DimensionSpacePointIsNoGeneralization;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\ContentRepository\Domain\ValueObject\NodePath;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeName;
use Neos\ContentRepository\Exception\NodeConstraintException;
use Neos\ContentRepository\Exception\NodeTypeNotFoundException;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream;
use Neos\ContentRepository\DimensionSpace\DimensionSpace;
use Neos\EventSourcedContentRepository\Domain\Context\Node\NodeAggregatesTypeIsAmbiguous;
use Neos\EventSourcedContentRepository\Domain\Context\Node\NodeEventPublisher;
use Neos\EventSourcedContentRepository\Domain\Context\Node\ParentsNodeAggregateNotVisibleInDimensionSpacePoint;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\CreateNodeAggregateWithNode;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\CreateNodeVariant;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\CreateRootNodeAggregateWithNode;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeAggregateNameWasChanged;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeGeneralizationVariantWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodePeerVariantWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Event\NodeSpecializationVariantWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeAggregate;
use Neos\EventSourcedContentRepository\Domain\ValueObject\CommandResult;
use Neos\EventSourcedContentRepository\Domain\ValueObject\PropertyValues;
use Neos\EventSourcedContentRepository\Exception\DimensionSpacePointNotFound;
use Neos\EventSourcedContentRepository\Service\Infrastructure\ReadSideMemoryCacheManager;
use Neos\EventSourcing\Event\Decorator\EventWithIdentifier;
use Neos\EventSourcing\Event\DomainEvents;

final class NodeAggregateCommandHandler
{
    /**
     * @var ContentStream\ContentStreamRepository
     */
    protected $contentStreamRepository;

    /**
     * Used for constraint checks against the current outside configuration state of node types
     *
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * Used for constraint checks against the current outside configuration state of content dimensions
     *
     * @var DimensionSpacePointSet
     */
    protected $allowedDimensionSubspace;

    /**
     * The graph projection used for soft constraint checks
     *
     * @var ContentGraphInterface
     */
    protected $contentGraph;

    /**
     * Used for variation resolution from the current outside state of content dimensions
     *
     * @var DimensionSpace\InterDimensionalVariationGraph
     */
    protected $interDimensionalVariationGraph;

    /**
     * Used for publishing events
     *
     * @var NodeEventPublisher
     */
    protected $nodeEventPublisher;

    /**
     * @var ReadSideMemoryCacheManager
     */
    protected $readSideMemoryCacheManager;


    public function __construct(
        ContentStream\ContentStreamRepository $contentStreamRepository,
        NodeTypeManager $nodeTypeManager,
        DimensionSpace\ContentDimensionZookeeper $contentDimensionZookeeper,
        ContentGraphInterface $contentGraph,
        DimensionSpace\InterDimensionalVariationGraph $interDimensionalVariationGraph,
        NodeEventPublisher $nodeEventPublisher,
        ReadSideMemoryCacheManager $readSideMemoryCacheManager
    ) {
        $this->contentStreamRepository = $contentStreamRepository;
        $this->nodeTypeManager = $nodeTypeManager;
        $this->allowedDimensionSubspace = $contentDimensionZookeeper->getAllowedDimensionSubspace();
        $this->contentGraph = $contentGraph;
        $this->interDimensionalVariationGraph = $interDimensionalVariationGraph;
        $this->nodeEventPublisher = $nodeEventPublisher;
        $this->readSideMemoryCacheManager = $readSideMemoryCacheManager;
    }

    /**
     * @param CreateRootNodeAggregateWithNode $command
     * @return CommandResult
     * @throws ContentStream\ContentStreamDoesNotExistYet
     */
    public function handleCreateRootNodeAggregateWithNode(CreateRootNodeAggregateWithNode $command): CommandResult
    {
        $this->readSideMemoryCacheManager->disableCache();

        $nodeAggregate = $this->getNodeAggregate($command->getContentStreamIdentifier(), $command->getNodeAggregateIdentifier());

        $nodeType = $this->getNodeType($command->getNodeTypeName());
        if (!$nodeType->isOfType(NodeTypeName::ROOT_NODE_TYPE_NAME)) {
            throw new NodeTypeIsNotOfTypeRoot('Node type "' . $nodeType . '" for root node "' . $command->getNodeAggregateIdentifier() . '" is not of type root.', 1541765701);
        }

        $events = $nodeAggregate->createRootWithNode(
            $command,
            $this->allowedDimensionSubspace
        );

        return CommandResult::fromPublishedEvents($events);
    }

    /**
     * @param CreateNodeAggregateWithNode $command
     * @return CommandResult
     * @throws ContentStream\ContentStreamDoesNotExistYet If the given content stream does not exist yet
     * @throws DimensionSpacePointNotFound If the given dimension space point is not in the allowed dimension space
     * @throws NodeConstraintException If a node aggregate of that type is not allowed to be created as a descendant of its parents
     * @throws NodeNameIsAlreadyOccupied If the given node name is already taken in any of the dimension space points the node will be visible in
     * @throws NodeTypeNotFoundException If the given type does not exist
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     * @throws NodeAggregatesTypeIsAmbiguous
     */
    public function handleCreateNodeAggregateWithNode(CreateNodeAggregateWithNode $command): CommandResult
    {
        $this->readSideMemoryCacheManager->disableCache();

        $nodeAggregate = $this->getNodeAggregate($command->getContentStreamIdentifier(), $command->getNodeAggregateIdentifier());
        $this->requireDimensionSpacePointToExist($command->getOriginDimensionSpacePoint());
        $nodeType = $this->getNodeType($command->getNodeTypeName());
        $this->requireAutoCreatedChildNodeTypesToExist($nodeType);
        $this->requireConstraintsImposedByAncestorsAreMet($command->getContentStreamIdentifier(), $nodeType, $command->getNodeName(), [$command->getParentNodeAggregateIdentifier()]);
        $this->requireProjectedNodeAggregateToNotExist($command->getContentStreamIdentifier(), $command->getNodeAggregateIdentifier());
        $parentNodeAggregate = $this->requireProjectedNodeAggregate($command->getContentStreamIdentifier(), $command->getParentNodeAggregateIdentifier());
        if ($nodeType->isOfType(NodeTypeName::ROOT_NODE_TYPE_NAME)) {
            throw new NodeTypeIsOfTypeRoot('Node type "' . $nodeType . '" for non-root node "' . $command->getNodeAggregateIdentifier() . '" is of type root.', 1541765806);
        }

        if ($command->getSucceedingSiblingNodeAggregateIdentifier()) {
            $this->requireProjectedNodeAggregate($command->getContentStreamIdentifier(), $command->getSucceedingSiblingNodeAggregateIdentifier());
        }
        $this->requireNodeAggregateToCoverDimensionSpacePoint($parentNodeAggregate, $command->getOriginDimensionSpacePoint());

        $specializations = $this->interDimensionalVariationGraph->getSpecializationSet($command->getOriginDimensionSpacePoint());
        $visibleDimensionSpacePoints = $specializations->getIntersection($parentNodeAggregate->getCoveredDimensionSpacePoints());

        if ($command->getNodeName()) {
            $this->requireNodeNameToBeUnoccupied(
                $command->getContentStreamIdentifier(),
                $command->getNodeName(),
                $command->getParentNodeAggregateIdentifier(),
                $command->getOriginDimensionSpacePoint(),
                $visibleDimensionSpacePoints
            );
        }

        $descendantNodeAggregateIdentifiers = $this->populateNodeAggregateIdentifiers($nodeType, $command->getTetheredDescendantNodeAggregateIdentifiers());

        foreach ($descendantNodeAggregateIdentifiers as $rawNodePath => $descendantNodeAggregateIdentifier) {
            $this->requireNodeAggregateToCurrentlyNotExist($command->getContentStreamIdentifier(), $descendantNodeAggregateIdentifier);
        }

        $defaultPropertyValues = $this->getDefaultPropertyValues($nodeType);
        $initialPropertyValues = $defaultPropertyValues->merge($command->getInitialPropertyValues());

        $events = $nodeAggregate->createWithNode(
            $command,
            $visibleDimensionSpacePoints,
            $initialPropertyValues
        );

        $events = $this->handleAutoCreatedChildNodes(
            $command,
            $nodeType,
            $visibleDimensionSpacePoints,
            $command->getNodeAggregateIdentifier(),
            $descendantNodeAggregateIdentifiers,
            $events
        );

        return CommandResult::fromPublishedEvents($events);
    }

    /**
     * @param CreateNodeAggregateWithNode $command
     * @param NodeType $nodeType
     * @param DimensionSpacePointSet $visibleDimensionSpacePoints
     * @param NodeAggregateIdentifier $parentNodeAggregateIdentifier
     * @param NodeAggregateIdentifiersByNodePaths $nodeAggregateIdentifiers
     * @param DomainEvents $events
     * @param NodePath|null $nodePath
     * @return DomainEvents
     * @throws ContentStream\ContentStreamDoesNotExistYet
     * @throws NodeTypeNotFoundException
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     */
    protected function handleAutoCreatedChildNodes(
        CreateNodeAggregateWithNode $command,
        NodeType $nodeType,
        DimensionSpacePointSet $visibleDimensionSpacePoints,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        NodeAggregateIdentifiersByNodePaths $nodeAggregateIdentifiers,
        DomainEvents $events,
        NodePath $nodePath = null
    ) {
        foreach ($nodeType->getAutoCreatedChildNodes() as $rawNodeName => $childNodeType) {
            $nodeName = NodeName::fromString($rawNodeName);
            $childNodePath = $nodePath ? $nodePath->appendPathSegment($nodeName) : NodePath::fromString((string) $nodeName);
            $childNodeAggregateIdentifier = $nodeAggregateIdentifiers->getNodeAggregateIdentifier($childNodePath);
            if (!$childNodeAggregateIdentifier) {
                throw new \Exception('Child node aggregate identifier for auto created child node at path ' . $nodePath . ' has not been initialized.', 1541763465);
            }
            if ($childNodeType->isOfType('Neos.ContentRepository:Root')) {
                throw new NodeTypeIsOfTypeRoot('Node type "' . $nodeType . '" for auto created child node "' . $childNodeAggregateIdentifier . '" is of type root.', 1541767062);
            }

            $initialPropertyValues = $this->getDefaultPropertyValues($childNodeType);

            $nodeAggregate = $this->getNodeAggregate($command->getContentStreamIdentifier(), $childNodeAggregateIdentifier);
            $events = $events->appendEvents($nodeAggregate->autoCreateWithNode(
                $command,
                NodeTypeName::fromString($childNodeType->getName()),
                $visibleDimensionSpacePoints,
                $parentNodeAggregateIdentifier,
                $nodeName,
                $initialPropertyValues
            ));

            $events = $this->handleAutoCreatedChildNodes(
                $command,
                $childNodeType,
                $visibleDimensionSpacePoints,
                $childNodeAggregateIdentifier,
                $nodeAggregateIdentifiers,
                $events,
                $childNodePath
            );
        }

        return $events;
    }

    protected function getDefaultPropertyValues(NodeType $nodeType): PropertyValues
    {
        $rawDefaultPropertyValues = [];
        foreach ($nodeType->getDefaultValuesForProperties() as $propertyName => $defaultValue) {
            $rawDefaultPropertyValues[$propertyName] = [
                'type' => $nodeType->getPropertyType($propertyName),
                'value' => $defaultValue
            ];
        }

        return PropertyValues::fromArray($rawDefaultPropertyValues);
    }

    /**
     * @param NodeType $nodeType
     * @param NodeAggregateIdentifiersByNodePaths $nodeAggregateIdentifiers
     * @param NodePath|null $childPath
     * @return NodeAggregateIdentifiersByNodePaths
     */
    protected function populateNodeAggregateIdentifiers(NodeType $nodeType, NodeAggregateIdentifiersByNodePaths $nodeAggregateIdentifiers, NodePath $childPath = null): NodeAggregateIdentifiersByNodePaths
    {
        foreach ($nodeType->getAutoCreatedChildNodes() as $rawChildName => $childNodeType) {
            $childName = NodeName::fromString($rawChildName);
            $childPath = $childPath ? $childPath->appendPathSegment($childName) : NodePath::fromString((string) $childName);
            if (!$nodeAggregateIdentifiers->getNodeAggregateIdentifier($childPath)) {
                $nodeAggregateIdentifiers = $nodeAggregateIdentifiers->add($childPath, NodeAggregateIdentifier::create());
            }
        }

        return $nodeAggregateIdentifiers;
    }

    /**
     * @param NodeType $nodeType
     * @throws NodeTypeNotFoundException
     */
    protected function requireAutoCreatedChildNodeTypesToExist(NodeType $nodeType): void
    {
        foreach ($nodeType->getAutoCreatedChildNodes() as $childNodeType) {
            $this->requireAutoCreatedChildNodeTypesToExist($childNodeType);
        }
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @return NodeAggregate
     * @throws NodeAggregatesTypeIsAmbiguous
     * @throws NodeAggregateCurrentlyDoesNotExist
     */
    protected function requireProjectedNodeAggregate(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier
    ): NodeAggregate {
        $nodeAggregate = $this->contentGraph->findNodeAggregateByIdentifier($contentStreamIdentifier, $nodeAggregateIdentifier);

        if (!$nodeAggregate) {
            throw new NodeAggregateCurrentlyDoesNotExist('Node aggregate "' . $nodeAggregateIdentifier . '" does currently not exist.', 1541678486);
        }

        return $nodeAggregate;
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @throws NodeAggregatesTypeIsAmbiguous
     * @throws NodeAggregateCurrentlyExists
     */
    protected function requireProjectedNodeAggregateToNotExist(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier
    ): void {
        $nodeAggregate = $this->contentGraph->findNodeAggregateByIdentifier($contentStreamIdentifier, $nodeAggregateIdentifier);

        if ($nodeAggregate) {
            throw new NodeAggregateCurrentlyExists('Node aggregate "' . $nodeAggregateIdentifier . '" does currently not exist.', 1541687645);
        }
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @throws ContentStream\ContentStreamDoesNotExistYet
     * @throws NodeAggregateCurrentlyDoesNotExist
     */
    protected function requireNodeAggregateToCurrentlyExist(ContentStreamIdentifier $contentStreamIdentifier, NodeAggregateIdentifier $nodeAggregateIdentifier): void
    {
        $nodeAggregate = $this->getNodeAggregate($contentStreamIdentifier, $nodeAggregateIdentifier);
        if (!$nodeAggregate->existsCurrently()) {
            throw new NodeAggregateCurrentlyDoesNotExist('Node aggregate "' . $nodeAggregateIdentifier . '" does currently not exist.', 1541678486);
        }
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @throws ContentStream\ContentStreamDoesNotExistYet
     */
    protected function requireNodeAggregateToCurrentlyNotExist(ContentStreamIdentifier $contentStreamIdentifier, NodeAggregateIdentifier $nodeAggregateIdentifier): void
    {
        $nodeAggregate = $this->getNodeAggregate($contentStreamIdentifier, $nodeAggregateIdentifier);
        if ($nodeAggregate->existsCurrently()) {
            throw new NodeAggregateCurrentlyExists('Node aggregate "' . $nodeAggregateIdentifier . '" does currently exist.', 1541687645);
        }
    }

    /**
     * @param ReadableNodeAggregateInterface $nodeAggregate
     */
    protected function requireNodeAggregateToBeNotOfTypeRoot(ReadableNodeAggregateInterface $nodeAggregate): void
    {
        $nodeType = $this->getNodeType($nodeAggregate->getNodeTypeName());
        if ($nodeType->isOfType(NodeTypeName::ROOT_NODE_TYPE_NAME)) {
            throw new NodeTypeIsOfTypeRoot('Node type "' . $nodeAggregate->getNodeTypeName() . '" is of type root.', 1552583824);
        }
    }

    /**
     * @param ReadableNodeAggregateInterface $nodeAggregate
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @throws NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint
     */
    protected function requireNodeAggregateToCoverDimensionSpacePoint(
        ReadableNodeAggregateInterface $nodeAggregate,
        DimensionSpacePoint $dimensionSpacePoint
    ): void {
        if (!$nodeAggregate->coversDimensionSpacePoint($dimensionSpacePoint)) {
            throw new NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint('Node aggregate "' . $nodeAggregate->getIdentifier() . '" is currently not visible in dimension space point ' . json_encode($dimensionSpacePoint) . '.', 1541678877);
        }
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeName $nodeName
     * @param NodeAggregateIdentifier $parentNodeAggregateIdentifier
     * @param DimensionSpacePoint $parentOriginDimensionSpacePoint
     * @param DimensionSpacePointSet $dimensionSpacePoints
     * @throws NodeNameIsAlreadyOccupied
     */
    protected function requireNodeNameToBeUnoccupied(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeName $nodeName,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        DimensionSpacePoint $parentOriginDimensionSpacePoint,
        DimensionSpacePointSet $dimensionSpacePoints
    ): void {
        $dimensionSpacePointsOccupiedByChildNodeName = $this->contentGraph->getDimensionSpacePointsOccupiedByChildNodeName(
            $contentStreamIdentifier,
            $nodeName,
            $parentNodeAggregateIdentifier,
            $parentOriginDimensionSpacePoint,
            $dimensionSpacePoints
        );
        if (count($dimensionSpacePointsOccupiedByChildNodeName) > 0) {
            throw new NodeNameIsAlreadyOccupied('Child node name "' . $nodeName . '" is already occupied for parent "' . $parentNodeAggregateIdentifier . '" in dimension space points ' . $dimensionSpacePointsOccupiedByChildNodeName);
        }
    }

    /**
     * @param Command\ChangeNodeAggregateType $command
     * @throws NodeTypeNotFound
     * @throws NodeConstraintException
     * @throws NodeTypeNotFoundException
     * @throws NodeAggregatesTypeIsAmbiguous
     */
    public function handleChangeNodeAggregateType(Command\ChangeNodeAggregateType $command)
    {
        $this->readSideMemoryCacheManager->disableCache();

        if (!$this->nodeTypeManager->hasNodeType((string)$command->getNewNodeTypeName())) {
            throw new NodeTypeNotFound('The given node type "' . $command->getNewNodeTypeName() . '" is unknown to the node type manager', 1520009174);
        }

        $this->checkConstraintsImposedByAncestors($command);
        $this->checkConstraintsImposedOnAlreadyPresentDescendants($command);

        // TODO: continue implementing!
    }

    /**
     * @param Command\ChangeNodeAggregateName $command
     * @return CommandResult
     */
    public function handleChangeNodeAggregateName(Command\ChangeNodeAggregateName $command): CommandResult
    {
        // TODO: check if CS exists
        // TODO: check if aggregate exists and delegate to it
        // TODO: check if aggregate is root
        $events = DomainEvents::fromArray([]);
        $this->nodeEventPublisher->withCommand($command, function () use ($command, &$events) {
            $events = DomainEvents::withSingleEvent(
                EventWithIdentifier::create(
                    new NodeAggregateNameWasChanged(
                        $command->getContentStreamIdentifier(),
                        $command->getNodeAggregateIdentifier(),
                        $command->getNewNodeName()
                    )
                )
            );

            $this->nodeEventPublisher->publishMany(
                NodeAggregateEventStreamName::fromContentStreamIdentifierAndNodeAggregateIdentifier(
                    $command->getContentStreamIdentifier(),
                    $command->getNodeAggregateIdentifier()
                )->getEventStreamName(),
                $events
            );
        });

        return CommandResult::fromPublishedEvents($events);
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeType $nodeType
     * @param NodeName $nodeName
     * @param array|NodeAggregateIdentifier[] $parentNodeAggregateIdentifiers
     * @throws ContentStream\ContentStreamDoesNotExistYet
     * @throws NodeConstraintException
     * @throws NodeTypeNotFoundException
     */
    protected function requireConstraintsImposedByAncestorsAreMet(ContentStreamIdentifier $contentStreamIdentifier, NodeType $nodeType, ?NodeName $nodeName, array $parentNodeAggregateIdentifiers): void
    {
        foreach ($parentNodeAggregateIdentifiers as $parentAggregateIdentifier) {
            $parentAggregate = $this->getNodeAggregate($contentStreamIdentifier, $parentAggregateIdentifier);
            /*
             * reenable this once node aggregates can say something about this
            try {

                $parentsNodeType = $this->getNodeType($parentAggregate->getNodeTypeName());
                if (!$parentsNodeType->allowsChildNodeType($nodeType)) {
                    throw new NodeConstraintException('Node type "' . $nodeType . '" is not allowed for child nodes of type ' . $parentsNodeType->getName());
                }
                if ($nodeName
                    &&$parentsNodeType->hasAutoCreatedChildNode($nodeName)
                    && $parentsNodeType->getTypeOfAutoCreatedChildNode($nodeName)->getName() !== $nodeType->getName()) {
                    throw new NodeConstraintException('Node type "' . $nodeType . '" does not match configured "' . $parentsNodeType->getTypeOfAutoCreatedChildNode($nodeName)->getName()
                        . '" for auto created child nodes for parent type "' . $parentsNodeType . '" with name "' . $nodeName . '"');
                }
            } catch (NodeTypeNotFound $e) {
                // skip constraint check; Once the parent is changed to be of an available type,
                // the constraint checks are executed again. See handleChangeNodeAggregateType
            }
            */

            /*
            foreach ($parentAggregate->getParentIdentifiers() as $grandParentAggregateIdentifier) {
                $grandParentAggregate = $this->getNodeAggregate($contentStreamIdentifier, $grandParentAggregateIdentifier);
                try {
                    $grandParentsNodeType = $this->getNodeType($grandParentAggregate->getNodeTypeName());
                    if ($grandParentsNodeType->hasAutoCreatedChildNode($parentAggregate->getNodeName())
                        && !$grandParentsNodeType->allowsGrandchildNodeType($parentAggregate->getNodeName(), $nodeType)) {
                        throw new NodeConstraintException('Node type "' . $nodeType . '" is not allowed below auto created child nodes "' . $parentAggregate->getNodeName()
                            . '" of nodes of type "' . $grandParentAggregate->getNodeTypeName() . '"', 1520011791);
                    }
                } catch (NodeTypeNotFound $e) {
                    // skip constraint check; Once the grand parent is changed to be of an available type,
                    // the constraint checks are executed again. See handleChangeNodeAggregateType
                }
            }*/
        }
    }

    /**
     * @param Command\ChangeNodeAggregateType $command
     * @throws NodeConstraintException
     * @throws NodeTypeNotFoundException
     * @throws NodeAggregatesTypeIsAmbiguous
     * @return void
     */
    protected function checkConstraintsImposedByAncestors(Command\ChangeNodeAggregateType $command): void
    {
        $nodeAggregate = $this->contentGraph->findNodeAggregateByIdentifier($command->getContentStreamIdentifier(), $command->getNodeAggregateIdentifier());
        $newNodeType = $this->nodeTypeManager->getNodeType((string)$command->getNewNodeTypeName());
        foreach ($this->contentGraph->findParentNodeAggregates($command->getContentStreamIdentifier(), $command->getNodeAggregateIdentifier()) as $parentAggregate) {
            $parentsNodeType = $this->nodeTypeManager->getNodeType((string)$parentAggregate->getNodeTypeName());
            if (!$parentsNodeType->allowsChildNodeType($newNodeType)) {
                throw new NodeConstraintException('Node type ' . $command->getNewNodeTypeName() . ' is not allowed below nodes of type ' . $parentAggregate->getNodeTypeName());
            }
            if ($nodeAggregate->getNodeName()
                && $parentsNodeType->hasAutoCreatedChildNode($nodeAggregate->getNodeName())
                && $parentsNodeType->getTypeOfAutoCreatedChildNode($nodeAggregate->getNodeName())->getName() !== (string)$command->getNewNodeTypeName()) {
                throw new NodeConstraintException('Cannot change type of auto created child node' . $nodeAggregate->getNodeName() . ' to ' . $command->getNewNodeTypeName());
            }
            foreach ($this->contentGraph->findParentNodeAggregates($command->getContentStreamIdentifier(), $parentAggregate->getIdentifier()) as $grandParentAggregate) {
                $grandParentsNodeType = $this->nodeTypeManager->getNodeType((string)$grandParentAggregate->getNodeTypeName());
                if ($parentAggregate->getNodeName()
                    && $grandParentsNodeType->hasAutoCreatedChildNode($parentAggregate->getNodeName())
                    && !$grandParentsNodeType->allowsGrandchildNodeType((string) $parentAggregate->getNodeName(), $newNodeType)) {
                    throw new NodeConstraintException('Node type "' . $command->getNewNodeTypeName() . '" is not allowed below auto created child nodes "' . $parentAggregate->getNodeName()
                        . '" of nodes of type "' . $grandParentAggregate->getNodeTypeName() . '"', 1520011791);
                }
            }
        }
    }

    /**
     * @param Command\ChangeNodeAggregateType $command
     * @throws NodeConstraintException
     * @throws NodeTypeNotFoundException
     * @return \void
     */
    protected function checkConstraintsImposedOnAlreadyPresentDescendants(Command\ChangeNodeAggregateType $command): void
    {
        $newNodeType = $this->nodeTypeManager->getNodeType((string)$command->getNewNodeTypeName());

        foreach ($this->contentGraph->findChildNodeAggregates($command->getContentStreamIdentifier(), $command->getNodeAggregateIdentifier()) as $childAggregate) {
            $childsNodeType = $this->nodeTypeManager->getNodeType((string)$childAggregate->getNodeTypeName());
            if (!$newNodeType->allowsChildNodeType($childsNodeType)) {
                if (!$command->getStrategy()) {
                    throw new NodeConstraintException('Node type ' . $command->getNewNodeTypeName() . ' does not allow children of type  ' . $childAggregate->getNodeTypeName()
                        . ', which already exist. Please choose a resolution strategy.', 1520014467);
                }
            }

            if ($childAggregate->getNodeName() && $newNodeType->hasAutoCreatedChildNode($childAggregate->getNodeName())) {
                foreach ($this->contentGraph->findChildNodeAggregates($command->getContentStreamIdentifier(), $childAggregate->getIdentifier()) as $grandChildAggregate) {
                    $grandChildsNodeType = $this->nodeTypeManager->getNodeType((string)$grandChildAggregate->getNodeTypeName());
                    if ($childAggregate->getNodeName() && !$newNodeType->allowsGrandchildNodeType((string)$childAggregate->getNodeName(), $grandChildsNodeType)) {
                        if (!$command->getStrategy()) {
                            throw new NodeConstraintException('Node type ' . $command->getNewNodeTypeName() . ' does not allow auto created child nodes "' . $childAggregate->getNodeName()
                                . '" to have children of type  ' . $grandChildAggregate->getNodeTypeName() . ', which already exist. Please choose a resolution strategy.', 1520151998);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param Command\CreateNodeVariant $command
     * @return CommandResult
     * @throws ContentStream\ContentStreamDoesNotExistYet
     * @throws NodeAggregateCurrentlyExists
     * @throws DimensionSpacePointNotFound
     * @throws NodeAggregatesTypeIsAmbiguous
     * @throws DimensionSpacePointIsNotYetOccupied
     * @throws DimensionSpacePointIsAlreadyOccupied
     * @throws NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint
     */
    public function handleCreateNodeVariant(Command\CreateNodeVariant $command): CommandResult
    {
        $this->readSideMemoryCacheManager->disableCache();

        $this->requireContentStreamToExist($command->getContentStreamIdentifier());
        $nodeAggregate = $this->requireProjectedNodeAggregate($command->getContentStreamIdentifier(), $command->getNodeAggregateIdentifier());
        $this->requireDimensionSpacePointToExist($command->getSourceDimensionSpacePoint());
        $this->requireDimensionSpacePointToExist($command->getTargetDimensionSpacePoint());
        $this->requireNodeAggregateToBeNotOfTypeRoot($nodeAggregate);
        $this->requireNodeAggregateToOccupyDimensionSpacePoint($nodeAggregate, $command->getSourceDimensionSpacePoint());
        $this->requireNodeAggregateToNotOccupyDimensionSpacePoint($nodeAggregate, $command->getTargetDimensionSpacePoint());

        $parentNodeAggregate = $this->contentGraph->findParentNodeAggregateByChildOriginDimensionSpacePoint(
            $command->getContentStreamIdentifier(),
            $command->getNodeAggregateIdentifier(),
            $command->getSourceDimensionSpacePoint()
        );
        $this->requireNodeAggregateToCoverDimensionSpacePoint($parentNodeAggregate, $command->getTargetDimensionSpacePoint());

        $events = [];
        $specializations = $this->interDimensionalVariationGraph->getIndexedSpecializations($command->getSourceDimensionSpacePoint());
        if ($specializations->contains($command->getTargetDimensionSpacePoint())) {
            $excludedSet = new DimensionSpacePointSet([]);
            foreach ($specializations->getIntersection($nodeAggregate->getOccupiedDimensionSpacePoints()) as $occupiedSpecialization) {
                $excludedSet = $excludedSet->getUnion($this->interDimensionalVariationGraph->getSpecializationSet($occupiedSpecialization));
            }
            $events[] = new Event\NodeSpecializationVariantWasCreated(
                $command->getContentStreamIdentifier(),
                $command->getNodeAggregateIdentifier(),
                $command->getSourceDimensionSpacePoint(),
                $command->getTargetDimensionSpacePoint(),
                $this->interDimensionalVariationGraph->getSpecializationSet(
                    $command->getTargetDimensionSpacePoint(),
                    true,
                    $excludedSet
                )
            );
            /** @var NodeSpecializationVariantWasCreated[] $events */
        } else {
            $generalizations = $this->interDimensionalVariationGraph->getIndexedGeneralizations($command->getSourceDimensionSpacePoint());
            if ($generalizations->contains($command->getTargetDimensionSpacePoint())) {
                $events[] = new Event\NodeGeneralizationVariantWasCreated(
                    $command->getContentStreamIdentifier(),
                    $command->getNodeAggregateIdentifier(),
                    $command->getSourceDimensionSpacePoint(),
                    $command->getTargetDimensionSpacePoint(),
                    $this->interDimensionalVariationGraph->getSpecializationSet(
                        $command->getTargetDimensionSpacePoint(),
                        true,
                        $nodeAggregate->getCoveredDimensionSpacePoints()
                    )
                );
                /** @var NodeGeneralizationVariantWasCreated[] $events */
            } else {
                $peerVisibility = $this->interDimensionalVariationGraph->getSpecializationSet(
                    $command->getTargetDimensionSpacePoint(),
                    true,
                    $nodeAggregate->getOccupiedDimensionSpacePoints()
                );

                $this->collectNodePeerVariantsThatWillHaveBeenCreated($command, $nodeAggregate, $peerVisibility, $events);
                /** @var NodePeerVariantWasCreated[] $events */
            }
        }

        $publishedEvents = DomainEvents::createEmpty();
        $this->nodeEventPublisher->withCommand($command, function () use ($command, $events, &$publishedEvents) {
            foreach ($events as $event) {
                $domainEvents = DomainEvents::withSingleEvent(
                    EventWithIdentifier::create($event)
                );

                $streamName = NodeAggregateEventStreamName::fromContentStreamIdentifierAndNodeAggregateIdentifier(
                    $event->getContentStreamIdentifier(),
                    $event->getNodeAggregateIdentifier()
                );

                $this->nodeEventPublisher->publishMany($streamName->getEventStreamName(), $domainEvents);

                $publishedEvents = $publishedEvents->appendEvents($domainEvents);
            }
        });

        return CommandResult::fromPublishedEvents($publishedEvents);
    }

    protected function collectNodePeerVariantsThatWillHaveBeenCreated(
        CreateNodeVariant $command,
        ReadableNodeAggregateInterface $nodeAggregate,
        DimensionSpacePointSet $peerVisibility,
        array& $events
    ) {
        $events[] = new Event\NodePeerVariantWasCreated(
            $command->getContentStreamIdentifier(),
            $nodeAggregate->getIdentifier(),
            $command->getSourceDimensionSpacePoint(),
            $command->getTargetDimensionSpacePoint(),
            $peerVisibility
        );

        $nodeType = $this->getNodeType($nodeAggregate->getNodeTypeName());
        foreach ($nodeType->getAutoCreatedChildNodes() as $nodeName => $nodeTypeName) {
            $childNodeAggregate = $this->contentGraph->findChildNodeAggregateByName(
                $command->getContentStreamIdentifier(),
                $nodeAggregate->getIdentifier(),
                NodeName::fromString($nodeName)
            );

            $this->collectNodePeerVariantsThatWillHaveBeenCreated($command, $childNodeAggregate, $peerVisibility, $events);
        }
    }

    /**
     * @param Command\CreateNodeGeneralization $command
     * @return CommandResult
     * @throws ContentStream\ContentStreamDoesNotExistYet
     * @throws DimensionSpacePointIsAlreadyOccupied
     * @throws DimensionSpacePointIsNotYetOccupied
     * @throws DimensionSpacePointNotFound
     */
    public function handleCreateNodeGeneralization(Command\CreateNodeGeneralization $command): CommandResult
    {
        $this->readSideMemoryCacheManager->disableCache();

        $nodeAggregate = $this->getNodeAggregate($command->getContentStreamIdentifier(), $command->getNodeAggregateIdentifier());

        $this->requireDimensionSpacePointToExist($command->getTargetDimensionSpacePoint());
        $this->requireDimensionSpacePointToBeGeneralization($command->getTargetDimensionSpacePoint(), $command->getSourceDimensionSpacePoint());

        $nodeAggregate->requireDimensionSpacePointToBeOccupied($command->getSourceDimensionSpacePoint());
        $nodeAggregate->requireDimensionSpacePointToBeUnoccupied($command->getTargetDimensionSpacePoint());

        $events = null;
        $this->nodeEventPublisher->withCommand($command, function () use ($command, $nodeAggregate, &$events) {
            $events = DomainEvents::withSingleEvent(
                EventWithIdentifier::create(
                    new Event\NodeGeneralizationVariantWasCreated(
                        $command->getContentStreamIdentifier(),
                        $command->getNodeAggregateIdentifier(),
                        $command->getSourceDimensionSpacePoint(),
                        $command->getTargetDimensionSpacePoint(),
                        $this->interDimensionalVariationGraph->getSpecializationSet($command->getTargetDimensionSpacePoint(), true, $nodeAggregate->getVisibleInDimensionSpacePoints())
                    )
                )
            );

            $this->nodeEventPublisher->publishMany($nodeAggregate->getStreamName(), $events);
        });

        return CommandResult::fromPublishedEvents($events);
    }

    /**
     * @param ReadableNodeAggregateInterface $nodeAggregate
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @throws DimensionSpacePointIsNotYetOccupied
     */
    protected function requireNodeAggregateToOccupyDimensionSpacePoint(ReadableNodeAggregateInterface $nodeAggregate, DimensionSpacePoint $dimensionSpacePoint)
    {
        if (!$nodeAggregate->occupiesDimensionSpacePoint($dimensionSpacePoint)) {
            throw new DimensionSpacePointIsNotYetOccupied('Dimension space point ' . json_encode($dimensionSpacePoint) . ' is not yet occupied by node aggregate "' . $nodeAggregate->getIdentifier() . '"', 1552595396);
        }
    }

    /**
     * @param ReadableNodeAggregateInterface $nodeAggregate
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @throws DimensionSpacePointIsAlreadyOccupied
     */
    protected function requireNodeAggregateToNotOccupyDimensionSpacePoint(ReadableNodeAggregateInterface $nodeAggregate, DimensionSpacePoint $dimensionSpacePoint)
    {
        if ($nodeAggregate->occupiesDimensionSpacePoint($dimensionSpacePoint)) {
            throw new DimensionSpacePointIsAlreadyOccupied('Dimension space point ' . json_encode($dimensionSpacePoint) . ' is already occupied by node aggregate "' . $nodeAggregate->getIdentifier() . '"', 1552595441);
        }
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @throws ContentStream\ContentStreamDoesNotExistYet
     */
    protected function requireContentStreamToExist(ContentStreamIdentifier $contentStreamIdentifier): void
    {
        $contentStream = $this->contentStreamRepository->findContentStream($contentStreamIdentifier);
        if (!$contentStream) {
            throw new ContentStream\ContentStreamDoesNotExistYet('Content stream "' . $contentStreamIdentifier . " does not exist yet.", 1521386692);
        }
    }

    /**
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @throws DimensionSpacePointNotFound
     */
    protected function requireDimensionSpacePointToExist(DimensionSpacePoint $dimensionSpacePoint): void
    {
        if (!$this->allowedDimensionSubspace->contains($dimensionSpacePoint)) {
            throw new DimensionSpacePointNotFound(sprintf('%s was not found in the allowed dimension subspace', $dimensionSpacePoint), 1520260137);
        }
    }

    /**
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @param DimensionSpacePoint $generalization
     * @throws DimensionSpacePointIsNoGeneralization
     */
    protected function requireDimensionSpacePointToBeSpecialization(DimensionSpacePoint $dimensionSpacePoint, DimensionSpacePoint $generalization): void
    {
        if (!$this->interDimensionalVariationGraph->getSpecializationSet($generalization)->contains($dimensionSpacePoint)) {
            throw new DimensionSpace\Exception\DimensionSpacePointIsNoSpecialization($dimensionSpacePoint . ' is no specialization of ' . $generalization, 1519931770);
        }
    }

    /**
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @param DimensionSpacePoint $specialization
     * @throws DimensionSpacePointIsNoGeneralization
     */
    protected function requireDimensionSpacePointToBeGeneralization(DimensionSpacePoint $dimensionSpacePoint, DimensionSpacePoint $specialization): void
    {
        if (!$this->interDimensionalVariationGraph->getSpecializationSet($dimensionSpacePoint)->contains($specialization)) {
            throw new DimensionSpace\Exception\DimensionSpacePointIsNoGeneralization($dimensionSpacePoint . ' is no generalization of ' . $dimensionSpacePoint, 1521367710);
        }
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param DimensionSpacePoint $sourceDimensionSpacePoint
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @throws ParentsNodeAggregateNotVisibleInDimensionSpacePoint
     */
    protected function requireParentNodesAggregateToBeVisibleInDimensionSpacePoint(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        DimensionSpacePoint $sourceDimensionSpacePoint,
        DimensionSpacePoint $dimensionSpacePoint
    ): void {
        $sourceSubgraph = $this->contentGraph->getSubgraphByIdentifier($contentStreamIdentifier, $sourceDimensionSpacePoint, VisibilityConstraints::withoutRestrictions());
        $sourceParentNode = $sourceSubgraph->findParentNode($nodeAggregateIdentifier);
        if (!$sourceParentNode // the root node is visible in all dimension space points
            || $this->contentGraph->findVisibleDimensionSpacePointsOfNodeAggregate($contentStreamIdentifier, $sourceParentNode->getNodeAggregateIdentifier())
                ->contains($dimensionSpacePoint)) {
            return;
        }

        throw new ParentsNodeAggregateNotVisibleInDimensionSpacePoint('No suitable parent could be found for node "' . $nodeAggregateIdentifier . '" in target dimension space point ' . $dimensionSpacePoint,
            1521322565);
    }

    /**
     * @param NodeTypeName $nodeTypeName
     * @return NodeType
     * @throws NodeTypeNotFound
     */
    protected function getNodeType(NodeTypeName $nodeTypeName): NodeType
    {
        try {
            return $this->nodeTypeManager->getNodeType((string)$nodeTypeName);
        } catch (NodeTypeNotFoundException $e) {
            throw new NodeTypeNotFound('Node type "' . $nodeTypeName . '" is unknown to the node type manager.', 1541671070);
        }
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @return \Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregate
     * @throws ContentStream\ContentStreamDoesNotExistYet
     */
    protected function getNodeAggregate(ContentStreamIdentifier $contentStreamIdentifier, NodeAggregateIdentifier $nodeAggregateIdentifier): \Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregate
    {
        $contentStream = $this->contentStreamRepository->findContentStream($contentStreamIdentifier);
        if (!$contentStream) {
            throw new ContentStream\ContentStreamDoesNotExistYet('The content stream "' . $contentStreamIdentifier . '" to get a node aggregate from does not exist yet.');
        }

        return $contentStream->getNodeAggregate($nodeAggregateIdentifier);
    }
}
