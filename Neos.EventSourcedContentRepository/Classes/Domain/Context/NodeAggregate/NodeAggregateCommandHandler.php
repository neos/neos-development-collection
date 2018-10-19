<?php
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
use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Exception\NodeConstraintException;
use Neos\ContentRepository\Exception\NodeTypeNotFoundException;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream;
use Neos\ContentRepository\DimensionSpace\DimensionSpace;
use Neos\EventSourcedContentRepository\Domain\Context\Node\NodeEventPublisher;
use Neos\EventSourcedContentRepository\Domain\Context\Node\ParentsNodeAggregateNotVisibleInDimensionSpacePoint;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
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
            if ($parentsNodeType->hasAutoCreatedChildNodeWithNodeName($nodeAggregate->getNodeName())
                && $parentsNodeType->getTypeOfAutoCreatedChildNodeWithNodeName($nodeAggregate->getNodeName())->getName() !== (string)$command->getNewNodeTypeName()) {
                throw new NodeConstraintException('Cannot change type of auto created child node' . $nodeAggregate->getNodeName() . ' to ' . $command->getNewNodeTypeName());
            }
            foreach ($this->contentGraph->findParentAggregates($command->getContentStreamIdentifier(), $parentAggregate->getNodeAggregateIdentifier()) as $grandParentAggregate) {
                $grandParentsNodeType = $this->nodeTypeManager->getNodeType((string)$grandParentAggregate->getNodeTypeName());
                if ($grandParentsNodeType->hasAutoCreatedChildNodeWithNodeName($parentAggregate->getNodeName())
                    && !$grandParentsNodeType->allowsGrandchildNodeType((string)$parentAggregate->getNodeName(), $newNodeType)) {
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

            if ($newNodeType->hasAutoCreatedChildNodeWithNodeName($childAggregate->getNodeName())) {
                foreach ($this->contentGraph->findChildAggregates($command->getContentStreamIdentifier(), $childAggregate->getNodeAggregateIdentifier()) as $grandChildAggregate) {
                    $grandChildsNodeType = $this->nodeTypeManager->getNodeType((string)$grandChildAggregate->getNodeTypeName());
                    if (!$newNodeType->allowsGrandchildNodeType((string)$childAggregate->getNodeName(), $grandChildsNodeType)) {
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
                $this->interDimensionalVariationGraph->getSpecializationSet($command->getTargetDimensionSpacePoint(), true, $nodeAggregate->getVisibleDimensionSpacePoints())
            );

            $this->nodeEventPublisher->publish($nodeAggregate->getStreamName(), $event);
        });
    }

    /**
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @throws DimensionSpacePointNotFound
     */
    protected function requireDimensionSpacePointToExist(DimensionSpacePoint $dimensionSpacePoint)
    {
        if (!$this->allowedDimensionSubspace->contains($dimensionSpacePoint)) {
            throw new DimensionSpacePointNotFound(sprintf('%s was not found in the allowed dimension subspace', $dimensionSpacePoint), 1520260137);
        }
    }

    /**
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @param DimensionSpacePoint $generalization
     * @throws DimensionSpacePointIsNoSpecialization
     */
    protected function requireDimensionSpacePointToBeSpecialization(DimensionSpacePoint $dimensionSpacePoint, DimensionSpacePoint $generalization)
    {
        if (!$this->interDimensionalVariationGraph->getSpecializationSet($generalization)->contains($dimensionSpacePoint)) {
            throw new DimensionSpacePointIsNoSpecialization($dimensionSpacePoint . ' is no specialization of ' . $generalization, 1519931770);
        }
    }

    /**
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @param DimensionSpacePoint $specialization
     * @throws DimensionSpacePointIsNoGeneralization
     */
    protected function requireDimensionSpacePointToBeGeneralization(DimensionSpacePoint $dimensionSpacePoint, DimensionSpacePoint $specialization)
    {
        if (!$this->interDimensionalVariationGraph->getSpecializationSet($dimensionSpacePoint)->contains($specialization)) {
            throw new DimensionSpacePointIsNoGeneralization($dimensionSpacePoint . ' is no generalization of ' . $dimensionSpacePoint, 1521367710);
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
    ) {
        $sourceSubgraph = $this->contentGraph->getSubgraphByIdentifier($contentStreamIdentifier, $sourceDimensionSpacePoint);
        $sourceParentNode = $sourceSubgraph->findParentNodeByNodeAggregateIdentifier($nodeAggregateIdentifier);
        if (!$sourceParentNode // the root node is visible in all dimension space points
            || $this->contentGraph->findVisibleDimensionSpacePointsOfNodeAggregate($contentStreamIdentifier, $sourceParentNode->getNodeAggregateIdentifier())
                ->contains($dimensionSpacePoint)) {
            return;
        }

        throw new ParentsNodeAggregateNotVisibleInDimensionSpacePoint('No suitable parent could be found for node "' . $nodeAggregateIdentifier . '" in target dimension space point ' . $dimensionSpacePoint,
            1521322565);
    }

    protected function getNodeAggregate(ContentStreamIdentifier $contentStreamIdentifier, NodeAggregateIdentifier $nodeAggregateIdentifier): NodeAggregate
    {
        $contentStream = $this->contentStreamRepository->findContentStream($contentStreamIdentifier);

        return $contentStream->getNodeAggregate($nodeAggregateIdentifier);
    }
}
