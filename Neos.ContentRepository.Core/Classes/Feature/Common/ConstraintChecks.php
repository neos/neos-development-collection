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

namespace Neos\ContentRepository\Core\Feature\Common;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\Exception\DimensionSpacePointNotFound;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\Feature\NodeVariation\Exception\DimensionSpacePointIsAlreadyOccupied;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyType;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindPrecedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSucceedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Exception\DimensionSpacePointIsNotYetOccupied;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateCurrentlyDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateCurrentlyExists;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateDoesCurrentlyNotCoverDimensionSpacePointSet;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateDoesCurrentlyNotOccupyDimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateIsDescendant;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateIsNoChild;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateIsNoSibling;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateIsRoot;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateIsTethered;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateIsUntethered;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeConstraintException;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeNameIsAlreadyCovered;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeIsAbstract;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeIsNotOfTypeRoot;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeIsOfTypeRoot;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeNotFound;
use Neos\ContentRepository\Core\SharedModel\Exception\ReferenceCannotBeSet;
use Neos\ContentRepository\Core\SharedModel\Exception\RootNodeAggregateTypeIsAlreadyOccupied;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\EventStore\Model\EventStream\ExpectedVersion;

/**
 * @internal implementation details of command handlers
 */
trait ConstraintChecks
{
    use NodeTypeConstraintChecks;

    abstract protected function getAllowedDimensionSubspace(): DimensionSpacePointSet;

    /**
     * @param DimensionSpacePoint $dimensionSpacePoint
     * @throws DimensionSpacePointNotFound
     */
    protected function requireDimensionSpacePointToExist(DimensionSpacePoint $dimensionSpacePoint): void
    {
        if (!$this->getAllowedDimensionSubspace()->contains($dimensionSpacePoint)) {
            throw DimensionSpacePointNotFound::becauseItIsNotWithinTheAllowedDimensionSubspace($dimensionSpacePoint);
        }
    }

    protected function requireNodeTypeToNotBeAbstract(NodeType $nodeType): void
    {
        if ($nodeType->isAbstract()) {
            throw NodeTypeIsAbstract::butWasNotSupposedToBe($nodeType->name);
        }
    }

    /**
     * @param NodeType $nodeType
     * @throws NodeTypeIsNotOfTypeRoot
     */
    protected function requireNodeTypeToBeOfTypeRoot(NodeType $nodeType): void
    {
        if (!$nodeType->isOfType(NodeTypeName::ROOT_NODE_TYPE_NAME)) {
            throw new NodeTypeIsNotOfTypeRoot('Node type "' . $nodeType->name->value . '" is not of type root.', 1541765701);
        }
    }

    /**
     * @param NodeType $nodeType
     * @throws NodeTypeIsOfTypeRoot
     */
    protected function requireNodeTypeToNotBeOfTypeRoot(NodeType $nodeType): void
    {
        if ($nodeType->isOfType(NodeTypeName::ROOT_NODE_TYPE_NAME)) {
            throw new NodeTypeIsOfTypeRoot(
                'Node type "' . $nodeType->name->value . '" is of type root.',
                1541765806
            );
        }
    }

    protected function requireRootNodeTypeToBeUnoccupied(
        ContentGraphInterface $contentGraph,
        NodeTypeName $nodeTypeName
    ): void {
        if ($contentGraph->findRootNodeAggregateByType($nodeTypeName) === null) {
            return;
        }
        throw RootNodeAggregateTypeIsAlreadyOccupied::butWasExpectedNotTo($nodeTypeName);
    }

    /**
     * @param NodeType $nodeType
     * @throws NodeTypeNotFound the configured child nodeType doesnt exist
     */
    protected function requireTetheredDescendantNodeTypesToExist(NodeType $nodeType): void
    {
        foreach ($nodeType->tetheredNodeTypeDefinitions as $tetheredNodeTypeDefinition) {
            $nodeType = $this->requireNodeType($tetheredNodeTypeDefinition->nodeTypeName);
            $this->requireTetheredDescendantNodeTypesToExist($nodeType);
        }
    }

