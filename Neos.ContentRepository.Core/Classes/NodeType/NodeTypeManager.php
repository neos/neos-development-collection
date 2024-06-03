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
 * @api Note: The constructor is not part of the public API
 */
final class NodeTypeManager
{
    /**
     * Node types, indexed by name
     *
     * @var array<string,NodeType>
     */
    private array $cachedNodeTypes = [];

    /**
     * Node types, indexed by supertype (also including abstract node types)
     *
     * @var array<string,array<string,NodeType>>
     */
    private array $cachedSubNodeTypes = [];

    /**
     * @internal
     */
    public function __construct(
        private readonly \Closure $nodeTypeConfigLoader
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
        if ($this->cachedNodeTypes === []) {
            $this->loadNodeTypes();
        }
        if ($includeAbstractNodeTypes) {
            return $this->cachedNodeTypes;
        }

        return array_filter($this->cachedNodeTypes, function ($nodeType) {
            return !$nodeType->isAbstract();
        });
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
        if ($superTypeName instanceof NodeTypeName) {
            $superTypeName = $superTypeName->value;
        }
        if ($this->cachedNodeTypes === []) {
            $this->loadNodeTypes();
        }

        if (!isset($this->cachedSubNodeTypes[$superTypeName])) {
            $filteredNodeTypes = [];
            foreach ($this->cachedNodeTypes as $nodeTypeName => $nodeType) {
                if ($nodeType->isOfType($superTypeName) && $nodeTypeName !== $superTypeName) {
                    $filteredNodeTypes[$nodeTypeName] = $nodeType;
                }
            }
            $this->cachedSubNodeTypes[$superTypeName] = $filteredNodeTypes;
        }

        if ($includeAbstractNodeTypes === false) {
            return array_filter($this->cachedSubNodeTypes[$superTypeName], function (NodeType $nodeType) {
                return !$nodeType->isAbstract();
            });
        }

        return $this->cachedSubNodeTypes[$superTypeName];
    }

    /**
     * Returns the specified node type (which could be abstract)
     */
    public function getNodeType(string|NodeTypeName $nodeTypeName): ?NodeType
    {
        if ($nodeTypeName instanceof NodeTypeName) {
            $nodeTypeName = $nodeTypeName->value;
        }
        if ($this->cachedNodeTypes === []) {
            $this->loadNodeTypes();
        }
        if (isset($this->cachedNodeTypes[$nodeTypeName])) {
            return $this->cachedNodeTypes[$nodeTypeName];
        }
        return null;
    }

    /**
     * Checks if the specified node type exists
     *
     * @param string|NodeTypeName $nodeTypeName Name of the node type
     * @return boolean true if it exists, otherwise false
     */
    public function hasNodeType(string|NodeTypeName $nodeTypeName): bool
    {
        if ($nodeTypeName instanceof NodeTypeName) {
            $nodeTypeName = $nodeTypeName->value;
        }
        if ($this->cachedNodeTypes === []) {
            $this->loadNodeTypes();
        }
        return isset($this->cachedNodeTypes[$nodeTypeName]);
    }

    /**
     * Loads all node types into memory.
     */
    private function loadNodeTypes(): void
    {
        $completeNodeTypeConfiguration = ($this->nodeTypeConfigLoader)();

        // the root node type must always exist
        $completeNodeTypeConfiguration[NodeTypeName::ROOT_NODE_TYPE_NAME] ??= [];

        foreach (array_keys($completeNodeTypeConfiguration) as $nodeTypeName) {
            if (!is_string($nodeTypeName)) {
                continue;
            }
            if (!is_array($completeNodeTypeConfiguration[$nodeTypeName])) {
                continue;
            }
            $this->loadNodeType($nodeTypeName, $completeNodeTypeConfiguration);
        }
    }

