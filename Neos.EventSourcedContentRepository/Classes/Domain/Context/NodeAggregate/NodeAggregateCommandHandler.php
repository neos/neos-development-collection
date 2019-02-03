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
use Neos\ContentRepository\DimensionSpace\DimensionSpace\Exception\DimensionSpacePointIsNoSpecialization;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\ContentRepository\Domain\ValueObject\NodePath;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeName;
use Neos\ContentRepository\Domain\ValueObject\RootNodeIdentifiers;
use Neos\ContentRepository\Exception\NodeConstraintException;
use Neos\ContentRepository\Exception\NodeTypeNotFoundException;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream;
use Neos\ContentRepository\DimensionSpace\DimensionSpace;
use Neos\EventSourcedContentRepository\Domain\Context\Node\NodeEventPublisher;
use Neos\EventSourcedContentRepository\Domain\Context\Node\ParentsNodeAggregateNotVisibleInDimensionSpacePoint;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\CreateNodeAggregateWithNode;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\CreateRootNodeAggregateWithNode;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\EventSourcedContentRepository\Domain\ValueObject\PropertyValue;
use Neos\EventSourcedContentRepository\Domain\ValueObject\PropertyValues;
use Neos\EventSourcedContentRepository\Exception\DimensionSpacePointNotFound;

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

    public function __construct(
        ContentStream\ContentStreamRepository $contentStreamRepository,
        NodeTypeManager $nodeTypeManager,
        DimensionSpace\ContentDimensionZookeeper $contentDimensionZookeeper,
        ContentGraphInterface $contentGraph,
        DimensionSpace\InterDimensionalVariationGraph $interDimensionalVariationGraph,
        NodeEventPublisher $nodeEventPublisher
    ) {
        $this->contentStreamRepository = $contentStreamRepository;
        $this->nodeTypeManager = $nodeTypeManager;
        $this->allowedDimensionSubspace = $contentDimensionZookeeper->getAllowedDimensionSubspace();
        $this->contentGraph = $contentGraph;
        $this->interDimensionalVariationGraph = $interDimensionalVariationGraph;
        $this->nodeEventPublisher = $nodeEventPublisher;
    }

    /**
     * @param CreateRootNodeAggregateWithNode $command
     * @throws ContentStream\ContentStreamDoesNotExistYet
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     * @throws NodeTypeNotFound
     * @throws NodeTypeIsNotOfTypeRoot
     * @throws \Exception
     */
    public function handleCreateRootNodeAggregateWithNode(CreateRootNodeAggregateWithNode $command): void
    {
        $nodeAggregate = $this->getNodeAggregate($command->getContentStreamIdentifier(), $command->getNodeAggregateIdentifier());

        $nodeType = $this->getNodeType($command->getNodeTypeName());
        if (!$nodeType->isOfType(NodeTypeName::ROOT_NODE_TYPE_NAME)) {
            throw new NodeTypeIsNotOfTypeRoot('Node type "' . $nodeType . '" for root node "' . $command->getNodeAggregateIdentifier() . '" is not of type root.', 1541765701);
        }

        $nodeAggregate->createRootWithNode(
            $command->getContentStreamIdentifier(),
            $command->getNodeTypeName(),
            $this->allowedDimensionSubspace,
            $command->getInitiatingUserIdentifier()
        );
    }

    /**
     * @param CreateNodeAggregateWithNode $command
     * @throws ContentStream\ContentStreamDoesNotExistYet If the given content stream does not exist yet
     * @throws NodeAggregateCurrentlyExists If the node aggregate to be created already exists
     * @throws DimensionSpacePointNotFound If the given dimension space point is not in the allowed dimension space
     * @throws NodeTypeNotFoundException If the given type does not exist
     * @throws NodeConstraintException If a node aggregate of that type is not allowed to be created as a descendant of its parents
     * @throws DimensionSpacePointIsNotYetOccupied If the parent node is not visible in the given dimension space point
     * @throws NodeNameIsAlreadyOccupied If the given node name is already taken in any of the dimension space points the node will be visible in
     * @throws NodeAggregateDoesNotCurrentlyExist If the parent node aggregate does not exist
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     * @throws \Exception
     */
    public function handleCreateNodeAggregateWithNode(CreateNodeAggregateWithNode $command): void
    {
        $nodeAggregate = $this->getNodeAggregate($command->getContentStreamIdentifier(), $command->getNodeAggregateIdentifier());
        $this->requireDimensionSpacePointToExist($command->getDimensionSpacePoint());
        $nodeType = $this->getNodeType($command->getNodeTypeName());
        $this->requireAutoCreatedChildNodeTypesToExist($nodeType);
        $this->requireConstraintsImposedByAncestorsAreMet($command->getContentStreamIdentifier(), $nodeType, $command->getNodeName(), [$command->getParentNodeAggregateIdentifier()]);
        $this->requireNodeAggregateToCurrentlyNotExist($command->getContentStreamIdentifier(), $command->getNodeAggregateIdentifier());
        $this->requireNodeAggregateToCurrentlyExist($command->getContentStreamIdentifier(), $command->getParentNodeAggregateIdentifier());
        if ($nodeType->isOfType(NodeTypeName::ROOT_NODE_TYPE_NAME)) {
            throw new NodeTypeIsOfTypeRoot('Node type "' . $nodeType . '" for non-root node "' . $command->getNodeAggregateIdentifier() . '" is of type root.', 1541765806);
        }

        if ($command->getSucceedingSiblingNodeAggregateIdentifier()) {
            $this->requireNodeAggregateToCurrentlyExist($command->getContentStreamIdentifier(), $command->getSucceedingSiblingNodeAggregateIdentifier());
        }
        $this->requireNodeAggregateToBeVisibleInDimensionSpacePoint($command->getContentStreamIdentifier(), $command->getParentNodeAggregateIdentifier(), $command->getDimensionSpacePoint());

        $specializations = $this->interDimensionalVariationGraph->getSpecializationSet($command->getDimensionSpacePoint());
        $parentAggregate = $this->getNodeAggregate($command->getContentStreamIdentifier(), $command->getParentNodeAggregateIdentifier());
        $visibleDimensionSpacePoints = $specializations->intersect($parentAggregate->getVisibleInDimensionSpacePoints());

        $this->requireNodeNameToBeUnoccupied(
            $command->getContentStreamIdentifier(),
            $command->getNodeName(),
            $command->getParentNodeAggregateIdentifier(),
            $command->getDimensionSpacePoint(),
            $visibleDimensionSpacePoints
        );

        $descendantNodeAggregateIdentifiers = $this->populateNodeAggregateIdentifiers($nodeType, $command->getAutoCreatedDescendantNodeAggregateIdentifiers());

        foreach ($descendantNodeAggregateIdentifiers as $rawNodePath => $nodeAggregateIdentifier) {
            $this->requireNodeAggregateToCurrentlyNotExist($command->getContentStreamIdentifier(), $nodeAggregateIdentifier);
        }

        $propertyValues = [];
        foreach ($nodeType->getDefaultValuesForProperties() as $rawPropertyName => $propertyValue) {
            $propertyValues[$rawPropertyName] = new PropertyValue($propertyValue, $nodeType->getPropertyType($rawPropertyName));
        }
        $defaultPropertyValues = new PropertyValues($propertyValues);
        $initialPropertyValues = $defaultPropertyValues->merge($command->getInitialPropertyValues());

        $nodeAggregate->createWithNode(
            $command->getContentStreamIdentifier(),
            $command->getNodeTypeName(),
            $command->getDimensionSpacePoint(),
            $visibleDimensionSpacePoints,
            $command->getParentNodeAggregateIdentifier(),
            $command->getNodeName(),
            $initialPropertyValues
        );

        $this->handleAutoCreatedChildNodes(
            $command->getContentStreamIdentifier(),
            $nodeType,
            $command->getDimensionSpacePoint(),
            $visibleDimensionSpacePoints,
            $command->getNodeAggregateIdentifier(),
            $descendantNodeAggregateIdentifiers
        );
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeType $nodeType
     * @param DimensionSpacePoint $originDimensionSpacePoint
     * @param DimensionSpacePointSet $visibleDimensionSpacePoints
     * @param NodeAggregateIdentifier $parentNodeAggregateIdentifier
     * @param NodeAggregateIdentifiersByNodePaths $nodeAggregateIdentifiers
     * @param NodePath|null $nodePath
     * @throws ContentStream\ContentStreamDoesNotExistYet
     * @throws NodeTypeNotFoundException
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     * @throws \Exception
     */
    protected function handleAutoCreatedChildNodes(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeType $nodeType,
        DimensionSpacePoint $originDimensionSpacePoint,
        DimensionSpacePointSet $visibleDimensionSpacePoints,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        NodeAggregateIdentifiersByNodePaths $nodeAggregateIdentifiers,
        NodePath $nodePath = null
    ) {
        foreach ($nodeType->getAutoCreatedChildNodes() as $rawNodeName => $childNodeType) {
            $nodeName = new NodeName($rawNodeName);
            $childNodePath = $nodePath ? $nodePath->appendPathSegment($nodeName) : new NodePath((string) $nodeName);
            $childNodeAggregateIdentifier = $nodeAggregateIdentifiers->getNodeAggregateIdentifier($childNodePath);
            if (!$childNodeAggregateIdentifier) {
                throw new \Exception('Child node aggregate identifier for auto created child node at path ' . $nodePath . ' has not been initialized.', 1541763465);
            }
            if ($childNodeType->isOfType('Neos.ContentRepository:Root')) {
                throw new NodeTypeIsOfTypeRoot('Node type "' . $nodeType . '" for auto created child node "' . $childNodeAggregateIdentifier . '" is of type root.', 1541767062);
            }

            $defaultPropertyValues = [];
            foreach ($childNodeType->getDefaultValuesForProperties() as $rawPropertyName => $propertyValue) {
                $defaultPropertyValues[$rawPropertyName] = new PropertyValue($propertyValue, $childNodeType->getPropertyType($rawPropertyName));
            }
            $initialPropertyValues = new PropertyValues($defaultPropertyValues);

            $nodeAggregate = $this->getNodeAggregate($contentStreamIdentifier, $childNodeAggregateIdentifier);
            $nodeAggregate->autoCreateWithNode(
                $contentStreamIdentifier,
                new NodeTypeName($childNodeType->getName()),
                $originDimensionSpacePoint,
                $visibleDimensionSpacePoints,
                $parentNodeAggregateIdentifier,
                $nodeName,
                $initialPropertyValues
            );

            $this->handleAutoCreatedChildNodes(
                $contentStreamIdentifier,
                $childNodeType,
                $originDimensionSpacePoint,
                $visibleDimensionSpacePoints,
                $childNodeAggregateIdentifier,
                $nodeAggregateIdentifiers,
                $childNodePath
            );
        }
    }

    /**
     * @param NodeType $nodeType
     * @param NodeAggregateIdentifiersByNodePaths $nodeAggregateIdentifiers
     * @param NodePath|null $childPath
     * @return NodeAggregateIdentifiersByNodePaths
     * @throws \Exception
     */
    protected function populateNodeAggregateIdentifiers(NodeType $nodeType, NodeAggregateIdentifiersByNodePaths $nodeAggregateIdentifiers, NodePath $childPath = null): NodeAggregateIdentifiersByNodePaths
    {
        foreach ($nodeType->getAutoCreatedChildNodes() as $rawChildName => $childNodeType) {
            $childName = new NodeName($rawChildName);
            $childPath = $childPath ? $childPath->appendPathSegment($childName) : new NodePath((string) $childName);
            if (!$nodeAggregateIdentifiers->getNodeAggregateIdentifier($childPath)) {
                $nodeAggregateIdentifiers = $nodeAggregateIdentifiers->add($childPath, new NodeAggregateIdentifier());
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
     * @throws ContentStream\ContentStreamDoesNotExistYet
     * @throws NodeAggregateDoesNotCurrentlyExist
     */
    protected function requireNodeAggregateToCurrentlyExist(ContentStreamIdentifier $contentStreamIdentifier, NodeAggregateIdentifier $nodeAggregateIdentifier): void
    {
        $nodeAggregate = $this->getNodeAggregate($contentStreamIdentifier, $nodeAggregateIdentifier);
        if (!$nodeAggregate->existsCurrently()) {
            throw new NodeAggregateDoesNotCurrentlyExist('Node aggregate "' . $nodeAggregateIdentifier . '" does currently not exist.', 1541678486);
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
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @throws ContentStream\ContentStreamDoesNotExistYet
     * @throws DimensionSpacePointIsNotYetOccupied
     */
    protected function requireNodeAggregateToBeVisibleInDimensionSpacePoint(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        DimensionSpacePoint $dimensionSpacePoint
    ): void {
        $nodeAggregate = $this->getNodeAggregate($contentStreamIdentifier, $nodeAggregateIdentifier);
        if (!$nodeAggregate->isDimensionSpacePointOccupied($dimensionSpacePoint)) {
            throw new DimensionSpacePointIsNotYetOccupied('Node aggregate "' . $nodeAggregateIdentifier . '" does not currently occupy dimension space point "' . $dimensionSpacePoint . '".', 1541678877);
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
        if (!empty($dimensionSpacePointsOccupiedByChildNodeName)) {
            throw new NodeNameIsAlreadyOccupied('Child node name "' . $nodeName . '" is already occupied for parent "' . $parentNodeAggregateIdentifier . '" in dimension space points ' . $dimensionSpacePointsOccupiedByChildNodeName);
        }
    }

    /**
     * @param Command\ChangeNodeAggregateType $command
     * @throws NodeTypeNotFound
     * @throws NodeConstraintException
     * @throws NodeTypeNotFoundException
     * @throws \Neos\EventSourcedContentRepository\Domain\Context\Node\NodeAggregatesTypeIsAmbiguous
     */
    public function handleChangeNodeAggregateType(Command\ChangeNodeAggregateType $command)
    {
        if (!$this->nodeTypeManager->hasNodeType((string)$command->getNewNodeTypeName())) {
            throw new NodeTypeNotFound('The given node type "' . $command->getNewNodeTypeName() . '" is unknown to the node type manager', 1520009174);
        }

        $this->checkConstraintsImposedByAncestors($command);
        $this->checkConstraintsImposedOnAlreadyPresentDescendants($command);
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
    protected function requireConstraintsImposedByAncestorsAreMet(ContentStreamIdentifier $contentStreamIdentifier, NodeType $nodeType, NodeName $nodeName, array $parentNodeAggregateIdentifiers): void
    {
        foreach ($parentNodeAggregateIdentifiers as $parentAggregateIdentifier) {
            $parentAggregate = $this->getNodeAggregate($contentStreamIdentifier, $parentAggregateIdentifier);
            try {
                $parentsNodeType = $this->getNodeType($parentAggregate->getNodeTypeName());
                if (!$parentsNodeType->allowsChildNodeType($nodeType)) {
                    throw new NodeConstraintException('Node type "' . $nodeType . '" is not allowed for child nodes of type ' . $parentsNodeType->getName());
                }
                if ($parentsNodeType->hasAutoCreatedChildNode($nodeName)
                    && $parentsNodeType->getTypeOfAutoCreatedChildNode($nodeName)->getName() !== $nodeType->getName()) {
                    throw new NodeConstraintException('Node type "' . $nodeType . '" does not match configured "' . $parentsNodeType->getTypeOfAutoCreatedChildNode($nodeName)->getName()
                        . '" for auto created child nodes for parent type "' . $parentsNodeType . '" with name "' . $nodeName . '"');
                }
            } catch (NodeTypeNotFound $e) {
                // skip constraint check; Once the parent is changed to be of an available type,
                // the constraint checks are executed again. See handleChangeNodeAggregateType
            }

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
     * @throws \Neos\EventSourcedContentRepository\Domain\Context\Node\NodeAggregatesTypeIsAmbiguous
     * @return void
     */
    protected function checkConstraintsImposedByAncestors(Command\ChangeNodeAggregateType $command): void
    {
        $nodeAggregate = $this->contentGraph->findNodeAggregateByIdentifier($command->getContentStreamIdentifier(), $command->getNodeAggregateIdentifier());
        $newNodeType = $this->nodeTypeManager->getNodeType((string)$command->getNewNodeTypeName());
        foreach ($this->contentGraph->findParentAggregates($command->getContentStreamIdentifier(), $command->getNodeAggregateIdentifier()) as $parentAggregate) {
            $parentsNodeType = $this->nodeTypeManager->getNodeType((string)$parentAggregate->getNodeTypeName());
            if (!$parentsNodeType->allowsChildNodeType($newNodeType)) {
                throw new NodeConstraintException('Node type ' . $command->getNewNodeTypeName() . ' is not allowed below nodes of type ' . $parentAggregate->getNodeTypeName());
            }
            if ($parentsNodeType->hasAutoCreatedChildNode($nodeAggregate->getNodeName())
                && $parentsNodeType->getTypeOfAutoCreatedChildNode($nodeAggregate->getNodeName())->getName() !== (string)$command->getNewNodeTypeName()) {
                throw new NodeConstraintException('Cannot change type of auto created child node' . $nodeAggregate->getNodeName() . ' to ' . $command->getNewNodeTypeName());
            }
            foreach ($this->contentGraph->findParentAggregates($command->getContentStreamIdentifier(), $parentAggregate->getNodeAggregateIdentifier()) as $grandParentAggregate) {
                $grandParentsNodeType = $this->nodeTypeManager->getNodeType((string)$grandParentAggregate->getNodeTypeName());
                if ($grandParentsNodeType->hasAutoCreatedChildNode($parentAggregate->getNodeName())
                    && !$grandParentsNodeType->allowsGrandchildNodeType($parentAggregate->getNodeName(), $newNodeType)) {
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

        foreach ($this->contentGraph->findChildAggregates($command->getContentStreamIdentifier(), $command->getNodeAggregateIdentifier()) as $childAggregate) {
            $childsNodeType = $this->nodeTypeManager->getNodeType((string)$childAggregate->getNodeTypeName());
            if (!$newNodeType->allowsChildNodeType($childsNodeType)) {
                if (!$command->getStrategy()) {
                    throw new NodeConstraintException('Node type ' . $command->getNewNodeTypeName() . ' does not allow children of type  ' . $childAggregate->getNodeTypeName()
                        . ', which already exist. Please choose a resolution strategy.', 1520014467);
                }
            }

            if ($newNodeType->hasAutoCreatedChildNode($childAggregate->getNodeName())) {
                foreach ($this->contentGraph->findChildAggregates($command->getContentStreamIdentifier(), $childAggregate->getNodeAggregateIdentifier()) as $grandChildAggregate) {
                    $grandChildsNodeType = $this->nodeTypeManager->getNodeType((string)$grandChildAggregate->getNodeTypeName());
                    if (!$newNodeType->allowsGrandchildNodeType($childAggregate->getNodeName(), $grandChildsNodeType)) {
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
     * @param Command\CreateNodeSpecialization $command
     * @throws DimensionSpacePointIsAlreadyOccupied
     * @throws DimensionSpacePointIsNotYetOccupied
     * @throws DimensionSpacePointNotFound
     * @throws DimensionSpacePointIsNoSpecialization
     * @throws ParentsNodeAggregateNotVisibleInDimensionSpacePoint
     */
    public function handleCreateNodeSpecialization(Command\CreateNodeSpecialization $command): void
    {
        $nodeAggregate = $this->getNodeAggregate($command->getContentStreamIdentifier(), $command->getNodeAggregateIdentifier());

        $this->requireDimensionSpacePointToExist($command->getTargetDimensionSpacePoint());
        $this->requireDimensionSpacePointToBeSpecialization($command->getTargetDimensionSpacePoint(), $command->getSourceDimensionSpacePoint());

        $nodeAggregate->requireDimensionSpacePointToBeOccupied($command->getSourceDimensionSpacePoint());
        $nodeAggregate->requireDimensionSpacePointToBeUnoccupied($command->getTargetDimensionSpacePoint());

        $this->requireParentNodesAggregateToBeVisibleInDimensionSpacePoint(
            $command->getContentStreamIdentifier(),
            $command->getNodeAggregateIdentifier(),
            $command->getSourceDimensionSpacePoint(),
            $command->getTargetDimensionSpacePoint()
        );

        $this->nodeEventPublisher->withCommand($command, function () use ($command, $nodeAggregate) {
            $event = new Event\NodeSpecializationWasCreated(
                $command->getContentStreamIdentifier(),
                $command->getNodeAggregateIdentifier(),
                $command->getSourceDimensionSpacePoint(),
                $command->getSpecializationIdentifier(),
                $command->getTargetDimensionSpacePoint(),
                $this->interDimensionalVariationGraph->getSpecializationSet($command->getTargetDimensionSpacePoint(), true, $nodeAggregate->getOccupiedDimensionSpacePoints())
            );
            $this->nodeEventPublisher->publish($nodeAggregate->getStreamName(), $event);
        });
    }


    /**
     * @param Command\CreateNodeGeneralization $command
     * @throws DimensionSpacePointNotFound
     * @throws DimensionSpacePointIsNoGeneralization
     * @throws DimensionSpacePointIsAlreadyOccupied
     * @throws DimensionSpacePointIsNotYetOccupied
     */
    public function handleCreateNodeGeneralization(Command\CreateNodeGeneralization $command): void
    {
        $nodeAggregate = $this->getNodeAggregate($command->getContentStreamIdentifier(), $command->getNodeAggregateIdentifier());

        $this->requireDimensionSpacePointToExist($command->getTargetDimensionSpacePoint());
        $this->requireDimensionSpacePointToBeGeneralization($command->getTargetDimensionSpacePoint(), $command->getSourceDimensionSpacePoint());

        $nodeAggregate->requireDimensionSpacePointToBeOccupied($command->getSourceDimensionSpacePoint());
        $nodeAggregate->requireDimensionSpacePointToBeUnoccupied($command->getTargetDimensionSpacePoint());

        $this->nodeEventPublisher->withCommand($command, function () use ($command, $nodeAggregate) {
            $event = new Event\NodeGeneralizationWasCreated(
                $command->getContentStreamIdentifier(),
                $command->getNodeAggregateIdentifier(),
                $command->getSourceDimensionSpacePoint(),
                $command->getGeneralizationIdentifier(),
                $command->getTargetDimensionSpacePoint(),
                $this->interDimensionalVariationGraph->getSpecializationSet($command->getTargetDimensionSpacePoint(), true, $nodeAggregate->getVisibleInDimensionSpacePoints())
            );

            $this->nodeEventPublisher->publish($nodeAggregate->getStreamName(), $event);
        });
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
     * @throws DimensionSpacePointIsNoGeneralizationException
     */
    protected function requireDimensionSpacePointToBeSpecialization(DimensionSpacePoint $dimensionSpacePoint, DimensionSpacePoint $generalization): void
    {
        if (!$this->interDimensionalVariationGraph->getSpecializationSet($generalization)->contains($dimensionSpacePoint)) {
            throw new DimensionSpace\Exception\DimensionSpacePointIsNoSpecializationException($dimensionSpacePoint . ' is no specialization of ' . $generalization, 1519931770);
        }
    }

    /**
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @param DimensionSpacePoint $specialization
     * @throws DimensionSpacePointIsNoGeneralizationException
     */
    protected function requireDimensionSpacePointToBeGeneralization(DimensionSpacePoint $dimensionSpacePoint, DimensionSpacePoint $specialization): void
    {
        if (!$this->interDimensionalVariationGraph->getSpecializationSet($dimensionSpacePoint)->contains($specialization)) {
            throw new DimensionSpace\Exception\DimensionSpacePointIsNoGeneralizationException($dimensionSpacePoint . ' is no generalization of ' . $dimensionSpacePoint, 1521367710);
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
     * @return NodeAggregate
     * @throws ContentStream\ContentStreamDoesNotExistYet
     */
    protected function getNodeAggregate(ContentStreamIdentifier $contentStreamIdentifier, NodeAggregateIdentifier $nodeAggregateIdentifier): NodeAggregate
    {
        $contentStream = $this->contentStreamRepository->findContentStream($contentStreamIdentifier);
        if (!$contentStream) {
            throw new ContentStream\ContentStreamDoesNotExistYet('The content stream "' . $contentStreamIdentifier . '" to get a node aggregate from does not exist yet.');
        }

        return $contentStream->getNodeAggregate($nodeAggregateIdentifier);
    }
}
