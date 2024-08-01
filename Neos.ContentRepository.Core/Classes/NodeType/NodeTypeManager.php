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

namespace Neos\ContentRepository\Core\NodeType;

use Neos\ContentRepository\Core\SharedModel\Exception\NodeConfigurationException;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeIsFinalException;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\Utility\Arrays;
use Neos\Utility\Exception\PropertyNotAccessibleException;

/**
 * Manager for node types
 * @api
 */
final class NodeTypeManager
{
    /**
     * Node types, indexed by supertype (also including abstract node types)
     *
     * @var array<string,NodeTypes>
     */
    private array $cachedSubNodeTypes = [];

    /**
     * @internal
     */
    public function __construct(
        private readonly NodeTypeProviderInterface $nodeTypeProvider
    ) {
    }

    /**
     * Return all registered node types.
     *
     * @param boolean $includeAbstractNodeTypes Whether to include abstract node types, defaults to true
     * @return array<string,NodeType> All node types registered in the system, indexed by node type name
     */
    public function getNodeTypes(bool $includeAbstractNodeTypes = true): array
    {
        if ($includeAbstractNodeTypes) {
            return $this->nodeTypeProvider->getNodeTypes()->toArray();
        }
        return $this->nodeTypeProvider->getNodeTypes()->withoutAbstractNodeTypes()->toArray();
    }

    /**
     * Return all non-abstract node types which have a certain $superType, without
     * the $superType itself.
     *
     * @param boolean $includeAbstractNodeTypes Whether to include abstract node types, defaults to true
     * @return array<NodeType> Sub node types of the given super type, indexed by node type name
     */
    public function getSubNodeTypes(string|NodeTypeName $superTypeName, bool $includeAbstractNodeTypes = true): array
    {
        if (is_string($superTypeName)) {
            $superTypeName = NodeTypeName::fromString($superTypeName);
        }
        if (!array_key_exists($superTypeName->value, $this->cachedSubNodeTypes)) {
            $this->cachedSubNodeTypes[$superTypeName->value] = $this->nodeTypeProvider->getNodeTypes()->filter(
                fn (NodeType $nodeType) => !$nodeType->name->equals($superTypeName) && $nodeType->isOfType($superTypeName)
            );
        }
        if ($includeAbstractNodeTypes) {
            return $this->cachedSubNodeTypes[$superTypeName->value]->toArray();
        }
        return $this->cachedSubNodeTypes[$superTypeName->value]->withoutAbstractNodeTypes()->toArray();
    }

    /**
     * Returns the specified node type (which could be abstract)
     */
    public function getNodeType(string|NodeTypeName $nodeTypeName): ?NodeType
    {
        return $this->nodeTypeProvider->getNodeTypes()->get($nodeTypeName);
    }

    /**
     * Checks if the specified node type exists
     *
     * @param string|NodeTypeName $nodeTypeName Name of the node type
     * @return boolean true if it exists, otherwise false
     */
    public function hasNodeType(string|NodeTypeName $nodeTypeName): bool
    {
        return $this->nodeTypeProvider->getNodeTypes()->has($nodeTypeName);
    }

    /**
     * Checks if the given $nodeTypeNameToCheck is allowed as a childNode of the given $tetheredNodeName.
     *
     * Returns false if $tetheredNodeName is not tethered to $parentNodeTypeName, or if any of the $nodeTypeNameToCheck does not exist.
     *
     * @param NodeTypeName $parentNodeTypeName The NodeType to check constraints based on.
     * @param NodeName $tetheredNodeName The name of a configured tethered node of this NodeType
     * @param NodeTypeName $nodeTypeNameToCheck The NodeType to check constraints for.
     * @return bool true if the $nodeTypeNameToCheck is allowed as grandchild node, false otherwise.
     */
    public function isNodeTypeAllowedAsChildToTetheredNode(NodeTypeName $parentNodeTypeName, NodeName $tetheredNodeName, NodeTypeName $nodeTypeNameToCheck): bool
    {
        $parentNodeType = $this->getNodeType($parentNodeTypeName);
        $nodeTypeNameOfTetheredNode = $parentNodeType?->tetheredNodeTypeDefinitions->get($tetheredNodeName)?->nodeTypeName;
        if (!$parentNodeType || !$nodeTypeNameOfTetheredNode) {
            // Cannot determine if grandchild is allowed, because the given child node name is not auto-created.
            return false;
        }

        $nodeTypeOfTetheredNode = $this->getNodeType($nodeTypeNameOfTetheredNode);
        if (!$nodeTypeOfTetheredNode) {
            return false;
        }

        // Constraints configured on the NodeType for the child node
        $constraints = $nodeTypeOfTetheredNode->getConfiguration('constraints.nodeTypes') ?: [];

        // Constraints configured at the childNode configuration of the parent.
        try {
            $childNodeConstraintConfiguration = $parentNodeType->getConfiguration('childNodes.' . $tetheredNodeName->value . '.constraints.nodeTypes') ?? [];
        } catch (PropertyNotAccessibleException $exception) {
            // We ignore this because the configuration might just not have any constraints, if the childNode was not configured null would have been returned.
            $childNodeConstraintConfiguration = [];
        }
        $constraints = Arrays::arrayMergeRecursiveOverrule($constraints, $childNodeConstraintConfiguration);

        $nodeTypeToCheck = $this->getNodeType($nodeTypeNameToCheck);
        if (!$nodeTypeToCheck) {
            return false;
        }

        return ConstraintCheck::create($constraints)->isNodeTypeAllowed(
            $nodeTypeToCheck
        );
    }
}