    /**
     * @param NodeType $nodeType
     * @throws NodeTypeIsOfTypeRoot
     */
    protected function requireTetheredDescendantNodeTypesToNotBeOfTypeRoot(NodeType $nodeType): void
    {
        foreach ($nodeType->tetheredNodeTypeDefinitions as $tetheredNodeTypeDefinition) {
            $tetheredChildNodeType = $this->requireNodeType($tetheredNodeTypeDefinition->nodeTypeName);
            if ($tetheredChildNodeType->isOfType(NodeTypeName::ROOT_NODE_TYPE_NAME)) {
                throw new NodeTypeIsOfTypeRoot(
                    'Node type "' . $nodeType->name->value . '" for tethered descendant is of type root.',
                    1541767062
                );
            }
            $this->requireTetheredDescendantNodeTypesToNotBeOfTypeRoot($tetheredChildNodeType);
        }
    }

    /**
     * @throws NodeAggregateIsUntethered
     */
    protected function requireExistingDeclaredTetheredDescendantsToBeTethered(
        ContentGraphInterface $contentGraph,
        NodeAggregate $nodeAggregate,
        NodeType $nodeType
    ): void {
        foreach ($nodeType->tetheredNodeTypeDefinitions as $tetheredNodeTypeDefinition) {
            $tetheredNodeAggregate = $contentGraph->findChildNodeAggregateByName($nodeAggregate->nodeAggregateId, $tetheredNodeTypeDefinition->name);
            if ($tetheredNodeAggregate === null) {
                continue;
            }
            if (!$tetheredNodeAggregate->classification->isTethered()) {
                throw new NodeAggregateIsUntethered(
                    'Node name ' . $tetheredNodeTypeDefinition->name->value . ' is occupied by untethered node aggregate ' . $tetheredNodeAggregate->nodeAggregateId->value,
                    1729592202
                );
            }
            $tetheredNodeType = $this->requireNodeType($tetheredNodeTypeDefinition->nodeTypeName);
            $this->requireExistingDeclaredTetheredDescendantsToBeTethered($contentGraph, $tetheredNodeAggregate, $tetheredNodeType);
        }
    }

    /**
     * @param array|NodeAggregateId[] $parentNodeAggregateIds
     * @throws NodeConstraintException
     */
    protected function requireConstraintsImposedByAncestorsAreMet(
        ContentGraphInterface $contentGraph,
        NodeType $nodeType,
        array $parentNodeAggregateIds,
    ): void {
        foreach ($parentNodeAggregateIds as $parentNodeAggregateId) {
            $parentAggregate = $this->requireProjectedNodeAggregate(
                $contentGraph,
                $parentNodeAggregateId
            );
            if (!$parentAggregate->classification->isTethered()) {
                try {
                    $parentsNodeType = $this->requireNodeType($parentAggregate->nodeTypeName);
                    $this->requireNodeTypeConstraintsImposedByParentToBeMet($parentsNodeType, $nodeType);
                } catch (NodeTypeNotFound $e) {
                    // skip constraint check; Once the parent is changed to be of an available type,
                    // the constraint checks are executed again. See handleChangeNodeAggregateType
                }
            }

            foreach (
                $contentGraph->findParentNodeAggregates(
                    $parentNodeAggregateId
                ) as $grandParentNodeAggregate
            ) {
                /* @var $grandParentNodeAggregate NodeAggregate */
                try {
                    $grandParentsNodeType = $this->requireNodeType($grandParentNodeAggregate->nodeTypeName);
                    $this->requireNodeTypeConstraintsImposedByGrandparentToBeMet(
                        $grandParentsNodeType,
                        $parentAggregate->nodeName,
                        $nodeType
                    );
                } catch (NodeTypeNotFound $e) {
                    // skip constraint check; Once the grandparent is changed to be of an available type,
                    // the constraint checks are executed again. See handleChangeNodeAggregateType
                }
            }
        }
    }

    /**
     * @throws NodeAggregateCurrentlyDoesNotExist
     */
    protected function requireProjectedNodeAggregate(
        ContentGraphInterface $contentGraph,
        NodeAggregateId $nodeAggregateId
    ): NodeAggregate {
        $nodeAggregate = $contentGraph->findNodeAggregateById(
            $nodeAggregateId
        );

        if (!$nodeAggregate) {
            throw new NodeAggregateCurrentlyDoesNotExist(
                'Node aggregate "' . $nodeAggregateId->value . '" does currently not exist.',
                1541678486
            );
        }

        return $nodeAggregate;
    }

