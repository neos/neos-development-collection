<?php
namespace Neos\Neos\Service\Mapping;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\SystemLoggerInterface;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Property\Exception as PropertyException;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Property\PropertyMappingConfiguration;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\Utility\ObjectAccess;
use Neos\Utility\TypeHandling;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\NodeType;

/**
 * Creates PropertyMappingConfigurations to map NodeType properties for the Neos interface.
 *
 * @Flow\Scope("singleton")
 */
class NodePropertyConverterService
{
    /**
     * @Flow\InjectConfiguration(package="Neos.Neos", path="userInterface.inspector.dataTypes")
     * @var array
     */
    protected $typesConfiguration;

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @Flow\Inject
     * @var PropertyMapper
     */
    protected $propertyMapper;

    /**
     * @Flow\Transient
     * @var array
     */
    protected $generatedPropertyMappingConfigurations = [];

    /**
     * @Flow\Inject
     * @var SystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * Get a single property reduced to a simple type (no objects) representation
     *
     * @param NodeInterface $node
     * @param string $propertyName
     * @return mixed
     */
    public function getProperty(NodeInterface $node, $propertyName)
    {
        if ($propertyName[0] === '_') {
            $propertyValue = ObjectAccess::getProperty($node, ltrim($propertyName, '_'));
        } else {
            $propertyValue = $node->getProperty($propertyName);
        }

        $dataType = $node->getNodeType()->getPropertyType($propertyName);
        try {
            $convertedValue = $this->convertValue($propertyValue, $dataType);
        } catch (PropertyException $exception) {
            $this->systemLogger->logException($exception);
            $convertedValue = null;
        }

        if ($convertedValue === null) {
            $convertedValue = $this->getDefaultValueForProperty($node->getNodeType(), $propertyName);
        }

        return $convertedValue;
    }

    /**
     * Get all properties as JSON encoded string representation
     *
     * @param NodeInterface $node
     * @return string
     */
    public function getPropertiesJson(NodeInterface $node)
    {
        return json_encode($this->getPropertiesArray($node));
    }

    /**
     * Get all properties reduced to simple type (no objects) representations in an array
     *
     * @param NodeInterface $node
     * @return array
     */
    public function getPropertiesArray(NodeInterface $node)
    {
        $properties = [];
        foreach ($node->getNodeType()->getProperties() as $propertyName => $propertyConfiguration) {
            if ($propertyName[0] === '_' && $propertyName[1] === '_') {
                // skip fully-private properties
                continue;
            }

            $properties[$propertyName] = $this->getProperty($node, $propertyName);
        }

        return $properties;
    }

    /**
     * @param mixed $propertyValue
     * @param string $dataType
     * @return mixed
     * @throws PropertyException
     */
    protected function convertValue($propertyValue, $dataType)
    {
        $parsedType = TypeHandling::parseType($dataType);

        // This hardcoded handling is to circumvent rewriting PropertyMappers that convert objects. Usually they expect the source to be an object already and break if not.
        if (!TypeHandling::isSimpleType($parsedType['type']) && !is_object($propertyValue) && !is_array($propertyValue)) {
            return null;
        }

        $conversionTargetType = $parsedType['type'];
        if (!TypeHandling::isSimpleType($parsedType['type'])) {
            $conversionTargetType = 'array';
        }
        if ($parsedType['type'] === 'array' && $parsedType['elementType'] !== null) {
            $conversionTargetType .= '<' . $parsedType['elementType'] . '>';
        }

        $propertyMappingConfiguration = $this->createConfiguration($dataType);
        $convertedValue = $this->propertyMapper->convert($propertyValue, $conversionTargetType, $propertyMappingConfiguration);

        if ($convertedValue instanceof \Neos\Error\Messages\Error) {
            throw new PropertyException($convertedValue->getMessage(), $convertedValue->getCode());
        }

        return $convertedValue;
    }

    /**
     * Tries to find a default value for the given property trying:
     * 1) The specific property configuration for the given NodeType
     * 2) The generic configuration for the property type in settings.
     *
     * @param NodeType $nodeType
     * @param string $propertyName
     * @return mixed
     */
    protected function getDefaultValueForProperty(NodeType $nodeType, $propertyName)
    {
        $defaultValues = $nodeType->getDefaultValuesForProperties();
        if (!isset($defaultValues[$propertyName])) {
            return null;
        }

        return $defaultValues[$propertyName];
    }

    /**
     * Create a property mapping configuration for the given dataType to convert a Node property value from the given dataType to a simple type.
     *
     * @param string $dataType
     * @return PropertyMappingConfigurationInterface
     */
    protected function createConfiguration($dataType)
    {
        if (!isset($this->generatedPropertyMappingConfigurations[$dataType])) {
            $propertyMappingConfiguration = new PropertyMappingConfiguration();
            $propertyMappingConfiguration->allowAllProperties();

            $parsedType = [
                'elementType' => null,
                'type' => $dataType
            ];
            // Special handling for "reference(s)", should be deprecated and normlized to array<NodeInterface>
            if ($dataType !== 'references' && $dataType !== 'reference') {
                $parsedType = TypeHandling::parseType($dataType);
            }

            if ($this->setTypeConverterForType($propertyMappingConfiguration, $dataType) === false) {
                $this->setTypeConverterForType($propertyMappingConfiguration, $parsedType['type']);
            }

            $elementConfiguration = $propertyMappingConfiguration->forProperty('*');
            $this->setTypeConverterForType($elementConfiguration, $parsedType['elementType']);

            $this->generatedPropertyMappingConfigurations[$dataType] = $propertyMappingConfiguration;
        }

        return $this->generatedPropertyMappingConfigurations[$dataType];
    }

    /**
     * @param PropertyMappingConfiguration $propertyMappingConfiguration
     * @param string $dataType
     * @return boolean
     */
    protected function setTypeConverterForType(PropertyMappingConfiguration $propertyMappingConfiguration, $dataType)
    {
        if (!isset($this->typesConfiguration[$dataType]) || !isset($this->typesConfiguration[$dataType]['typeConverter'])) {
            return false;
        }

        $typeConverter = $this->objectManager->get($this->typesConfiguration[$dataType]['typeConverter']);
        $propertyMappingConfiguration->setTypeConverter($typeConverter);
        $this->setTypeConverterOptionsForType($propertyMappingConfiguration, $this->typesConfiguration[$dataType]['typeConverter'], $dataType);

        return true;
    }

    /**
     * @param PropertyMappingConfiguration $propertyMappingConfiguration
     * @param string $typeConverterClass
     * @param string $dataType
     * @return void
     */
    protected function setTypeConverterOptionsForType(PropertyMappingConfiguration $propertyMappingConfiguration, $typeConverterClass, $dataType)
    {
        if (!isset($this->typesConfiguration[$dataType]['typeConverterOptions']) || !is_array($this->typesConfiguration[$dataType]['typeConverterOptions'])) {
            return;
        }

        foreach ($this->typesConfiguration[$dataType]['typeConverterOptions'] as $option => $value) {
            $propertyMappingConfiguration->setTypeConverterOption($typeConverterClass, $option, $value);
        }
    }
}
