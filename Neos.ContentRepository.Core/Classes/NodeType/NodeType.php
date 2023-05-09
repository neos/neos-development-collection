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


use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeNotFoundException;
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
 * TODO: REFACTOR TO immutable readonly; and value objects
 *
 * TODO: I'd love to make NodeType final; but this breaks quite some unit and functional tests.
 *
 * @api
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
    public bool $abstract = false;

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
     * Constructs this node type
     *
     * @param NodeTypeName $name Name of the node type
     * @param array<string,mixed> $declaredSuperTypes Parent types of this node type
     * @param array<string,mixed> $configuration the configuration for this node type which is defined in the schema
     * @throws \InvalidArgumentException
     */
    public function __construct(
        NodeTypeName $name,
        array $declaredSuperTypes,
        array $configuration,
        private readonly NodeTypeManager $nodeTypeManager,
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
     * Returns the name of this node type
     * @deprecated use "name" property directly
     */
    public function getName(): string
    {
        return $this->name->value;
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
    public function isOfType(string $nodeType): bool
    {
        if ($nodeType === $this->name->value) {
            return true;
        }
        if (array_key_exists($nodeType, $this->declaredSuperTypes) && $this->declaredSuperTypes[$nodeType] === null) {
            return false;
        }
        foreach ($this->declaredSuperTypes as $superType) {
            if ($superType !== null && $superType->isOfType($nodeType) === true) {
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
     * Returns the configured type of the specified property
     *
     * @param string $propertyName Name of the property
     */
    public function getPropertyType(string $propertyName): string
    {
        $this->initialize();

        if (
            !isset($this->fullConfiguration['properties'])
            || !isset($this->fullConfiguration['properties'][$propertyName])
            || !isset($this->fullConfiguration['properties'][$propertyName]['type'])
        ) {
            return 'string';
        }
        return $this->fullConfiguration['properties'][$propertyName]['type'];
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
     * Return an array with child nodes which should be automatically created
     *
     * @return array<string,self> the key of this array is the name of the child, and the value its NodeType.
     * @api
     */
    public function getAutoCreatedChildNodes(): array
    {
        $this->initialize();
        if (!isset($this->fullConfiguration['childNodes'])) {
            return [];
        }

        $autoCreatedChildNodes = [];
        foreach ($this->fullConfiguration['childNodes'] as $childNodeName => $childNodeConfiguration) {
            if (isset($childNodeConfiguration['type'])) {
                $autoCreatedChildNodes[NodeName::transliterateFromString($childNodeName)->value]
                    = $this->nodeTypeManager->getNodeType($childNodeConfiguration['type']);
            }
        }

        return $autoCreatedChildNodes;
    }

    /**
     * @return bool true if $nodeName is an autocreated child node, false otherwise
     */
    public function hasAutoCreatedChildNode(NodeName $nodeName): bool
    {
        $this->initialize();
        return isset($this->fullConfiguration['childNodes'][$nodeName->value]);
    }

    /**
     * @throws NodeTypeNotFoundException
     */
    public function getTypeOfAutoCreatedChildNode(NodeName $nodeName): ?NodeType
    {
        return isset($this->fullConfiguration['childNodes'][$nodeName->value]['type'])
            ? $this->nodeTypeManager->getNodeType($this->fullConfiguration['childNodes'][$nodeName->value]['type'])
            : null;
    }


    /**
     * Checks if the given NodeType is acceptable as sub-node with the configured constraints,
     * not taking constraints of auto-created nodes into account. Thus, this method only returns
     * the correct result if called on NON-AUTO-CREATED nodes!
     *
     * Otherwise, allowsGrandchildNodeType() needs to be called on the *parent node type*.
     *
     * @return boolean true if the $nodeType is allowed as child node, false otherwise.
     */
    public function allowsChildNodeType(NodeType $nodeType): bool
    {
        $constraints = $this->getConfiguration('constraints.nodeTypes') ?: [];

        return $this->isNodeTypeAllowedByConstraints($nodeType, $constraints);
    }

    /**
     * Checks if the given $nodeType is allowed as a childNode of the given $childNodeName
     * (which must be auto-created in $this NodeType).
     *
     * Only allowed to be called if $childNodeName is auto-created.
     *
     * @param string $childNodeName The name of a configured childNode of this NodeType
     * @param NodeType $nodeType The NodeType to check constraints for.
     * @return bool true if the $nodeType is allowed as grandchild node, false otherwise.
     * @throws \InvalidArgumentException If the given $childNodeName is not configured to be auto-created in $this.
     */
    public function allowsGrandchildNodeType(string $childNodeName, NodeType $nodeType): bool
    {
        $autoCreatedChildNodes = $this->getAutoCreatedChildNodes();
        if (!isset($autoCreatedChildNodes[$childNodeName])) {
            throw new \InvalidArgumentException(
                'The method "allowsGrandchildNodeType" can only be used on auto-created childNodes, '
                    . 'given $childNodeName "' . $childNodeName . '" is not auto-created.',
                1403858395
            );
        }
        $constraints = $autoCreatedChildNodes[$childNodeName]->getConfiguration('constraints.nodeTypes') ?: [];

        $childNodeConfiguration = [];
        foreach ($this->getConfiguration('childNodes') as $name => $configuration) {
            $childNodeConfiguration[NodeName::transliterateFromString($name)->value] = $configuration;
        }
        $childNodeConstraintConfiguration = ObjectAccess::getPropertyPath(
            $childNodeConfiguration,
            $childNodeName . '.constraints.nodeTypes'
        ) ?: [];

        $constraints = Arrays::arrayMergeRecursiveOverrule($constraints, $childNodeConstraintConfiguration);

        return $this->isNodeTypeAllowedByConstraints($nodeType, $constraints);
    }

    /**
     * Internal method to check whether the passed-in $nodeType is allowed by the $constraints array.
     *
     * $constraints is an associative array where the key is the Node Type Name. If the value is "true",
     * the node type is explicitly allowed. If the value is "false", the node type is explicitly denied.
     * If nothing is specified, the fallback "*" is used. If that one is also not specified, we DENY by
     * default.
     *
     * Super types of the given node types are also checked, so if a super type is constrained
     * it will also take affect on the inherited node types. The closest constrained super type match is used.
     *
     * @param array<string,mixed> $constraints
     */
    protected function isNodeTypeAllowedByConstraints(NodeType $nodeType, array $constraints): bool
    {
        $directConstraintsResult = $this->isNodeTypeAllowedByDirectConstraints($nodeType, $constraints);
        if ($directConstraintsResult !== null) {
            return $directConstraintsResult;
        }

        $inheritanceConstraintsResult = $this->isNodeTypeAllowedByInheritanceConstraints($nodeType, $constraints);
        if ($inheritanceConstraintsResult !== null) {
            return $inheritanceConstraintsResult;
        }

        if (isset($constraints['*'])) {
            return (bool)$constraints['*'];
        }

        return false;
    }

    /**
     * @param array<string,mixed> $constraints
     * @return boolean true if the passed $nodeType is allowed by the $constraints
     */
    protected function isNodeTypeAllowedByDirectConstraints(NodeType $nodeType, array $constraints): ?bool
    {
        if ($constraints === []) {
            return true;
        }

        if (
            array_key_exists($nodeType->name->value, $constraints)
            && $constraints[$nodeType->name->value] === true
        ) {
            return true;
        }

        if (
            array_key_exists($nodeType->name->value, $constraints)
            && $constraints[$nodeType->name->value] === false
        ) {
            return false;
        }

        return null;
    }

    /**
     * This method loops over the constraints and finds node types that the given node type inherits from. For all
     * matched super types, their super types are traversed to find the closest super node with a constraint which
     * is used to evaluated if the node type is allowed. It finds the closest results for true and false, and uses
     * the distance to choose which one wins (lowest). If no result is found the node type is allowed.
     *
     * @param array<string,mixed> $constraints
     * @return ?boolean (null if no constraint matched)
     */
    protected function isNodeTypeAllowedByInheritanceConstraints(NodeType $nodeType, array $constraints): ?bool
    {
        $constraintDistanceForTrue = null;
        $constraintDistanceForFalse = null;
        foreach ($constraints as $superType => $constraint) {
            if ($nodeType->isOfType($superType)) {
                $distance = $this->traverseSuperTypes($nodeType, $superType, 0);

                if (
                    $constraint === true
                    && ($constraintDistanceForTrue === null || $constraintDistanceForTrue > $distance)
                ) {
                    $constraintDistanceForTrue = $distance;
                }
                if (
                    $constraint === false
                    && ($constraintDistanceForFalse === null || $constraintDistanceForFalse > $distance)
                ) {
                    $constraintDistanceForFalse = $distance;
                }
            }
        }

        if ($constraintDistanceForTrue !== null && $constraintDistanceForFalse !== null) {
            return $constraintDistanceForTrue < $constraintDistanceForFalse;
        }

        if ($constraintDistanceForFalse !== null) {
            return false;
        }

        if ($constraintDistanceForTrue !== null) {
            return true;
        }

        return null;
    }

    /**
     * This method traverses the given node type to find the first super type that matches the constraint node type.
     * In case the hierarchy has more than one way of finding a path to the node type it's not taken into account,
     * since the first matched is returned. This is accepted on purpose for performance reasons and due to the fact
     * that such hierarchies should be avoided.
     *
     * Returns null if no NodeType matched
     */
    protected function traverseSuperTypes(
        NodeType $currentNodeType,
        string $constraintNodeTypeName,
        int $distance
    ): ?int {
        if ($currentNodeType->getName() === $constraintNodeTypeName) {
            return $distance;
        }

        $distance++;
        foreach ($currentNodeType->getDeclaredSuperTypes() as $superType) {
            $result = $this->traverseSuperTypes($superType, $constraintNodeTypeName, $distance);
            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }

    /**
     * @param array<string,mixed> $fullConfiguration
     */
    protected function setFullConfiguration(array $fullConfiguration): void
    {
        $this->fullConfiguration = $fullConfiguration;
    }
}
