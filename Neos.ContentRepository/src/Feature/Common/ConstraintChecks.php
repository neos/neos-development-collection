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

namespace Neos\ContentRepository\Feature\Common;

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\Exception\DimensionSpacePointNotFound;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\NodeType\NodeType;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeName;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeManager;
use Neos\ContentRepository\Feature\ContentStreamRepository;
use Neos\ContentRepository\Feature\Common\Exception\ContentStreamDoesNotExistYet;
use Neos\ContentRepository\Feature\NodeVariation\Exception\DimensionSpacePointIsAlreadyOccupied;
use Neos\ContentRepository\Feature\Common\Exception\DimensionSpacePointIsNotYetOccupied;
use Neos\ContentRepository\Feature\NodeDisabling\Exception\NodeAggregateCurrentlyDisablesDimensionSpacePoint;
use Neos\ContentRepository\Feature\NodeDisabling\Exception\NodeAggregateCurrentlyDoesNotDisableDimensionSpacePoint;
use Neos\ContentRepository\Feature\Common\Exception\NodeAggregateCurrentlyDoesNotExist;
use Neos\ContentRepository\Feature\Common\Exception\NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint;
use Neos\ContentRepository\Feature\Common\Exception\NodeAggregateDoesCurrentlyNotCoverDimensionSpacePointSet;
use Neos\ContentRepository\Feature\Common\Exception\NodeAggregateIsDescendant;
use Neos\ContentRepository\Feature\Common\Exception\NodeAggregateIsRoot;
use Neos\ContentRepository\Feature\Common\Exception\NodeAggregateIsTethered;
use Neos\ContentRepository\Feature\Common\Exception\NodeAggregatesTypeIsAmbiguous;
use Neos\ContentRepository\Feature\Common\Exception\NodeAggregateCurrentlyExists;
use Neos\ContentRepository\Feature\Common\Exception\NodeNameIsAlreadyCovered;
use Neos\ContentRepository\Feature\Common\Exception\NodeNameIsAlreadyOccupied;
use Neos\ContentRepository\Feature\Common\Exception\NodeTypeIsAbstract;
use Neos\ContentRepository\Feature\Common\Exception\NodeTypeIsNotOfTypeRoot;
use Neos\ContentRepository\Feature\Common\Exception\NodeTypeIsOfTypeRoot;
use Neos\ContentRepository\Feature\Common\Exception\NodeTypeNotFound;
use Neos\ContentRepository\Feature\Common\Exception\ReferenceCannotBeSet;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeConstraintsFactory;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\ContentRepository\SharedModel\Node\ReadableNodeAggregateInterface;
use Neos\ContentRepository\Projection\Content\ContentGraphInterface;
use Neos\ContentRepository\SharedModel\Node\PropertyName;

trait ConstraintChecks
{
    abstract protected function getContentGraph(): ContentGraphInterface;

    abstract protected function getContentStreamRepository(): ContentStreamRepository;

    abstract protected function getNodeTypeManager(): NodeTypeManager;

