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

use Neos\ContentRepository\Core\NodeType\Exception\TetheredNodeNotConfigured;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\Utility\ObjectAccess;
use Neos\Utility\Arrays;
use Neos\Utility\PositionalArraySorter;
use Neos\ContentRepository\Core\SharedModel\Exception\InvalidNodeTypePostprocessorException;

/**
 * A Node Type
 *
 * Although methods contained in this class belong to the public API, you should
 * not need to deal with creating or managing node types manually. New node types
 * should be defined in a NodeTypes.yaml file.
 *
 * @api Note: The constructor is not part of the public API
 */
class NodeType
{
    /**
     * Name of this node type. Example: "ContentRepository:Folder"
     */
    public readonly NodeTypeName $name;

    /**
     * Configuration for this node type, can be an arbitrarily nested array. Does not include inherited configuration.
     *
     * @var array<string,mixed>
     */
    protected array $localConfiguration;

    /**
     * Full configuration for this node type, can be an arbitrarily nested array. Includes any inherited configuration.
     *
     * @var array<string,mixed>
     */
    protected array $fullConfiguration = [];

    /**
     * Is this node type marked abstract
     */
    protected bool $abstract = false;

    /**
     * Is this node type marked final
     */
    protected bool $final = false;

    /**
     * node types this node type directly inherits from
     *
     * @var array<string,?NodeType>
     */
    protected array $declaredSuperTypes;

    protected ?NodeLabelGeneratorInterface $nodeLabelGenerator = null;

    /**
     * Whether or not this node type has been initialized (e.g. if it has been postprocessed)
     */
    protected bool $initialized = false;

    /**
     * @param NodeTypeName $name Name of the node type
     * @param array<string,mixed> $declaredSuperTypes Parent types of this node type
     * @param array<string,mixed> $configuration the configuration for this node type which is defined in the schema
     * @throws \InvalidArgumentException
     *
     * @internal
     */
    public function __construct(
        NodeTypeName $name,
        array $declaredSuperTypes,
        array $configuration,
        private readonly NodeLabelGeneratorFactoryInterface $nodeLabelGeneratorFactory
    ) {
        $this->name = $name;

        foreach ($declaredSuperTypes as $type) {
            if ($type !== null && !$type instanceof NodeType) {
                throw new \InvalidArgumentException(
                    '$declaredSuperTypes must be an array of NodeType objects',
                    1291300950
                );
            }
        }
        $this->declaredSuperTypes = $declaredSuperTypes;

        if (isset($configuration['abstract']) && $configuration['abstract'] === true) {
            $this->abstract = true;
            unset($configuration['abstract']);
        }

        if (isset($configuration['final']) && $configuration['final'] === true) {
            $this->final = true;
            unset($configuration['final']);
        }

        $this->localConfiguration = $configuration;
    }

    /**
     * Initializes this node type
     *
     * @throws InvalidNodeTypePostprocessorException
     * @throws \Exception
     */
    protected function initialize(): void
    {
        if ($this->initialized === true) {
            return;
        }
        $this->initialized = true;
        $this->setFullConfiguration($this->applyPostprocessing($this->buildFullConfiguration()));
    }

    /**
     * Builds the full configuration by merging configuration from the supertypes into the local configuration.
     *
     * @return array<string,mixed>
     */
    protected function buildFullConfiguration(): array
    {
        $mergedConfiguration = [];
        $applicableSuperTypes = static::getFlattenedSuperTypes($this);
        foreach ($applicableSuperTypes as $key => $superType) {
            $mergedConfiguration = Arrays::arrayMergeRecursiveOverrule(
                $mergedConfiguration,
                $superType->getLocalConfiguration()
            );
        }
        $this->fullConfiguration = Arrays::arrayMergeRecursiveOverrule($mergedConfiguration, $this->localConfiguration);

        if (
            isset($this->fullConfiguration['childNodes'])
            && is_array($this->fullConfiguration['childNodes'])
            && $this->fullConfiguration['childNodes'] !== []
        ) {
            $sorter = new PositionalArraySorter($this->fullConfiguration['childNodes']);
            $this->fullConfiguration['childNodes'] = $sorter->toArray();
        }

        return $this->fullConfiguration;
    }

    /**
     * Returns a flat list of super types to inherit from.
     *
     * @return array<string,self>
     */
    protected static function getFlattenedSuperTypes(NodeType $nodeType): array
    {
        $flattenedSuperTypes = [];
        foreach ($nodeType->declaredSuperTypes as $superTypeName => $superType) {
            if ($superType !== null) {
                $flattenedSuperTypes += static::getFlattenedSuperTypes($superType);
                $flattenedSuperTypes[$superTypeName] = $superType;
            }
        }

        foreach ($nodeType->declaredSuperTypes as $superTypeName => $superType) {
            if ($superType === null) {
                unset($flattenedSuperTypes[$superTypeName]);
            }
        }

        return $flattenedSuperTypes;
    }

