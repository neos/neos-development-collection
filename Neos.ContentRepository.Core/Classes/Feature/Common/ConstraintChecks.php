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

use Neos\ContentRepository\Core\CommandHandlingDependencies;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\Exception\DimensionSpacePointNotFound;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\SerializedNodeReferences;
use Neos\ContentRepository\Core\Feature\NodeVariation\Exception\DimensionSpacePointIsAlreadyOccupied;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyType;
use Neos\ContentRepository\Core\NodeType\ConstraintCheck;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindPrecedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSucceedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamDoesNotExistYet;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamIsClosed;
use Neos\ContentRepository\Core\SharedModel\Exception\DimensionSpacePointIsNotYetOccupied;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateCurrentlyDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateCurrentlyExists;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateDoesCurrentlyNotCoverDimensionSpacePointSet;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateIsDescendant;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateIsNoChild;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateIsNoSibling;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateIsRoot;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateIsTethered;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregatesTypeIsAmbiguous;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeConstraintException;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeNameIsAlreadyCovered;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeIsAbstract;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeIsNotOfTypeRoot;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeIsOfTypeRoot;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeNotFound;
use Neos\ContentRepository\Core\SharedModel\Exception\PropertyCannotBeSet;
use Neos\ContentRepository\Core\SharedModel\Exception\ReferenceCannotBeSet;
use Neos\ContentRepository\Core\SharedModel\Exception\RootNodeAggregateTypeIsAlreadyOccupied;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamStatus;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\EventStore\Model\EventStream\ExpectedVersion;

/**
 * @internal implementation details of command handlers
 */
trait ConstraintChecks
{
    abstract protected function getNodeTypeManager(): NodeTypeManager;

    abstract protected function getAllowedDimensionSubspace(): DimensionSpacePointSet;

