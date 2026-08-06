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

use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\SerializedNodeReferences;
use Neos\ContentRepository\Core\NodeType\ConstraintCheck;
use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeConstraintException;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeNameIsAlreadyCovered;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeNotFound;
use Neos\ContentRepository\Core\SharedModel\Exception\PropertyCannotBeSet;
use Neos\ContentRepository\Core\SharedModel\Exception\ReferenceCannotBeSet;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;

/**
 * @internal implementation details of command handlers; Constraint checks only operating on the NodeTypes's not on the content graph
 */
trait NodeTypeConstraintChecks
{
    abstract protected function getNodeTypeManager(): NodeTypeManager;

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

    final protected function requireNodeTypeToDeclareProperty(NodeTypeName $nodeTypeName, PropertyName $propertyName): void
    {
        $nodeType = $this->requireNodeType($nodeTypeName);
        if (!$nodeType->hasProperty($propertyName->value)) {
            throw PropertyCannotBeSet::becauseTheNodeTypeDoesNotDeclareIt(
                $propertyName,
                $nodeTypeName
            );
        }
    }

    final protected function requireNodeTypeToDeclareReference(NodeTypeName $nodeTypeName, ReferenceName $referenceName): void
    {
        $nodeType = $this->requireNodeType($nodeTypeName);
        if ($nodeType->hasReference($referenceName->value)) {
            return;
        }
        throw ReferenceCannotBeSet::becauseTheNodeTypeDoesNotDeclareIt($referenceName, $nodeTypeName);
    }

    final protected function requireNodeTypeNotToDeclareTetheredChildNodeName(NodeTypeName $nodeTypeName, NodeName $nodeName): void
    {
        $nodeType = $this->requireNodeType($nodeTypeName);
        if ($nodeType->tetheredNodeTypeDefinitions->contain($nodeName)) {
            throw new NodeNameIsAlreadyCovered(
                'Node name "' . $nodeName->value . '" is reserved for a tethered child of parent node aggregate of type "'
                . $nodeTypeName->value . '".'
            );
        }
    }

    final protected function requireNodeTypeToAllowNodesOfTypeInReference(
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

    final protected function requireNodeTypeToAllowNumberOfReferencesInReference(SerializedNodeReferences $nodeReferences, NodeTypeName $nodeTypeName): void
    {
        $nodeType = $this->requireNodeType($nodeTypeName);

        foreach ($nodeReferences->references as $referencesByName) {
            $maxItems = $nodeType->getReferences()[$referencesByName->referenceName->value]['constraints']['maxItems'] ?? null;
            if ($maxItems === null) {
                continue;
            }

            if ($maxItems < $referencesByName->count()) {
                throw ReferenceCannotBeSet::becauseTheItemsCountConstraintsAreNotMatched(
                    $referencesByName->referenceName,
                    $nodeTypeName,
                    $referencesByName->count()
                );
            }
        }
    }

    /**
     * @throws NodeTypeNotFound
     * @throws NodeConstraintException
     */
    final protected function requireNodeTypeConstraintsImposedByParentToBeMet(
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

    final protected function areNodeTypeConstraintsImposedByParentValid(
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
    final protected function requireNodeTypeConstraintsImposedByGrandparentToBeMet(
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

    final protected function areNodeTypeConstraintsImposedByGrandparentValid(
        NodeType $grandParentsNodeType,
        ?NodeName $parentNodeName,
        NodeType $nodeType
    ): bool {
        return !($parentNodeName
            && $grandParentsNodeType->tetheredNodeTypeDefinitions->contain($parentNodeName)
            && !$this->getNodeTypeManager()->isNodeTypeAllowedAsChildToTetheredNode($grandParentsNodeType->name, $parentNodeName, $nodeType->name));
    }
}
