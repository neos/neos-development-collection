<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\NodeType;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\SharedModel\Exception\InvalidNodeTypePostprocessorException;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeConfigurationException;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;

/**
 * A Node Type
 *
 * Although methods contained in this class belong to the public API, you should
 * not need to deal with creating or managing node types manually. New node types
 * should be defined in a NodeTypes.yaml file.
 *
 * @api Note: The constructor is not part of the public API
 */
final class NodeType
{
    /**
     * @param array<string,mixed> $metadata arbitrary domain specific configuration for this node type
     *
     * @internal
     */
    private function __construct(
        public NodeTypeName $name,
        public NodeTypeNames $superTypeNames,
        public TetheredNodeTypeDefinitions $tetheredNodeTypeDefinitions,
        public PropertyDefinitions $propertyDefinitions,
        public ReferenceDefinitions $referenceDefinitions,
        public bool $isAggregate,
        public NodeTypeConstraints $childNodeTypeConstraints,
        public array $metadata,
        public NodeTypeLabel|null $label,
    ) {
    }

    /**
     * Returns whether this node type (or any parent type) is an *aggregate*.
     *
     * The most prominent *aggregate* is a Document and everything which inherits from it, like a Page.
     *
     * If a node type is marked as aggregate, it means that:
     *
     * - the node type can "live on its own", i.e. can be part of an external URL
     * - when moving this node, all node variants are also moved (across all dimensions)
     * - Recursive copying only happens *inside* this aggregate, and stops at nested aggregates.
     *
     * @return boolean true if the node type is an aggregate
     * @api
     */
    public function isAggregate(): bool
    {
        return $this->isAggregate;
    }

    /**
     * If this node type or any of the direct or indirect super types
     * has the given name.
     *
     * @return boolean true if this node type is of the given kind, otherwise false
     * @api
     */
    public function isOfType(string|NodeTypeName $nodeTypeName): bool
    {
        if (is_string($nodeTypeName)) {
            $nodeTypeName = NodeTypeName::fromString($nodeTypeName);
        }
        return $this->superTypeNames->contain($nodeTypeName);
    }

    /**
     * Get the full configuration of the node type. Should only be used internally.
     *
     * Instead, use the hasConfiguration()/getConfiguration() methods to check/retrieve single configuration values.
     *
     * @return array<string,mixed>
     * @deprecated with Neos 9.0 the public fields should be used instead, e.g. $nodeType->metadata
     */
    public function getFullConfiguration(): array
    {
        // TODO implement
    }

    /**
     * Checks if the configuration of this node type contains a setting for the given $configurationPath
     *
     * @param string $configurationPath The name of the configuration option to verify
     * @deprecated with Neos 9.0 the public fields should be used instead
     */
    public function hasConfiguration(string $configurationPath): bool
    {
        // TODO implement
    }

    /**
     * Returns the configuration option with the specified $configurationPath or NULL if it does not exist
     *
     * @param string $configurationPath The name of the configuration option to retrieve
     * @deprecated with Neos 9.0 the public fields should be used instead
     */
    public function getConfiguration(string $configurationPath): mixed
    {
        // TODO implement
    }

    /**
     * Get the human-readable label of this node type
     *
     * @api
     */
    public function getLabel(): string
    {
        return $this->label->value;
    }

    /**
     * Get additional options (if specified)
     *
     * @return array<string,mixed>
     * @api
     */
    public function getOptions(): array
    {
        return $this->metadata['options'] ?? [];
    }

    /**
     * Return the array with the defined properties. The key is the property name,
     * the value the property configuration. There are no guarantees on how the
     * property configuration looks like.
     *
     * @return array<string,mixed>
     * @deprecated with Neos 9.0 – {@see propertyDefinitions} should be used instead
     */
    public function getProperties(): array
    {
        return $this->propertyDefinitions->map(function (PropertyDefinition $definition) {
            return [
                ...$definition->metadata,
                'type' => $definition->type,
                'defaultValue' => $definition->defaultValue,
                'scope' => $definition->scope->value,
            ];
        });
    }

    /**
     * Check if the property is configured in the schema.
     * @deprecated with Neos 9.0 – {@see propertyDefinitions::contain()} should be used instead
     */
    public function hasProperty(string $propertyName): bool
    {
        return $this->propertyDefinitions->contain($propertyName);
    }

    /**
     * Returns the configured type of the specified property, and falls back to 'string'.
     *
     * @deprecated with Neos 9.0 – {@see propertyDefinitions::get($propertyName)->type} should be used instead
     */
    public function getPropertyType(string $propertyName): string
    {
        $propertyDefinition = $this->propertyDefinitions->get(PropertyName::fromString($propertyName));
        if ($propertyDefinition === null) {
            throw new \InvalidArgumentException(sprintf('NodeType schema has no property "%s" configured for the NodeType "%s". Cannot read its type.', $propertyName, $this->name->value), 1708025421);
        }
        return $propertyDefinition->type;
    }


    /**
     * Return an array with the defined default values for each property, if any.
     *
     * The default value is configured for each property under the "default" key.
     *
     * @return array<string,int|float|string|bool|array<int|string,mixed>>
     * @deprecated with Neos 9.0 – {@see propertyDefinitions->get($propertyName)?->defaultValue} should be used instead
     */
    public function getDefaultValuesForProperties(): array
    {
        return $this->propertyDefinitions->map(fn (PropertyDefinition $propertyDefinition) => $propertyDefinition->defaultValue);
    }

    /**
     * Checks if the given NodeType is acceptable as sub-node with the configured constraints,
     * not taking constraints of auto-created nodes into account. Thus, this method only returns
     * the correct result if called on NON-AUTO-CREATED nodes!
     *
     * Otherwise, isNodeTypeAllowedAsChildToTetheredNode() needs to be called on the *parent node type*.
     *
     * @return boolean true if the $nodeType is allowed as child node, false otherwise.
     * @deprecated with Neos 9.0 – {@see childNodeTypeConstraints} should be used instead
     */
    public function allowsChildNodeType(NodeType $nodeType): bool
    {
        return $this->childNodeTypeConstraints->isNodeTypeAllowed($nodeType->name);
    }

    /**
     * @param NodeName $nodeName
     * @return bool true if $nodeName is an autocreated child node, false otherwise
     * @deprecated with Neos 9.0 – {@see tetheredNodeTypeDefinitions} should be used instead
     */
    public function hasAutoCreatedChildNode(NodeName $nodeName): bool
    {
        return $this->tetheredNodeTypeDefinitions->contain($nodeName);
    }

    /**
     * @param NodeName $nodeName
     * @return NodeTypeName|null
     * @deprecated with Neos 9.0 – {@see tetheredNodeTypeDefinitions} should be used instead
     */
    public function getTypeOfAutoCreatedChildNode(NodeName $nodeName): ?NodeTypeName
    {
        $tetheredNodeDefinition = $this->tetheredNodeTypeDefinitions->get($nodeName);
        return $tetheredNodeDefinition->nodeTypeName;
    }

    // TODO remove (check usages first)
    public function getReferences()
    {
    }

    // TODO remove (check usages first)
    public function hasReference()
    {
    }

    // TODO remove (check usages first)
    public function isAbstract(): bool
    {
    }

    // TODO remove (check usages first)
    public function isFinal(): bool
    {
    }
}