    /**
     * This method can be used by Functional of Behavioral Tests to completely
     * override the node types known in the system.
     *
     * In order to reset the node type override, an empty array can be passed in.
     * In this case, the system-node-types are used again.
     *
     * @internal
     * @param array<string,mixed> $completeNodeTypeConfiguration
     */
    public function overrideNodeTypes(array $completeNodeTypeConfiguration): void
    {
        $this->cachedNodeTypes = [];

        if ($completeNodeTypeConfiguration === []) {
            // as cachedNodeTypes is now empty loadNodeTypes will reload the default nodeTypes
            return;
        }

        // the root node type must always exist
        $completeNodeTypeConfiguration[NodeTypeName::ROOT_NODE_TYPE_NAME] ??= [];

        foreach (array_keys($completeNodeTypeConfiguration) as $nodeTypeName) {
            /** @var string $nodeTypeName */
            $this->loadNodeType($nodeTypeName, $completeNodeTypeConfiguration);
        }
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

    /**
     * Load one node type, if it is not loaded yet.
     *
     * @param array<string,mixed> $completeNodeTypeConfiguration the full node type configuration for all node types
     * @throws NodeConfigurationException
     * @throws NodeTypeIsFinalException
     */
    private function loadNodeType(string $nodeTypeName, array &$completeNodeTypeConfiguration): NodeType
    {
        if (isset($this->cachedNodeTypes[$nodeTypeName])) {
            return $this->cachedNodeTypes[$nodeTypeName];
        }

        if (!isset($completeNodeTypeConfiguration[$nodeTypeName])) {
            // only thrown if a programming error occurred.
            throw new \RuntimeException('Must not happen, logic error: Node type "' . $nodeTypeName . '" does not exist', 1316451800);
        }

        $nodeTypeConfiguration = $completeNodeTypeConfiguration[$nodeTypeName];
        try {
            $superTypes = isset($nodeTypeConfiguration['superTypes'])
                ? $this->evaluateSuperTypesConfiguration(
                    $nodeTypeConfiguration['superTypes'],
                    $completeNodeTypeConfiguration
                )
                : [];
        } catch (NodeConfigurationException $exception) {
            throw new NodeConfigurationException(
                'Node type "' . $nodeTypeName . '" sets super type with a non-string key to NULL.',
                1416578395
            );
        } catch (NodeTypeIsFinalException $exception) {
            throw new NodeTypeIsFinalException(
                'Node type "' . $nodeTypeName . '" has a super type "' . $exception->getMessage() . '" which is final.',
                1316452423
            );
        }

        // Remove unset properties
        $properties = [];
        if (isset($nodeTypeConfiguration['properties']) && is_array($nodeTypeConfiguration['properties'])) {
            $properties = $nodeTypeConfiguration['properties'];
        }

        $nodeTypeConfiguration['properties'] = array_filter($properties, function ($propertyConfiguration) {
            return $propertyConfiguration !== null;
        });

        if ($nodeTypeConfiguration['properties'] === []) {
            unset($nodeTypeConfiguration['properties']);
        }

        $nodeType = new NodeType(
            NodeTypeName::fromString($nodeTypeName),
            $superTypes,
            $nodeTypeConfiguration
        );

        $this->cachedNodeTypes[$nodeTypeName] = $nodeType;
        return $nodeType;
    }

    /**
     * Evaluates the given superTypes configuation and returns the array of effective superTypes.
     *
     * @param array<string,mixed> $superTypesConfiguration
     * @param array<string,mixed> $completeNodeTypeConfiguration
     * @return array<string,NodeType|null>
     */
    private function evaluateSuperTypesConfiguration(
        array $superTypesConfiguration,
        array $completeNodeTypeConfiguration
    ): array {
        $superTypes = [];
        foreach ($superTypesConfiguration as $superTypeName => $enabled) {
            if (!is_string($superTypeName)) {
                throw new NodeConfigurationException(
                    'superTypes must be a dictionary; the array format was deprecated since Neos 2.0',
                    1651821391
                );
            }
            $superTypes[$superTypeName] = $this->evaluateSuperTypeConfiguration(
                $superTypeName,
                $enabled,
                $completeNodeTypeConfiguration
            );
        }

        return $superTypes;
    }

    /**
     * Evaluates a single superType configuration and returns the NodeType if enabled.
     *
     * @param array<string,mixed> $completeNodeTypeConfiguration
     * @throws NodeConfigurationException
     * @throws NodeTypeIsFinalException
     */
    private function evaluateSuperTypeConfiguration(
        string $superTypeName,
        ?bool $enabled,
        array &$completeNodeTypeConfiguration
    ): ?NodeType {
        // Skip unset node types
        if ($enabled === false || $enabled === null) {
            return null;
        }

        $superType = $this->loadNodeType($superTypeName, $completeNodeTypeConfiguration);
        if ($superType->isFinal() === true) {
            throw new NodeTypeIsFinalException($superType->name->value, 1444944148);
        }

        return $superType;
    }
}
