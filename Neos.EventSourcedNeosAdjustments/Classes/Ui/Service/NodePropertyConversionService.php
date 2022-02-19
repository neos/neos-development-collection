<?php
declare(strict_types=1);
namespace Neos\EventSourcedNeosAdjustments\Ui\Service;

/*
 * This file is part of the Neos.Neos.Ui package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\MvcPropertyMappingConfiguration;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Property\TypeConverter\PersistentObjectConverter;
use Neos\Utility\Exception\InvalidTypeException;
use Neos\Utility\TypeHandling;

/**
 * @Flow\Scope("singleton")
 */
class NodePropertyConversionService
{

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
     * Convert raw property values to the correct type according to a node type configuration
     *
     * @param NodeType $nodeType
     * @param string $propertyName
     * @param string $rawValue
     * @param Context $context
     * @return mixed
     */
    public function convert(NodeType $nodeType, $propertyName, $rawValue)
    {
        // WORKAROUND: $nodeType->getPropertyType() is missing the "initialize" call,
        // so we need to trigger another method beforehand.
        $nodeType->getFullConfiguration();
        $propertyType = $nodeType->getPropertyType($propertyName);

        switch ($propertyType) {
            case 'string':
                return $rawValue;

            case 'reference':
                throw new \Exception("TODO FIX");
                return $this->convertReference($rawValue, $context);

            case 'references':
                throw new \Exception("TODO FIX");
                return $this->convertReferences($rawValue, $context);

            case 'DateTime':
                return $this->convertDateTime($rawValue);

            case 'integer':
                return $this->convertInteger($rawValue);

            case 'boolean':
                return $this->convertBoolean($rawValue);

            case 'array':
                return $this->convertArray($rawValue);

            default:
                $innerType = $propertyType;
                if ($propertyType !== null) {
                    try {
                        $parsedType = TypeHandling::parseType($propertyType);
                        $innerType = $parsedType['elementType'] ?: $parsedType['type'];
                    } catch (InvalidTypeException $exception) {
                    }
                }

                if ((is_string($rawValue) || is_array($rawValue))
                    && $this->objectManager->isRegistered($innerType) && $rawValue !== '') {
                    $propertyMappingConfiguration = new MvcPropertyMappingConfiguration();
                    $propertyMappingConfiguration->allowOverrideTargetType();
                    $propertyMappingConfiguration->allowAllProperties();
                    $propertyMappingConfiguration->skipUnknownProperties();
                    $propertyMappingConfiguration->setTypeConverterOption(
                        PersistentObjectConverter::class,
                        PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED,
                        true
                    );
                    $propertyMappingConfiguration->setTypeConverterOption(
                        PersistentObjectConverter::class,
                        PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED,
                        true
                    );

                    return $this->propertyMapper->convert($rawValue, $propertyType, $propertyMappingConfiguration);
                } else {
                    return $rawValue;
                }
        }
    }

    /**
     * Convert raw value to reference
     *
     * @param string $rawValue
     * @param Context $context
     * @return NodeInterface
     */
    protected function convertReference($rawValue, Context $context)
    {
        return $context->getNodeByIdentifier($rawValue);
    }

    /**
     * Convert raw value to references
     *
     * @param string $rawValue
     * @param Context $context
     * @return array<NodeInterface>
     */
    protected function convertReferences($rawValue, Context $context)
    {
        $nodeIdentifiers = $rawValue;
        $result = [];

        if (is_array($nodeIdentifiers)) {
            foreach ($nodeIdentifiers as $nodeIdentifier) {
                $referencedNode = $context->getNodeByIdentifier($nodeIdentifier);
                if ($referencedNode !== null) {
                    $result[] = $referencedNode;
                }
            }
        }

        return $result;
    }

    /**
     * Convert raw value to \DateTime
     *
     * @param string $rawValue
     * @return \DateTime|null
     */
    protected function convertDateTime($rawValue)
    {
        if ($rawValue !== '') {
            $result = \DateTime::createFromFormat(\DateTime::W3C, $rawValue);
            $result->setTimezone(new \DateTimeZone(date_default_timezone_get()));

            return $result;
        }
    }

    /**
     * Convert raw value to integer
     *
     * @param mixed $rawValue
     * @return integer
     */
    protected function convertInteger($rawValue)
    {
        return (int)$rawValue;
    }

    /**
     * Convert raw value to boolean
     *
     * @param mixed $rawValue
     * @return boolean
     */
    protected function convertBoolean($rawValue)
    {
        if (is_string($rawValue) && strtolower($rawValue) === 'false') {
            return false;
        }

        return (bool)$rawValue;
    }

    /**
     * Convert raw value to array
     *
     * @param string|array $rawValue
     * @return array
     */
    protected function convertArray($rawValue)
    {
        if (is_string($rawValue)) {
            return json_decode($rawValue, true);
        }

        return $rawValue;
    }
}
