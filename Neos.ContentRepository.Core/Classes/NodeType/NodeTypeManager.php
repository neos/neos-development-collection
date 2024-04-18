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

use Neos\ContentRepository\Core\NodeType\Exception\TetheredNodeNotConfigured;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeConfigurationException;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeIsFinalException;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeNotFoundException;
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
     *
     * @throws NodeTypeNotFoundException
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
     * @param NodeType $nodeType
     * @param NodeName $tetheredNodeName
     * @return NodeType
     * @throws TetheredNodeNotConfigured if the requested tethered node is not configured. Check via {@see NodeType::hasTetheredNode()}.
     */
    public function getTypeOfTetheredNode(NodeType $nodeType, NodeName $tetheredNodeName): NodeType
    {
        $nameOfTetheredNode = $nodeType->getNodeTypeNameOfTetheredNode($tetheredNodeName);
        return $this->requireNodeType($nameOfTetheredNode);
    }

    /**
     * Return an array with child nodes which should be automatically created
     *
     * @return array<string,NodeType> the key of this array is the name of the child, and the value its NodeType.
     */
    public function getTetheredNodesConfigurationForNodeType(NodeType $nodeType): array
    {
        $childNodeConfiguration = $nodeType->getConfiguration('childNodes');
        $autoCreatedChildNodes = [];
        foreach ($childNodeConfiguration ?? [] as $childNodeName => $configurationForChildNode) {
            if (isset($configurationForChildNode['type'])) {
                $autoCreatedChildNodes[NodeName::transliterateFromString($childNodeName)->value] = $this->requireNodeType($configurationForChildNode['type']);
            }
        }
        return $autoCreatedChildNodes;
    }

    /**
     * Checks if the given $nodeType is allowed as a childNode of the given $tetheredNodeName
     * (which must be tethered in $parentNodeType).
     *
     * Only allowed to be called if $tetheredNodeName is actually tethered.
     *
     * @param NodeType $parentNodeType The NodeType to check constraints based on.
     * @param NodeName $tetheredNodeName The name of a configured tethered node of this NodeType
     * @param NodeType $nodeType The NodeType to check constraints for.
     * @return bool true if the $nodeType is allowed as grandchild node, false otherwise.
     * @throws \InvalidArgumentException if the requested tethered node is not configured in the parent NodeType. Check via {@see NodeType::hasTetheredNode()}.
     */
    public function isNodeTypeAllowedAsChildToTetheredNode(NodeType $parentNodeType, NodeName $tetheredNodeName, NodeType $nodeType): bool
    {
        try {
            $typeOfTetheredNode = $this->getTypeOfTetheredNode($parentNodeType, $tetheredNodeName);
        } catch (TetheredNodeNotConfigured $exception) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Cannot determine if grandchild is allowed in %s. Because the given child node name "%s" is not auto-created.',
                    $parentNodeType->name->value,
                    $tetheredNodeName->value
                ),
                1403858395,
                $exception
            );
        }

        // Constraints configured on the NodeType for the child node
        $constraints = $typeOfTetheredNode->getConfiguration('constraints.nodeTypes') ?: [];

        // Constraints configured at the childNode configuration of the parent.
        try {
            $childNodeConstraintConfiguration = $parentNodeType->getConfiguration('childNodes.' . $tetheredNodeName->value . '.constraints.nodeTypes') ?? [];
        } catch (PropertyNotAccessibleException $exception) {
            // We ignore this because the configuration might just not have any constraints, if the childNode was not configured the exception above would have been thrown.
            $childNodeConstraintConfiguration = [];
        }
        $constraints = Arrays::arrayMergeRecursiveOverrule($constraints, $childNodeConstraintConfiguration);
        return ConstraintCheck::create($constraints)->isNodeTypeAllowed($nodeType);
    }

    /**
     * @internal helper to throw if the NodeType doesn't exit
     */
    public function requireNodeType(string|NodeTypeName $nodeTypeName): NodeType
    {
        return $this->getNodeType($nodeTypeName) ?? throw new NodeTypeNotFoundException(
            sprintf(
                'The node type "%s" is not available',
                $nodeTypeName instanceof NodeTypeName ? $nodeTypeName->value : $nodeTypeName
            ),
            1316598370
        );
    }
}
