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

/**
 * @internal
 */
final class ClosureNodeTypeProvider implements NodeTypeProviderInterface
{
    private NodeTypes $cachedNodeTypes;

    public function __construct(
        private readonly \Closure $nodeTypeConfigLoader,
    ) {
        $this->cachedNodeTypes = NodeTypes::fromArray([]);
    }

    public function getNodeTypes(): NodeTypes
    {
        if ($this->cachedNodeTypes->isEmpty()) {
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
        return $this->cachedNodeTypes;
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
        $cachedNodeType = $this->cachedNodeTypes->get($nodeTypeName);
        if ($cachedNodeType !== null) {
            return $cachedNodeType;
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
            throw new NodeConfigurationException('Node type "' . $nodeTypeName . '" sets super type with a non-string key to NULL.', 1416578395, $exception);
        } catch (NodeTypeIsFinalException $exception) {
            throw new NodeTypeIsFinalException('Node type "' . $nodeTypeName . '" has a super type "' . $exception->getMessage() . '" which is final.', 1316452423, $exception);
        }

        // Remove unset properties
        $nodeTypeConfiguration['properties'] = array_filter($nodeTypeConfiguration['properties'] ?? [], static fn ($propertyConfiguration) => $propertyConfiguration !== null);
        if ($nodeTypeConfiguration['properties'] === []) {
            unset($nodeTypeConfiguration['properties']);
        }

        $nodeType = new NodeType(
            NodeTypeName::fromString($nodeTypeName),
            $superTypes,
            $nodeTypeConfiguration
        );

        $this->cachedNodeTypes = $this->cachedNodeTypes->with($nodeType);
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
