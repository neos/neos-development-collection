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

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\Exception\DimensionSpacePointNotFound;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamDoesNotExistYet;
use Neos\ContentRepository\Core\SharedModel\Exception\DimensionSpacePointIsNotYetOccupied;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateCurrentlyDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateCurrentlyExists;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateDoesCurrentlyNotCoverDimensionSpacePointSet;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateIsDescendant;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateIsRoot;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateIsTethered;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregatesTypeIsAmbiguous;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeConstraintException;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeNameIsAlreadyCovered;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeNameIsAlreadyOccupied;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeIsAbstract;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeIsNotOfTypeRoot;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeIsOfTypeRoot;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeNotFound;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeNotFoundException;
use Neos\ContentRepository\Core\SharedModel\Exception\ReferenceCannotBeSet;
use Neos\ContentRepository\Core\Feature\NodeDisabling\Exception\NodeAggregateCurrentlyDisablesDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeDisabling\Exception\NodeAggregateCurrentlyDoesNotDisableDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeVariation\Exception\DimensionSpacePointIsAlreadyOccupied;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyType;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTypeConstraintsWithSubNodeTypes;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * @internal implementation details of command handlers
 */
trait ConstraintChecks
{
    abstract protected function getNodeTypeManager(): NodeTypeManager;

    abstract protected function getAllowedDimensionSubspace(): DimensionSpacePointSet;

    /**
     * @param ContentStreamId $contentStreamId
     * @throws ContentStreamDoesNotExistYet
     */
    protected function requireContentStreamToExist(
        ContentStreamId $contentStreamId,
        ContentRepository $contentRepository
    ): void {
        if (!$contentRepository->getContentStreamFinder()->hasContentStream($contentStreamId)) {
            throw new ContentStreamDoesNotExistYet(
                'Content stream "' . $contentStreamId . '" does not exist yet.',
                1521386692
            );
        }
    }

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