    /**
     * Iterates through configured postprocessors and invokes them
     *
     * @param array<string,mixed> $fullConfiguration
     * @return array<string,mixed>
     * @throws InvalidNodeTypePostprocessorException
     */
    protected function applyPostprocessing(array $fullConfiguration): array
    {
        if (!isset($fullConfiguration['postprocessors'])) {
            return $fullConfiguration;
        }
        $sortedPostProcessors = (new PositionalArraySorter($this->fullConfiguration['postprocessors']))->toArray();
        foreach ($sortedPostProcessors as $postprocessorConfiguration) {
            $postprocessor = new $postprocessorConfiguration['postprocessor']();
            if (!$postprocessor instanceof NodeTypePostprocessorInterface) {
                throw new InvalidNodeTypePostprocessorException(
                    sprintf(
                        'Expected NodeTypePostprocessorInterface but got "%s"',
                        get_class($postprocessor)
                    ),
                    1364759955
                );
            }
            $postprocessorOptions = [];
            if (isset($postprocessorConfiguration['postprocessorOptions'])) {
                $postprocessorOptions = $postprocessorConfiguration['postprocessorOptions'];
            }
            // TODO: Needs to be made more transparent by returning configuration
            $postprocessor->process($this, $fullConfiguration, $postprocessorOptions);
        }

        return $fullConfiguration;
    }

    /**
     * Return boolean true if marked abstract
     */
    public function isAbstract(): bool
    {
        return $this->abstract;
    }

    /**
     * Return boolean true if marked final
     */
    public function isFinal(): bool
    {
        return $this->final;
    }