    /**
     * @throws NodeAggregateCurrentlyExists
     */
    protected function requireProjectedNodeAggregateToNotExist(
        ContentGraphInterface $contentGraph,
        NodeAggregateId $nodeAggregateId
    ): void {
        $nodeAggregate = $contentGraph->findNodeAggregateById(
            $nodeAggregateId
        );

        if ($nodeAggregate) {
            throw new NodeAggregateCurrentlyExists(
                'Node aggregate "' . $nodeAggregateId->value . '" does currently exist, but should not.',
                1541687645
            );
        }
    }

    /**
     * @throws NodeAggregateCurrentlyDoesNotExist
     */
    public function requireProjectedParentNodeAggregate(
        ContentGraphInterface $contentGraph,
        NodeAggregateId $childNodeAggregateId,
        OriginDimensionSpacePoint $childOriginDimensionSpacePoint
    ): NodeAggregate {
        $parentNodeAggregate = $contentGraph
            ->findParentNodeAggregateByChildOriginDimensionSpacePoint(
                $childNodeAggregateId,
                $childOriginDimensionSpacePoint
            );

        if (!$parentNodeAggregate) {
            throw new NodeAggregateCurrentlyDoesNotExist(
                'Parent node aggregate for ' . $childNodeAggregateId->value
                    . ' does currently not exist in origin dimension space point ' . $childOriginDimensionSpacePoint->toJson()
                    . ' and workspace ' . $contentGraph->getWorkspaceName()->value,
                1645368685
            );
        }

        return $parentNodeAggregate;
    }

    /**
     * @throws NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint
     */
    protected function requireNodeAggregateToCoverDimensionSpacePoint(
        NodeAggregate $nodeAggregate,
        DimensionSpacePoint $dimensionSpacePoint
    ): void {
        if (!$nodeAggregate->coversDimensionSpacePoint($dimensionSpacePoint)) {
            throw new NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint(
                'Node aggregate "' . $nodeAggregate->nodeAggregateId->value
                    . '" does currently not cover dimension space point '
                    . json_encode($dimensionSpacePoint, JSON_THROW_ON_ERROR) . '.',
                1541678877
            );
        }
    }

    /**
     * @throws NodeAggregateDoesCurrentlyNotCoverDimensionSpacePointSet
     */
    protected function requireNodeAggregateToCoverDimensionSpacePoints(
        NodeAggregate $nodeAggregate,
        DimensionSpacePointSet $dimensionSpacePointSet
    ): void {
        if (!$dimensionSpacePointSet->getDifference($nodeAggregate->coveredDimensionSpacePoints)->isEmpty()) {
            throw NodeAggregateDoesCurrentlyNotCoverDimensionSpacePointSet::butWasSupposedTo(
                $nodeAggregate->nodeAggregateId,
                $dimensionSpacePointSet,
                $nodeAggregate->coveredDimensionSpacePoints
            );
        }
    }

    /**
     * @throws NodeAggregateIsRoot
     */
    protected function requireNodeAggregateToNotBeRoot(NodeAggregate $nodeAggregate, ?string $extraReason = '.'): void
    {
        if ($nodeAggregate->classification->isRoot()) {
            throw new NodeAggregateIsRoot(
                'Node aggregate "' . $nodeAggregate->nodeAggregateId->value . '" is classified as root  ' . $extraReason,
                1554586860
            );
        }
    }

    /**
     * @throws NodeAggregateIsTethered
     */
    protected function requireNodeAggregateToBeUntethered(NodeAggregate $nodeAggregate): void
    {
        if ($nodeAggregate->classification->isTethered()) {
            throw new NodeAggregateIsTethered(
                'Node aggregate "' . $nodeAggregate->nodeAggregateId->value . '" is classified as tethered.',
                1554587288
            );
        }
    }

    /**
     * @throws NodeAggregateIsDescendant
     */
    protected function requireNodeAggregateToNotBeDescendant(
        ContentGraphInterface $contentGraph,
        NodeAggregate $nodeAggregate,
        NodeAggregate $referenceNodeAggregate
    ): void {
        if ($nodeAggregate->nodeAggregateId->equals($referenceNodeAggregate->nodeAggregateId)) {
            throw new NodeAggregateIsDescendant(
                'Node aggregate "' . $nodeAggregate->nodeAggregateId->value
                    . '" is descendant of node aggregate "' . $referenceNodeAggregate->nodeAggregateId->value . '"',
                1554971124
            );
        }
        foreach (
            $contentGraph->findChildNodeAggregates(
                $referenceNodeAggregate->nodeAggregateId
            ) as $childReferenceNodeAggregate
        ) {
            $this->requireNodeAggregateToNotBeDescendant(
                $contentGraph,
                $nodeAggregate,
                $childReferenceNodeAggregate
            );
        }
    }