    /**
     * @param NodeTypeName $nodeTypeName
     * @return NodeType
     * @throws NodeTypeNotFound
     */
    protected function requireNodeType(NodeTypeName $nodeTypeName): NodeType
    {
        try {
            return $this->getNodeTypeManager()->getNodeType((string)$nodeTypeName);
        } catch (NodeTypeNotFoundException $exception) {
            throw new NodeTypeNotFound(
                'Node type "' . $nodeTypeName . '" is unknown to the node type manager.',
                1541671070
            );
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
            throw new NodeTypeIsNotOfTypeRoot('Node type "' . $nodeType . '" is not of type root.', 1541765701);
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
                'Node type "' . $nodeType->name . '" is of type root.',
                1541765806
            );
        }
    }

    /**
     * @param NodeType $nodeType
     * @throws NodeTypeNotFoundException
     */
    protected function requireTetheredDescendantNodeTypesToExist(NodeType $nodeType): void
    {
        foreach ($nodeType->getAutoCreatedChildNodes() as $childNodeType) {
            $this->requireTetheredDescendantNodeTypesToExist($childNodeType);
        }
    }

    /**
     * @param NodeType $nodeType
     * @throws NodeTypeIsOfTypeRoot
     */
    protected function requireTetheredDescendantNodeTypesToNotBeOfTypeRoot(NodeType $nodeType): void
    {
        foreach ($nodeType->getAutoCreatedChildNodes() as $tetheredChildNodeType) {
            if ($tetheredChildNodeType->isOfType(NodeTypeName::ROOT_NODE_TYPE_NAME)) {
                throw new NodeTypeIsOfTypeRoot(
                    'Node type "' . $nodeType->name . '" for tethered descendant is of type root.',
                    1541767062
                );
            }
            $this->requireTetheredDescendantNodeTypesToNotBeOfTypeRoot($tetheredChildNodeType);
        }
    }

    protected function requireNodeTypeToDeclareProperty(NodeTypeName $nodeTypeName, PropertyName $propertyName): void
    {
        $nodeType = $this->getNodeTypeManager()->getNodeType((string) $nodeTypeName);
        if (!isset($nodeType->getProperties()[(string)$propertyName])) {
        }
    }

    protected function requireNodeTypeToDeclareReference(NodeTypeName $nodeTypeName, ReferenceName $propertyName): void
    {
        $nodeType = $this->getNodeTypeManager()->getNodeType((string)$nodeTypeName);
        if (isset($nodeType->getProperties()[(string)$propertyName])) {
            $propertyType = $nodeType->getPropertyType((string)$propertyName);
            if ($propertyType === 'reference' || $propertyType === 'references') {
                return;
            }
        }
        throw ReferenceCannotBeSet::becauseTheNodeTypeDoesNotDeclareIt($propertyName, $nodeTypeName);
    }

    protected function requireNodeTypeToAllowNodesOfTypeInReference(
        NodeTypeName $nodeTypeName,
        ReferenceName $referenceName,
        NodeTypeName $nodeTypeNameInQuestion
    ): void {
        $nodeType = $this->getNodeTypeManager()->getNodeType((string)$nodeTypeName);
        $propertyDeclaration = $nodeType->getProperties()[(string)$referenceName] ?? null;
        if (is_null($propertyDeclaration)) {
            throw ReferenceCannotBeSet::becauseTheNodeTypeDoesNotDeclareIt($referenceName, $nodeTypeName);
        }
        if (isset($propertyDeclaration['constraints']['nodeTypes'])) {
            $nodeTypeConstraints = NodeTypeConstraintsWithSubNodeTypes::createFromNodeTypeDeclaration(
                $propertyDeclaration['constraints']['nodeTypes'],
                $this->getNodeTypeManager()
            );
            if (!$nodeTypeConstraints->matches($nodeTypeNameInQuestion)) {
                throw ReferenceCannotBeSet::becauseTheConstraintsAreNotMatched(
                    $referenceName,
                    $nodeTypeName,
                    $nodeTypeNameInQuestion
                );
            }
        }
    }

    /**
     * NodeType and NodeName must belong together to the same node, which is the to-be-checked one.
     *
     * @param ContentStreamId $contentStreamId
     * @param NodeType $nodeType
     * @param NodeName|null $nodeName
     * @param array|NodeAggregateId[] $parentNodeAggregateIds
     * @throws NodeConstraintException
     */
    protected function requireConstraintsImposedByAncestorsAreMet(
        ContentStreamId $contentStreamId,
        NodeType $nodeType,
        ?NodeName $nodeName,
        array $parentNodeAggregateIds,
        ContentRepository $contentRepository
    ): void {
        foreach ($parentNodeAggregateIds as $parentNodeAggregateId) {
            $parentAggregate = $this->requireProjectedNodeAggregate(
                $contentStreamId,
                $parentNodeAggregateId,
                $contentRepository
            );
            try {
                $parentsNodeType = $this->requireNodeType($parentAggregate->nodeTypeName);
                $this->requireNodeTypeConstraintsImposedByParentToBeMet($parentsNodeType, $nodeName, $nodeType);
            } catch (NodeTypeNotFound $e) {
                // skip constraint check; Once the parent is changed to be of an available type,
                // the constraint checks are executed again. See handleChangeNodeAggregateType
            }

            foreach (
                $contentRepository->getContentGraph()->findParentNodeAggregates(
                    $contentStreamId,
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
                    // skip constraint check; Once the grand parent is changed to be of an available type,
                    // the constraint checks are executed again. See handleChangeNodeAggregateType
                }
            }
        }
    }

    /**
     * @throws NodeTypeNotFoundException
     * @throws NodeConstraintException
     */
    protected function requireNodeTypeConstraintsImposedByParentToBeMet(
        NodeType $parentsNodeType,
        ?NodeName $nodeName,
        NodeType $nodeType
    ): void {
        // !!! IF YOU ADJUST THIS METHOD, also adjust the method below.
        if (!$parentsNodeType->allowsChildNodeType($nodeType)) {
            throw new NodeConstraintException(
                'Node type "' . $nodeType . '" is not allowed for child nodes of type '
                    . $parentsNodeType->name
            );
        }
        if (
            $nodeName
            && $parentsNodeType->hasAutoCreatedChildNode($nodeName)
            && !$parentsNodeType->getTypeOfAutoCreatedChildNode($nodeName)?->name->equals($nodeType->name)
        ) {
            throw new NodeConstraintException(
                'Node type "' . $nodeType . '" does not match configured "'
                    . $parentsNodeType->getTypeOfAutoCreatedChildNode($nodeName)?->name
                    . '" for auto created child nodes for parent type "' . $parentsNodeType
                    . '" with name "' . $nodeName . '"'
            );
        }
    }

    protected function areNodeTypeConstraintsImposedByParentValid(
        NodeType $parentsNodeType,
        ?NodeName $nodeName,
        NodeType $nodeType
    ): bool {
        // !!! IF YOU ADJUST THIS METHOD, also adjust the method above.
        if (!$parentsNodeType->allowsChildNodeType($nodeType)) {
            return false;
        }
        if (
            $nodeName
            && $parentsNodeType->hasAutoCreatedChildNode($nodeName)
            && !$parentsNodeType->getTypeOfAutoCreatedChildNode($nodeName)?->name->equals($nodeType->name)
        ) {
            return false;
        }
        return true;
    }

    /**
     * @throws NodeConstraintException
     */
    protected function requireNodeTypeConstraintsImposedByGrandparentToBeMet(
        NodeType $grandParentsNodeType,
        ?NodeName $parentNodeName,
        NodeType $nodeType
    ): void {
        if (
            !$this->areNodeTypeConstraintsImposedByGrandparentValid(
                $grandParentsNodeType,
                $parentNodeName,
                $nodeType
            )
        ) {
            throw new NodeConstraintException(
                'Node type "' . $nodeType . '" is not allowed below tethered child nodes "' . $parentNodeName
                    . '" of nodes of type "' . $grandParentsNodeType->name . '"',
                1520011791
            );
        }
    }

    protected function areNodeTypeConstraintsImposedByGrandparentValid(
        NodeType $grandParentsNodeType,
        ?NodeName $parentNodeName,
        NodeType $nodeType
    ): bool {
        if (
            $parentNodeName
            && $grandParentsNodeType->hasAutoCreatedChildNode($parentNodeName)
            && !$grandParentsNodeType->allowsGrandchildNodeType((string)$parentNodeName, $nodeType)
        ) {
            return false;
        }
        return true;
    }

    /**
     * @throws NodeAggregatesTypeIsAmbiguous
     * @throws NodeAggregateCurrentlyDoesNotExist
     */
    protected function requireProjectedNodeAggregate(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
        ContentRepository $contentRepository
    ): NodeAggregate {
        $nodeAggregate = $contentRepository->getContentGraph()->findNodeAggregateById(
            $contentStreamId,
            $nodeAggregateId
        );

        if (!$nodeAggregate) {
            throw new NodeAggregateCurrentlyDoesNotExist(
                'Node aggregate "' . $nodeAggregateId . '" does currently not exist.',
                1541678486
            );
        }

        return $nodeAggregate;
    }

    /**
     * @throws NodeAggregatesTypeIsAmbiguous
     * @throws NodeAggregateCurrentlyExists
     */
    protected function requireProjectedNodeAggregateToNotExist(
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
        ContentRepository $contentRepository
    ): void {
        $nodeAggregate = $contentRepository->getContentGraph()->findNodeAggregateById(
            $contentStreamId,
            $nodeAggregateId
        );

        if ($nodeAggregate) {
            throw new NodeAggregateCurrentlyExists(
                'Node aggregate "' . $nodeAggregateId . '" does currently exist, but should not.',
                1541687645
            );
        }
    }

    /**
     * @throws NodeAggregateCurrentlyDoesNotExist
     */
    public function requireProjectedParentNodeAggregate(
        ContentStreamId $contentStreamId,
        NodeAggregateId $childNodeAggregateId,
        OriginDimensionSpacePoint $childOriginDimensionSpacePoint,
        ContentRepository $contentRepository
    ): NodeAggregate {
        $parentNodeAggregate = $contentRepository->getContentGraph()
            ->findParentNodeAggregateByChildOriginDimensionSpacePoint(
                $contentStreamId,
                $childNodeAggregateId,
                $childOriginDimensionSpacePoint
            );

        if (!$parentNodeAggregate) {
            throw new NodeAggregateCurrentlyDoesNotExist(
                'Parent node aggregate for ' . $childNodeAggregateId
                    . ' does currently not exist in origin dimension space point ' . $childOriginDimensionSpacePoint
                    . ' and content stream ' . $contentStreamId,
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
                'Node aggregate "' . $nodeAggregate->nodeAggregateId
                    . '" does currently not cover dimension space point '
                    . json_encode($dimensionSpacePoint) . '.',
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
                'Node aggregate "' . $nodeAggregate->nodeAggregateId . '" is classified as root  ' . $extraReason,
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
                'Node aggregate "' . $nodeAggregate->nodeAggregateId . '" is classified as tethered.',
                1554587288
            );
        }
    }

    /**
     * @throws NodeAggregateIsDescendant
     */
    protected function requireNodeAggregateToNotBeDescendant(
        ContentStreamId $contentStreamId,
        NodeAggregate $nodeAggregate,
        NodeAggregate $referenceNodeAggregate,
        ContentRepository $contentRepository
    ): void {
        if ($nodeAggregate->nodeAggregateId->equals($referenceNodeAggregate->nodeAggregateId)) {
            throw new NodeAggregateIsDescendant(
                'Node aggregate "' . $nodeAggregate->nodeAggregateId
                    . '" is descendant of node aggregate "' . $referenceNodeAggregate->nodeAggregateId . '"',
                1554971124
            );
        }
        foreach (
            $contentRepository->getContentGraph()->findChildNodeAggregates(
                $contentStreamId,
                $referenceNodeAggregate->nodeAggregateId
            ) as $childReferenceNodeAggregate
        ) {
            $this->requireNodeAggregateToNotBeDescendant(
                $contentStreamId,
                $nodeAggregate,
                $childReferenceNodeAggregate,
                $contentRepository
            );
        }
    }

    /**
     * @throws NodeNameIsAlreadyOccupied
     */
    protected function requireNodeNameToBeUnoccupied(
        ContentStreamId $contentStreamId,
        ?NodeName $nodeName,
        NodeAggregateId $parentNodeAggregateId,
        OriginDimensionSpacePoint $parentOriginDimensionSpacePoint,
        DimensionSpacePointSet $dimensionSpacePoints,
        ContentRepository $contentRepository
    ): void {
        if ($nodeName === null) {
            return;
        }
        $dimensionSpacePointsOccupiedByChildNodeName = $contentRepository->getContentGraph()
            ->getDimensionSpacePointsOccupiedByChildNodeName(
                $contentStreamId,
                $nodeName,
                $parentNodeAggregateId,
                $parentOriginDimensionSpacePoint,
                $dimensionSpacePoints
            );
        if (count($dimensionSpacePointsOccupiedByChildNodeName) > 0) {
            throw new NodeNameIsAlreadyOccupied(
                'Child node name "' . $nodeName . '" is already occupied for parent "'
                    . $parentNodeAggregateId . '" in dimension space points '
                    . $dimensionSpacePointsOccupiedByChildNodeName
            );
        }
    }

    /**
     * @throws NodeNameIsAlreadyCovered
     */
    protected function requireNodeNameToBeUncovered(
        ContentStreamId $contentStreamId,
        ?NodeName $nodeName,
        NodeAggregateId $parentNodeAggregateId,
        DimensionSpacePointSet $dimensionSpacePointsToBeCovered,
        ContentRepository $contentRepository
    ): void {
        if ($nodeName === null) {
            return;
        }
        $childNodeAggregates = $contentRepository->getContentGraph()->findChildNodeAggregatesByName(
            $contentStreamId,
            $parentNodeAggregateId,
            $nodeName
        );
        foreach ($childNodeAggregates as $childNodeAggregate) {
            /* @var $childNodeAggregate NodeAggregate */
            $alreadyCoveredDimensionSpacePoints = $childNodeAggregate->coveredDimensionSpacePoints
                ->getIntersection($dimensionSpacePointsToBeCovered);
            if (!$alreadyCoveredDimensionSpacePoints->isEmpty()) {
                throw new NodeNameIsAlreadyCovered(
                    'Node name "' . $nodeName . '" is already covered in dimension space points '
                        . $alreadyCoveredDimensionSpacePoints . ' by node aggregate "'
                        . $childNodeAggregate->nodeAggregateId . '".'
                );
            }
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
                'Dimension space point ' . json_encode($originDimensionSpacePoint)
                    . ' is not yet occupied by node aggregate "' . $nodeAggregate->nodeAggregateId . '"',
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
                'Dimension space point ' . json_encode($originDimensionSpacePoint)
                    . ' is already occupied by node aggregate "' . $nodeAggregate->nodeAggregateId . '"',
                1552595441
            );
        }
    }

    /**
     * @throws NodeAggregateCurrentlyDoesNotDisableDimensionSpacePoint
     */
    protected function requireNodeAggregateToDisableDimensionSpacePoint(
        NodeAggregate $nodeAggregate,
        DimensionSpacePoint $dimensionSpacePoint
    ): void {
        if (!$nodeAggregate->disablesDimensionSpacePoint($dimensionSpacePoint)) {
            throw new NodeAggregateCurrentlyDoesNotDisableDimensionSpacePoint(
                'Node aggregate "' . $nodeAggregate->nodeAggregateId
                    . '" currently does not disable dimension space point '
                    . json_encode($dimensionSpacePoint) . '.',
                1557735431
            );
        }
    }

    /**
     * @throws NodeAggregateCurrentlyDisablesDimensionSpacePoint
     */
    protected function requireNodeAggregateToNotDisableDimensionSpacePoint(
        NodeAggregate $nodeAggregate,
        DimensionSpacePoint $dimensionSpacePoint
    ): void {
        if ($nodeAggregate->disablesDimensionSpacePoint($dimensionSpacePoint)) {
            throw new NodeAggregateCurrentlyDisablesDimensionSpacePoint(
                'Node aggregate "' . $nodeAggregate->nodeAggregateId
                    . '" currently disables dimension space point ' . json_encode($dimensionSpacePoint) . '.',
                1555179563
            );
        }
    }

    protected function validateReferenceProperties(
        ReferenceName $referenceName,
        PropertyValuesToWrite $referenceProperties,
        NodeTypeName $nodeTypeName
    ): void {
        $nodeType = $this->nodeTypeManager->getNodeType((string)$nodeTypeName);

        foreach ($referenceProperties->values as $propertyName => $propertyValue) {
            $referencePropertyConfig = $nodeType->getProperties()[(string)$referenceName]['properties'][$propertyName]
                ?? null;

            if (is_null($referencePropertyConfig)) {
                throw ReferenceCannotBeSet::becauseTheItDoesNotDeclareAProperty(
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
}