    /**
     * Returns the direct, explicitly declared super types
     * of this node type.
     *
     * Note: NULL values are skipped since they are used only internally.
     *
     * @return array<string,NodeType>
     * @api
     */
    public function getDeclaredSuperTypes(): array
    {
        return array_filter($this->declaredSuperTypes, function ($value) {
            return $value !== null;
        });
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
        return $this->getConfiguration('aggregate') === true;
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
        if (!is_string($nodeTypeName)) {
            $nodeTypeName = $nodeTypeName->value;
        }
        if ($nodeTypeName === $this->name->value) {
            return true;
        }
        if (array_key_exists($nodeTypeName, $this->declaredSuperTypes) && $this->declaredSuperTypes[$nodeTypeName] === null) {
            return false;
        }
        foreach ($this->declaredSuperTypes as $superType) {
            if ($superType !== null && $superType->isOfType($nodeTypeName) === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the local configuration of the node type. Should only be used internally.
     *
     * Note: post processing is not applied to this.
     *
     * @return array<string,mixed>
     */
    public function getLocalConfiguration(): array
    {
        return $this->localConfiguration;
    }

    /**
     * Get the full configuration of the node type. Should only be used internally.
     *
     * Instead, use the hasConfiguration()/getConfiguration() methods to check/retrieve single configuration values.
     *
     * @return array<string,mixed>
     */
    public function getFullConfiguration(): array
    {
        $this->initialize();

        return $this->fullConfiguration;
    }

    /**
     * Checks if the configuration of this node type contains a setting for the given $configurationPath
     *
     * @param string $configurationPath The name of the configuration option to verify
     * @api
     */
    public function hasConfiguration(string $configurationPath): bool
    {
        return $this->getConfiguration($configurationPath) !== null;
    }

    /**
     * Returns the configuration option with the specified $configurationPath or NULL if it does not exist
     *
     * @param string $configurationPath The name of the configuration option to retrieve
     * @api
     */
    public function getConfiguration(string $configurationPath): mixed
    {
        $this->initialize();

        return ObjectAccess::getPropertyPath($this->fullConfiguration, $configurationPath);
    }

    /**
     * Get the human-readable label of this node type
     *
     * @api
     */
    public function getLabel(): string
    {
        $this->initialize();

        return $this->fullConfiguration['ui']['label'] ?? '';
    }

    /**
     * Get additional options (if specified)
     *
     * @return array<string,mixed>
     * @api
     */
    public function getOptions(): array
    {
        $this->initialize();

        return ($this->fullConfiguration['options'] ?? []);
    }

    /**
     * Return the node label generator class for the given node
     */
    public function getNodeLabelGenerator(): NodeLabelGeneratorInterface
    {
        $this->initialize();

        if ($this->nodeLabelGenerator === null) {
            $this->nodeLabelGenerator = $this->nodeLabelGeneratorFactory->create($this);
        }

        return $this->nodeLabelGenerator;
    }

    /**
     * Return the array with the defined properties. The key is the property name,
     * the value the property configuration. There are no guarantees on how the
     * property configuration looks like.
     *
     * @return array<string,mixed>
     * @api
     */
    public function getProperties(): array
    {
        $this->initialize();

        return ($this->fullConfiguration['properties'] ?? []);
    }

    /**
     * Check if the property is configured in the schema.
     */
    public function hasReference(string $referenceName): bool
    {
        $this->initialize();

        return isset($this->fullConfiguration['references'][$referenceName]);
    }

    /**
     * Return the array with the defined references. The key is the reference name,
     * the value the reference configuration. There are no guarantees on how the
     * reference configuration looks like.
     *
     * @return array<string,mixed>
     * @api
     */
    public function getReferences(): array
    {
        $this->initialize();

        return ($this->fullConfiguration['references'] ?? []);
    }

    /**
     * Check if the property is configured in the schema.
     */
    public function hasProperty(string $propertyName): bool
    {
        $this->initialize();

        return isset($this->fullConfiguration['properties'][$propertyName]);
    }

    /**
     * Returns the configured type of the specified property, and falls back to 'string'.
     *
     * @throws \InvalidArgumentException if the property is not configured
     */
    public function getPropertyType(string $propertyName): string
    {
        $this->initialize();

        if (!$this->hasProperty($propertyName)) {
            throw new \InvalidArgumentException(
                sprintf('NodeType schema has no property "%s" configured. Cannot read its type.', $propertyName),
                1695062252040
            );
        }

        return $this->fullConfiguration['properties'][$propertyName]['type'] ?? 'string';
    }

    /**
     * Return an array with the defined default values for each property, if any.
     *
     * The default value is configured for each property under the "default" key.
     *
     * @return array<string,mixed>
     * @api
     */
    public function getDefaultValuesForProperties(): array
    {
        $this->initialize();
        if (!isset($this->fullConfiguration['properties'])) {
            return [];
        }

        $defaultValues = [];
        foreach ($this->fullConfiguration['properties'] as $propertyName => $propertyConfiguration) {
            if (is_string($propertyName) && isset($propertyConfiguration['defaultValue'])) {
                $defaultValues[$propertyName] = $propertyConfiguration['defaultValue'];
            }
        }

        return $defaultValues;
    }

    /**
     * @return bool true if $nodeName is an autocreated child node, false otherwise
     */
    public function hasTetheredNode(NodeName $nodeName): bool
    {
        $this->initialize();
        foreach ($this->fullConfiguration['childNodes'] ?? [] as $rawChildNodeName => $configurationForChildNode) {
            if (isset($configurationForChildNode['type'])) {
                if (NodeName::transliterateFromString($rawChildNodeName)->equals($nodeName)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @throws TetheredNodeNotConfigured if the requested tethred node is not configured. Check via {@see NodeType::hasTetheredNode()}.
     */
    public function getNodeTypeNameOfTetheredNode(NodeName $nodeName): NodeTypeName
    {
        $this->initialize();
        foreach ($this->fullConfiguration['childNodes'] ?? [] as $rawChildNodeName => $configurationForChildNode) {
            if (isset($configurationForChildNode['type'])) {
                if (NodeName::transliterateFromString($rawChildNodeName)->equals($nodeName)) {
                    return NodeTypeName::fromString($configurationForChildNode['type']);
                }
            }
        }
        throw new TetheredNodeNotConfigured(sprintf('The child node "%s" is not configured for node type "%s"', $nodeName->value, $this->name->value), 1694786811);
    }

    /**
     * Checks if the given NodeType is acceptable as sub-node with the configured constraints,
     * not taking constraints of auto-created nodes into account. Thus, this method only returns
     * the correct result if called on NON-AUTO-CREATED nodes!
     *
     * Otherwise, isNodeTypeAllowedAsChildToTetheredNode() needs to be called on the *parent node type*.
     *
     * @return boolean true if the $nodeType is allowed as child node, false otherwise.
     */
    public function allowsChildNodeType(NodeType $nodeType): bool
    {
        $constraints = $this->getConfiguration('constraints.nodeTypes') ?: [];
        return ConstraintCheck::create($constraints)->isNodeTypeAllowed($nodeType);
    }

    /**
     * @param array<string,mixed> $fullConfiguration
     */
    protected function setFullConfiguration(array $fullConfiguration): void
    {
        $referencesConfiguration = $fullConfiguration['references'] ?? [];
        foreach ($fullConfiguration['properties'] ?? [] as $propertyName => $propertyConfiguration) {
            $propertyType = $propertyConfiguration['type'] ?? 'string';
            switch ($propertyType) {
                case 'reference':
                    unset($propertyConfiguration['type']);
                    $propertyConfiguration['constraints']['maxItems'] = 1;
                    // @deprecated remove with 10
                    // used to ensure that the FlowQuery property operation will return the node directly but not an array of nodes
                    $propertyConfiguration['__legacyPropertyType'] = 'reference';
                    $referencesConfiguration[$propertyName] = $propertyConfiguration;
                    unset($fullConfiguration['properties'][$propertyName]);
                    break;
                case 'references':
                    unset($propertyConfiguration['type']);
                    $referencesConfiguration[$propertyName] = $propertyConfiguration;
                    unset($fullConfiguration['properties'][$propertyName]);
                    break;
            }
        }
        $fullConfiguration['references'] = $referencesConfiguration;
        $this->fullConfiguration = $fullConfiguration;
    }
}