    /**
     * @throws ContentStreamDoesNotExistYet
     */
    protected function requireContentStream(
        WorkspaceName $workspaceName,
        CommandHandlingDependencies $commandHandlingDependencies
    ): ContentStreamId {
        $contentStreamId = $commandHandlingDependencies->getContentGraph($workspaceName)->getContentStreamId();
        if (!$commandHandlingDependencies->contentStreamExists($contentStreamId)) {
            throw new ContentStreamDoesNotExistYet(
                'Content stream for "' . $workspaceName->value . '" does not exist yet.',
                1521386692
            );
        }
        $state = $commandHandlingDependencies->getContentStreamStatus($contentStreamId);
        if ($state === ContentStreamStatus::CLOSED) {
            throw new ContentStreamIsClosed(
                'Content stream "' . $contentStreamId->value . '" is closed.',
                1710260081
            );
        }

        return $contentStreamId;
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
        return $this->getNodeTypeManager()->getNodeType($nodeTypeName) ?? throw new NodeTypeNotFound(
            'Node type "' . $nodeTypeName->value . '" is unknown to the node type manager.',
            1541671070
        );
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

    protected function requireNodeTypeToDeclareProperty(NodeTypeName $nodeTypeName, PropertyName $propertyName): void
    {
        $nodeType = $this->requireNodeType($nodeTypeName);
        if (!$nodeType->hasProperty($propertyName->value)) {
            throw PropertyCannotBeSet::becauseTheNodeTypeDoesNotDeclareIt(
                $propertyName,
                $nodeTypeName
            );
        }
    }

    protected function requireNodeTypeToDeclareReference(NodeTypeName $nodeTypeName, ReferenceName $referenceName): void
    {
        $nodeType = $this->requireNodeType($nodeTypeName);
        if ($nodeType->hasReference($referenceName->value)) {
            return;
        }
        throw ReferenceCannotBeSet::becauseTheNodeTypeDoesNotDeclareIt($referenceName, $nodeTypeName);
    }

    protected function requireNodeTypeNotToDeclareTetheredChildNodeName(NodeTypeName $nodeTypeName, NodeName $nodeName): void
    {
        $nodeType = $this->requireNodeType($nodeTypeName);
        if ($nodeType->tetheredNodeTypeDefinitions->contain($nodeName)) {
            throw new NodeNameIsAlreadyCovered(
                'Node name "' . $nodeName->value . '" is reserved for a tethered child of parent node aggregate of type "'
                . $nodeTypeName->value . '".'
            );
        }
    }

    protected function requireNodeTypeToAllowNodesOfTypeInReference(
        NodeTypeName $nodeTypeName,
        ReferenceName $referenceName,
        NodeTypeName $nodeTypeNameInQuestion
    ): void {
        $nodeType = $this->requireNodeType($nodeTypeName);
        $constraints = $nodeType->getReferences()[$referenceName->value]['constraints']['nodeTypes'] ?? [];

        if (!ConstraintCheck::create($constraints)->isNodeTypeAllowed($this->requireNodeType($nodeTypeNameInQuestion))) {
            throw ReferenceCannotBeSet::becauseTheNodeTypeConstraintsAreNotMatched(
                $referenceName,
                $nodeTypeName,
                $nodeTypeNameInQuestion
            );
        }
    }

    protected function requireNodeTypeToAllowNumberOfReferencesInReference(SerializedNodeReferences $nodeReferences, ReferenceName $referenceName, NodeTypeName $nodeTypeName): void
    {
        $nodeType = $this->requireNodeType($nodeTypeName);

        $maxItems = $nodeType->getReferences()[$referenceName->value]['constraints']['maxItems'] ?? null;
        if ($maxItems === null) {
            return;
        }

        if ($maxItems < count($nodeReferences)) {
            throw ReferenceCannotBeSet::becauseTheItemsCountConstraintsAreNotMatched(
                $referenceName,
                $nodeTypeName,
                count($nodeReferences)
            );
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
     * @throws NodeTypeNotFound
     * @throws NodeConstraintException
     */
    protected function requireNodeTypeConstraintsImposedByParentToBeMet(
        NodeType $parentsNodeType,
        NodeType $nodeType
    ): void {
        // !!! IF YOU ADJUST THIS METHOD, also adjust the method below.
        if (!$parentsNodeType->allowsChildNodeType($nodeType)) {
            throw new NodeConstraintException(
                'Node type "' . $nodeType->name->value . '" is not allowed for child nodes of type '
                    . $parentsNodeType->name->value,
                1707561400
            );
        }
    }

    protected function areNodeTypeConstraintsImposedByParentValid(
        NodeType $parentsNodeType,
        NodeType $nodeType
    ): bool {
        // !!! IF YOU ADJUST THIS METHOD, also adjust the method above.
        if (!$parentsNodeType->allowsChildNodeType($nodeType)) {
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
                'Node type "' . $nodeType->name->value . '" is not allowed below tethered child nodes "' . $parentNodeName?->value
                    . '" of nodes of type "' . $grandParentsNodeType->name->value . '"',
                1520011791
            );
        }
    }

    protected function areNodeTypeConstraintsImposedByGrandparentValid(
        NodeType $grandParentsNodeType,
        ?NodeName $parentNodeName,
        NodeType $nodeType
    ): bool {
        return !($parentNodeName
            && $grandParentsNodeType->tetheredNodeTypeDefinitions->contain($parentNodeName)
            && !$this->getNodeTypeManager()->isNodeTypeAllowedAsChildToTetheredNode($grandParentsNodeType->name, $parentNodeName, $nodeType->name));
    }

    /**
     * @throws NodeAggregatesTypeIsAmbiguous
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
     * @throws NodeAggregatesTypeIsAmbiguous
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
            VisibilityConstraints::withoutRestrictions()
        )->findSucceedingSiblingNodes($referenceNodeAggregateId, FindSucceedingSiblingNodesFilter::create());
        if ($succeedingSiblings->toNodeAggregateIds()->contain($siblingNodeAggregateId)) {
            return;
        }

        $precedingSiblings = $contentGraph->getSubgraph(
            $dimensionSpacePoint,
            VisibilityConstraints::withoutRestrictions()
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
            VisibilityConstraints::withoutRestrictions()
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
     * @throws NodeNameIsAlreadyCovered
     */
    protected function requireNodeNameToBeUncovered(
        ContentGraphInterface $contentGraph,
        ?NodeName $nodeName,
        NodeAggregateId $parentNodeAggregateId,
    ): void {
        if ($nodeName === null) {
            return;
        }

        $childNodeAggregate = $contentGraph->findChildNodeAggregateByName(
            $parentNodeAggregateId,
            $nodeName
        );
        if ($childNodeAggregate instanceof NodeAggregate) {
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
        CommandHandlingDependencies $commandHandlingDependencies
    ): ExpectedVersion {

        return ExpectedVersion::fromVersion(
            $commandHandlingDependencies->getContentStreamVersion($contentStreamId)
        );
    }
}