    abstract protected function getAllowedDimensionSubspace(): DimensionSpacePointSet;

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @throws ContentStreamDoesNotExistYet
     */
    protected function requireContentStreamToExist(ContentStreamIdentifier $contentStreamIdentifier): void
    {
        $contentStream = $this->getContentStreamRepository()->findContentStream($contentStreamIdentifier);
        if (!$contentStream) {
            throw new ContentStreamDoesNotExistYet(
                'Content stream "' . $contentStreamIdentifier . " does not exist yet.",
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
            throw NodeTypeIsAbstract::butWasNotSupposedToBe(NodeTypeName::fromString($nodeType->getName()));
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
                'Node type "' . $nodeType->getName() . '" is of type root.',
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
                    'Node type "' . $nodeType->getName() . '" for tethered descendant is of type root.',
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

    protected function requireNodeTypeToDeclareReference(NodeTypeName $nodeTypeName, PropertyName $propertyName): void
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
        PropertyName $referenceName,
        NodeTypeName $nodeTypeNameInQuestion
    ): void {
        $nodeType = $this->getNodeTypeManager()->getNodeType((string)$nodeTypeName);
        $nodeTypeInQuestion = $this->getNodeTypeManager()->getNodeType((string)$nodeTypeNameInQuestion);
        $propertyDeclaration = $nodeType->getProperties()[(string)$referenceName] ?? null;
        if (is_null($propertyDeclaration)) {
            throw ReferenceCannotBeSet::becauseTheNodeTypeDoesNotDeclareIt($referenceName, $nodeTypeName);
        }
        if (isset($propertyDeclaration['constraints']['nodeTypes'])) {
            $nodeTypeConstraints = NodeTypeConstraintsFactory::createFromNodeTypeDeclaration(
                $propertyDeclaration['constraints']['nodeTypes']
            );

            $constraintCheckClosure = function (NodeType $nodeType) use (
                $nodeTypeConstraints,
                $referenceName,
                $nodeTypeName,
                $nodeTypeNameInQuestion
            ) {
                foreach ($nodeTypeConstraints->explicitlyAllowedNodeTypeNames as $allowedNodeTypeName) {
                    if ($allowedNodeTypeName->equals(NodeTypeName::fromString($nodeType->getName()))) {
                        return false;
                    }
                }
                foreach ($nodeTypeConstraints->explicitlyDisallowedNodeTypeNames as $disallowedNodeTypeName) {
                    if ($disallowedNodeTypeName->equals(NodeTypeName::fromString($nodeType->getName()))) {
                        throw ReferenceCannotBeSet::becauseTheConstraintsAreNotMatched(
                            $referenceName,
                            $nodeTypeName,
                            $nodeTypeNameInQuestion
                        );
                    }
                }
                return true;
            };
            $this->traverseNodeTypeTreeBreadthFirst([$nodeTypeInQuestion], $constraintCheckClosure);
        }
    }

    /**
     * @param array<int,NodeType> $nodeTypes
     */
    private function traverseNodeTypeTreeBreadthFirst(array $nodeTypes, \Closure $closure): bool
    {
        $nextLevelNodeTypes = [];
        foreach ($nodeTypes as $nodeType) {
            $continue = $closure($nodeType);
            if (!$continue) {
                return false;
            }
            $nextLevelNodeTypes = array_merge(
                $nextLevelNodeTypes,
                $this->nodeTypeManager->getSubNodeTypes($nodeType->getName())
            );
        }

        $this->traverseNodeTypeTreeBreadthFirst($nextLevelNodeTypes, $closure);

        return true;
    }

    /**
     * NodeType and NodeName must belong together to the same node, which is the to-be-checked one.
     *
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeType $nodeType
     * @param NodeName|null $nodeName
     * @param array|NodeAggregateIdentifier[] $parentNodeAggregateIdentifiers
     * @throws NodeConstraintException
     */
    protected function requireConstraintsImposedByAncestorsAreMet(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeType $nodeType,
        ?NodeName $nodeName,
        array $parentNodeAggregateIdentifiers
    ): void {
        foreach ($parentNodeAggregateIdentifiers as $parentNodeAggregateIdentifier) {
            $parentAggregate = $this->requireProjectedNodeAggregate(
                $contentStreamIdentifier,
                $parentNodeAggregateIdentifier
            );
            try {
                $parentsNodeType = $this->requireNodeType($parentAggregate->getNodeTypeName());
                $this->requireNodeTypeConstraintsImposedByParentToBeMet($parentsNodeType, $nodeName, $nodeType);
            } catch (NodeTypeNotFound $e) {
                // skip constraint check; Once the parent is changed to be of an available type,
                // the constraint checks are executed again. See handleChangeNodeAggregateType
            }

            foreach (
                $this->getContentGraph()->findParentNodeAggregates(
                    $contentStreamIdentifier,
                    $parentNodeAggregateIdentifier
                ) as $grandParentNodeAggregate
            ) {
                try {
                    $grandParentsNodeType = $this->requireNodeType($grandParentNodeAggregate->getNodeTypeName());
                    $this->requireNodeTypeConstraintsImposedByGrandparentToBeMet(
                        $grandParentsNodeType,
                        $parentAggregate->getNodeName(),
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
                    . $parentsNodeType->getName()
            );
        }
        if (
            $nodeName
            && $parentsNodeType->hasAutoCreatedChildNode($nodeName)
            && $parentsNodeType->getTypeOfAutoCreatedChildNode($nodeName)?->getName() !== $nodeType->getName()
        ) {
            throw new NodeConstraintException(
                'Node type "' . $nodeType . '" does not match configured "'
                    . $parentsNodeType->getTypeOfAutoCreatedChildNode($nodeName)?->getName()
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
            && $parentsNodeType->getTypeOfAutoCreatedChildNode($nodeName)?->getName() !== $nodeType->getName()
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
                    . '" of nodes of type "' . $grandParentsNodeType->getName() . '"',
                1520011791
            );
        }
    }

    protected function areNodeTypeConstraintsImposedByGrandparentValid(
        NodeType $grandParentsNodeType,
        ?NodeName $parentNodeName,
        NodeType $nodeType
    ): bool {
        // WORKAROUND: $nodeType->hasAutoCreatedChildNode() is missing the "initialize" call,
        // so we need to trigger another method beforehand.
        $grandParentsNodeType->getFullConfiguration();
        $nodeType->getFullConfiguration();

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
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier
    ): ReadableNodeAggregateInterface {
        $nodeAggregate = $this->getContentGraph()->findNodeAggregateByIdentifier(
            $contentStreamIdentifier,
            $nodeAggregateIdentifier
        );

        if (!$nodeAggregate) {
            throw new NodeAggregateCurrentlyDoesNotExist(
                'Node aggregate "' . $nodeAggregateIdentifier . '" does currently not exist.',
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
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $nodeAggregateIdentifier
    ): void {
        $nodeAggregate = $this->getContentGraph()->findNodeAggregateByIdentifier(
            $contentStreamIdentifier,
            $nodeAggregateIdentifier
        );

        if ($nodeAggregate) {
            throw new NodeAggregateCurrentlyExists(
                'Node aggregate "' . $nodeAggregateIdentifier . '" does currently exist, but should not.',
                1541687645
            );
        }
    }

    /**
     * @throws NodeAggregateCurrentlyDoesNotExist
     */
    public function requireProjectedParentNodeAggregate(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeAggregateIdentifier $childNodeAggregateIdentifier,
        OriginDimensionSpacePoint $childOriginDimensionSpacePoint
    ): ReadableNodeAggregateInterface {
        $parentNodeAggregate = $this->getContentGraph()->findParentNodeAggregateByChildOriginDimensionSpacePoint(
            $contentStreamIdentifier,
            $childNodeAggregateIdentifier,
            $childOriginDimensionSpacePoint
        );

        if (!$parentNodeAggregate) {
            throw new NodeAggregateCurrentlyDoesNotExist(
                'Parent node aggregate for ' . $childNodeAggregateIdentifier
                    . ' does currently not exist in origin dimension space point ' . $childOriginDimensionSpacePoint
                    . ' and content stream ' . $contentStreamIdentifier,
                1645368685
            );
        }

        return $parentNodeAggregate;
    }

    /**
     * @throws NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint
     */
    protected function requireNodeAggregateToCoverDimensionSpacePoint(
        ReadableNodeAggregateInterface $nodeAggregate,
        DimensionSpacePoint $dimensionSpacePoint
    ): void {
        if (!$nodeAggregate->coversDimensionSpacePoint($dimensionSpacePoint)) {
            throw new NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint(
                'Node aggregate "' . $nodeAggregate->getIdentifier()
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
        ReadableNodeAggregateInterface $nodeAggregate,
        DimensionSpacePointSet $dimensionSpacePointSet
    ): void {
        if (!$dimensionSpacePointSet->getDifference($nodeAggregate->getCoveredDimensionSpacePoints())->isEmpty()) {
            throw NodeAggregateDoesCurrentlyNotCoverDimensionSpacePointSet::butWasSupposedTo(
                $nodeAggregate->getIdentifier(),
                $dimensionSpacePointSet,
                $nodeAggregate->getCoveredDimensionSpacePoints()
            );
        }
    }

    /**
     * @throws NodeAggregateIsRoot
     */
    protected function requireNodeAggregateToNotBeRoot(ReadableNodeAggregateInterface $nodeAggregate): void
    {
        if ($nodeAggregate->isRoot()) {
            throw new NodeAggregateIsRoot(
                'Node aggregate "' . $nodeAggregate->getIdentifier() . '" is classified as root.',
                1554586860
            );
        }
    }

    /**
     * @throws NodeAggregateIsTethered
     */
    protected function requireNodeAggregateToBeUntethered(ReadableNodeAggregateInterface $nodeAggregate): void
    {
        if ($nodeAggregate->isTethered()) {
            throw new NodeAggregateIsTethered(
                'Node aggregate "' . $nodeAggregate->getIdentifier() . '" is classified as tethered.',
                1554587288
            );
        }
    }

    /**
     * @throws NodeAggregateIsDescendant
     */
    protected function requireNodeAggregateToNotBeDescendant(
        ContentStreamIdentifier $contentStreamIdentifier,
        ReadableNodeAggregateInterface $nodeAggregate,
        ReadableNodeAggregateInterface $referenceNodeAggregate
    ): void {
        if ($nodeAggregate->getIdentifier()->equals($referenceNodeAggregate->getIdentifier())) {
            throw new NodeAggregateIsDescendant(
                'Node aggregate "' . $nodeAggregate->getIdentifier()
                    . '" is descendant of node aggregate "' . $referenceNodeAggregate->getIdentifier() . '"',
                1554971124
            );
        }
        foreach (
            $this->getContentGraph()->findChildNodeAggregates(
                $contentStreamIdentifier,
                $referenceNodeAggregate->getIdentifier()
            ) as $childReferenceNodeAggregate
        ) {
            $this->requireNodeAggregateToNotBeDescendant(
                $contentStreamIdentifier,
                $nodeAggregate,
                $childReferenceNodeAggregate
            );
        }
    }

    /**
     * @throws NodeNameIsAlreadyOccupied
     */
    protected function requireNodeNameToBeUnoccupied(
        ContentStreamIdentifier $contentStreamIdentifier,
        ?NodeName $nodeName,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        OriginDimensionSpacePoint $parentOriginDimensionSpacePoint,
        DimensionSpacePointSet $dimensionSpacePoints
    ): void {
        if ($nodeName === null) {
            return;
        }
        $dimensionSpacePointsOccupiedByChildNodeName = $this->getContentGraph()
            ->getDimensionSpacePointsOccupiedByChildNodeName(
                $contentStreamIdentifier,
                $nodeName,
                $parentNodeAggregateIdentifier,
                $parentOriginDimensionSpacePoint,
                $dimensionSpacePoints
            );
        if (count($dimensionSpacePointsOccupiedByChildNodeName) > 0) {
            throw new NodeNameIsAlreadyOccupied(
                'Child node name "' . $nodeName . '" is already occupied for parent "'
                    . $parentNodeAggregateIdentifier . '" in dimension space points '
                    . $dimensionSpacePointsOccupiedByChildNodeName
            );
        }
    }

    /**
     * @throws NodeNameIsAlreadyCovered
     */
    protected function requireNodeNameToBeUncovered(
        ContentStreamIdentifier $contentStreamIdentifier,
        ?NodeName $nodeName,
        NodeAggregateIdentifier $parentNodeAggregateIdentifier,
        DimensionSpacePointSet $dimensionSpacePointsToBeCovered
    ): void {
        if ($nodeName === null) {
            return;
        }
        $childNodeAggregates = $this->getContentGraph()->findChildNodeAggregatesByName(
            $contentStreamIdentifier,
            $parentNodeAggregateIdentifier,
            $nodeName
        );
        foreach ($childNodeAggregates as $childNodeAggregate) {
            $alreadyCoveredDimensionSpacePoints = $childNodeAggregate->getCoveredDimensionSpacePoints()
                ->getIntersection($dimensionSpacePointsToBeCovered);
            if (!$alreadyCoveredDimensionSpacePoints->isEmpty()) {
                throw new NodeNameIsAlreadyCovered(
                    'Node name "' . $nodeName . '" is already covered in dimension space points '
                        . $alreadyCoveredDimensionSpacePoints . ' by node aggregate "'
                        . $childNodeAggregate->getIdentifier() . '".'
                );
            }
        }
    }

    /**
     * @throws DimensionSpacePointIsNotYetOccupied
     */
    protected function requireNodeAggregateToOccupyDimensionSpacePoint(
        ReadableNodeAggregateInterface $nodeAggregate,
        OriginDimensionSpacePoint $originDimensionSpacePoint
    ): void {
        if (!$nodeAggregate->occupiesDimensionSpacePoint($originDimensionSpacePoint)) {
            throw new DimensionSpacePointIsNotYetOccupied(
                'Dimension space point ' . json_encode($originDimensionSpacePoint)
                    . ' is not yet occupied by node aggregate "' . $nodeAggregate->getIdentifier() . '"',
                1552595396
            );
        }
    }

    /**
     * @throws DimensionSpacePointIsAlreadyOccupied
     */
    protected function requireNodeAggregateToNotOccupyDimensionSpacePoint(
        ReadableNodeAggregateInterface $nodeAggregate,
        OriginDimensionSpacePoint $originDimensionSpacePoint
    ): void {
        if ($nodeAggregate->occupiesDimensionSpacePoint($originDimensionSpacePoint)) {
            throw new DimensionSpacePointIsAlreadyOccupied(
                'Dimension space point ' . json_encode($originDimensionSpacePoint)
                    . ' is already occupied by node aggregate "' . $nodeAggregate->getIdentifier() . '"',
                1552595441
            );
        }
    }

    /**
     * @throws NodeAggregateCurrentlyDoesNotDisableDimensionSpacePoint
     */
    protected function requireNodeAggregateToDisableDimensionSpacePoint(
        ReadableNodeAggregateInterface $nodeAggregate,
        DimensionSpacePoint $dimensionSpacePoint
    ): void {
        if (!$nodeAggregate->disablesDimensionSpacePoint($dimensionSpacePoint)) {
            throw new NodeAggregateCurrentlyDoesNotDisableDimensionSpacePoint(
                'Node aggregate "' . $nodeAggregate->getIdentifier()
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
        ReadableNodeAggregateInterface $nodeAggregate,
        DimensionSpacePoint $dimensionSpacePoint
    ): void {
        if ($nodeAggregate->disablesDimensionSpacePoint($dimensionSpacePoint)) {
            throw new NodeAggregateCurrentlyDisablesDimensionSpacePoint(
                'Node aggregate "' . $nodeAggregate->getIdentifier()
                    . '" currently disables dimension space point ' . json_encode($dimensionSpacePoint) . '.',
                1555179563
            );
        }
    }
}