    /**
     * @throws NodeAggregateIsNoSibling
     */
    protected function requireNodeAggregateToBeSibling(
        ContentGraphInterface $contentGraph,
        NodeAggregateId $referenceNodeAggregateId,
        NodeAggregateId $siblingNodeAggregateId,
        DimensionSpacePoint $dimensionSpacePoint,
    ): void {
        $succeedingSiblings = $contentGraph->getSubgraph(
            $dimensionSpacePoint,
            VisibilityConstraints::createEmpty()
        )->findSucceedingSiblingNodes($referenceNodeAggregateId, FindSucceedingSiblingNodesFilter::create());
        if ($succeedingSiblings->toNodeAggregateIds()->contain($siblingNodeAggregateId)) {
            return;
        }

        $precedingSiblings = $contentGraph->getSubgraph(
            $dimensionSpacePoint,
            VisibilityConstraints::createEmpty()
        )->findPrecedingSiblingNodes($referenceNodeAggregateId, FindPrecedingSiblingNodesFilter::create());
        if ($precedingSiblings->toNodeAggregateIds()->contain($siblingNodeAggregateId)) {
            return;
        }

        throw NodeAggregateIsNoSibling::butWasExpectedToBeInDimensionSpacePoint(
            $siblingNodeAggregateId,
            $referenceNodeAggregateId,
            $dimensionSpacePoint
        );
    }

    /**
     * @throws NodeAggregateIsNoChild
     */
    protected function requireNodeAggregateToBeChild(
        ContentGraphInterface $contentGraph,
        NodeAggregateId $childNodeAggregateId,
        NodeAggregateId $parentNodeAggregateId,
        DimensionSpacePoint $dimensionSpacePoint,
    ): void {
        $childNodes = $contentGraph->getSubgraph(
            $dimensionSpacePoint,
            VisibilityConstraints::createEmpty()
        )->findChildNodes($parentNodeAggregateId, FindChildNodesFilter::create());
        if ($childNodes->toNodeAggregateIds()->contain($childNodeAggregateId)) {
            return;
        }

        throw NodeAggregateIsNoChild::butWasExpectedToBeInDimensionSpacePoint(
            $childNodeAggregateId,
            $parentNodeAggregateId,
            $dimensionSpacePoint
        );
    }

    /**
     * @param ?NodeAggregateId $exclusivelyAllowedNodeAggregateId in some cases, aggregates of a given ID
     *      are allowed to cover this name. E.g. in the move case, if a node is moved (back) to its other variants,
     *      the name does not need to be reserved for future variants because the moved node already is that exact one
     * @throws NodeNameIsAlreadyCovered
     */
    protected function requireNodeNameToBeUncovered(
        ContentGraphInterface $contentGraph,
        ?NodeName $nodeName,
        NodeAggregateId $parentNodeAggregateId,
        ?NodeAggregateId $exclusivelyAllowedNodeAggregateId = null,
    ): void {
        if ($nodeName === null) {
            return;
        }

        $childNodeAggregate = $contentGraph->findChildNodeAggregateByName(
            $parentNodeAggregateId,
            $nodeName
        );
        if (
            $childNodeAggregate instanceof NodeAggregate
            && ($exclusivelyAllowedNodeAggregateId === null || !$childNodeAggregate->nodeAggregateId->equals($exclusivelyAllowedNodeAggregateId))
        ) {
            throw new NodeNameIsAlreadyCovered(
                'Node name "' . $nodeName->value . '" is already covered by node aggregate "'
                    . $childNodeAggregate->nodeAggregateId->value . '".'
            );
        }
    }

    /**
     * @throws DimensionSpacePointIsNotYetOccupied
     */
    protected function requireNodeAggregateToOccupyDimensionSpacePoint(
        NodeAggregate $nodeAggregate,
        OriginDimensionSpacePoint $originDimensionSpacePoint
    ): void {
        if (!$nodeAggregate->occupiesDimensionSpacePoint($originDimensionSpacePoint)) {
            throw new DimensionSpacePointIsNotYetOccupied(
                'Dimension space point ' . json_encode($originDimensionSpacePoint, JSON_PARTIAL_OUTPUT_ON_ERROR)
                    . ' is not yet occupied by node aggregate "' . $nodeAggregate->nodeAggregateId->value . '"',
                1552595396
            );
        }
    }

    /**
     * @throws DimensionSpacePointIsAlreadyOccupied
     */
    protected function requireNodeAggregateToNotOccupyDimensionSpacePoint(
        NodeAggregate $nodeAggregate,
        OriginDimensionSpacePoint $originDimensionSpacePoint
    ): void {
        if ($nodeAggregate->occupiesDimensionSpacePoint($originDimensionSpacePoint)) {
            throw new DimensionSpacePointIsAlreadyOccupied(
                'Dimension space point ' . json_encode($originDimensionSpacePoint, JSON_PARTIAL_OUTPUT_ON_ERROR)
                    . ' is already occupied by node aggregate "' . $nodeAggregate->nodeAggregateId->value . '"',
                1552595441
            );
        }
    }

    protected function validateReferenceProperties(
        ReferenceName $referenceName,
        PropertyValuesToWrite $referenceProperties,
        NodeTypeName $nodeTypeName
    ): void {
        $nodeType = $this->requireNodeType($nodeTypeName);

        foreach ($referenceProperties->values as $propertyName => $propertyValue) {
            $referencePropertyConfig = $nodeType->getReferences()[$referenceName->value]['properties'][$propertyName]
                ?? null;

            if (is_null($referencePropertyConfig)) {
                throw ReferenceCannotBeSet::becauseTheReferenceDoesNotDeclareTheProperty(
                    $referenceName,
                    $nodeTypeName,
                    PropertyName::fromString($propertyName)
                );
            }
            $propertyType = PropertyType::fromNodeTypeDeclaration(
                $referencePropertyConfig['type'],
                PropertyName::fromString($propertyName),
                $nodeTypeName
            );
            if (!$propertyType->isMatchedBy($propertyValue)) {
                throw ReferenceCannotBeSet::becauseAPropertyDoesNotMatchTheDeclaredType(
                    $referenceName,
                    $nodeTypeName,
                    PropertyName::fromString($propertyName),
                    is_object($propertyValue) ? get_class($propertyValue) : gettype($propertyValue),
                    $propertyType->value
                );
            }
        }
    }

    protected function getExpectedVersionOfContentStream(
        ContentStreamId $contentStreamId,
    ): ExpectedVersion {
        return ExpectedVersion::fromVersion(
            $this->commandHandlingDependencies->getContentStreamVersion($contentStreamId)
        );
    }

    protected function requireDescendantNodesToNotFallbackToDimensionSpacePointsOtherThan(
        NodeAggregateId $nodeAggregateId,
        ContentGraphInterface $contentGraph,
        DimensionSpacePointsWithAllowedSpecializations $fallbackConstraints,
    ): void {
        foreach ($contentGraph->findChildNodeAggregates($nodeAggregateId) as $childNodeAggregate) {
            foreach ($childNodeAggregate->occupiedDimensionSpacePoints as $occupiedDimensionSpacePoint) {
                if (!$fallbackConstraints->constraint($occupiedDimensionSpacePoint->toDimensionSpacePoint())) {
                    continue;
                }
                $disallowedFallbacks = $childNodeAggregate
                    ->getCoverageByOccupant($occupiedDimensionSpacePoint)
                    // exclude the occupied e.g. no- fallback dimension space point itself
                    ->getDifference(DimensionSpacePointSet::fromArray([$occupiedDimensionSpacePoint->toDimensionSpacePoint()]))
                    // exclude all dimension space points that are still allowed to fall back after the operation
                    ->getDifference($fallbackConstraints->getAllowedSpecializations($occupiedDimensionSpacePoint));
                if (!$disallowedFallbacks->isEmpty()) {
                    throw new NodeAggregateDoesCurrentlyNotOccupyDimensionSpacePoint(sprintf('Descendant Node %s in dimensions %s must not fallback to dimension %s which will be removed.', $childNodeAggregate->nodeAggregateId, $disallowedFallbacks->toJson(), $occupiedDimensionSpacePoint->toJson()));
                }
            }
            $this->requireDescendantNodesToNotFallbackToDimensionSpacePointsOtherThan($childNodeAggregate->nodeAggregateId, $contentGraph, $fallbackConstraints);
        }
    }
}
