<?php
namespace Neos\ContentRepository\TypeConverter;

use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\Model\WriteablePropertiesInterface;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Property\Exception\TypeConverterException;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\Utility\Exception\InvalidTypeException;
use Neos\Utility\ObjectAccess;
use Neos\Utility\TypeHandling;

/**
 * A trait that abstracts functionality needed in various converters for models of the content repository.
 *
 */
trait NodeLikeConverterHelperTrait
{
    /**
     * Iterates through the given $properties setting them on the specified $node using the appropriate TypeConverters.
     *
     * @param WriteablePropertiesInterface $node
     * @param NodeType $nodeType
     * @param array $properties
     * @param Context $context
     * @param ObjectManagerInterface $objectManager
     * @param PropertyMapper $propertyMapper
     * @param PropertyMappingConfigurationInterface $configuration
     * @return void
     * @throws TypeConverterException
     */
    protected function _setNodeProperties(WriteablePropertiesInterface $node, NodeType $nodeType, array $properties, Context $context, ObjectManagerInterface $objectManager, PropertyMapper $propertyMapper, PropertyMappingConfigurationInterface $configuration = null)
    {
        $nodeTypeProperties = $nodeType->getProperties();
        unset($properties['_lastPublicationDateTime']);

        foreach ($properties as $nodePropertyName => $nodePropertyValue) {
            if (substr($nodePropertyName, 0, 2) === '__') {
                continue;
            }
            $nodePropertyType = isset($nodeTypeProperties[$nodePropertyName]['type']) ? $nodeTypeProperties[$nodePropertyName]['type'] : null;
            switch ($nodePropertyType) {
                case 'reference':
                    $nodePropertyValue = $context->getNodeByIdentifier($nodePropertyValue);
                    break;
                case 'references':
                    $nodeIdentifiers = json_decode($nodePropertyValue);
                    $nodePropertyValue = [];
                    if (is_array($nodeIdentifiers)) {
                        foreach ($nodeIdentifiers as $nodeIdentifier) {
                            $referencedNode = $context->getNodeByIdentifier($nodeIdentifier);
                            if ($referencedNode !== null) {
                                $nodePropertyValue[] = $referencedNode;
                            }
                        }
                    } elseif ($nodeIdentifiers !== null) {
                        throw new TypeConverterException(sprintf('node type "%s" expects an array of identifiers for its property "%s"', $nodeType->getName(), $nodePropertyName), 1383587419);
                    }
                    break;
                case 'DateTime':
                    if ($nodePropertyValue !== '' && ($nodePropertyValue = \DateTime::createFromFormat(\DateTime::W3C, $nodePropertyValue)) !== false) {
                        $nodePropertyValue->setTimezone(new \DateTimeZone(date_default_timezone_get()));
                    } else {
                        $nodePropertyValue = null;
                    }
                    break;
                case 'integer':
                    $nodePropertyValue = intval($nodePropertyValue);
                    break;
                case 'boolean':
                    if (is_string($nodePropertyValue)) {
                        $nodePropertyValue = $nodePropertyValue === 'true' ? true : false;
                    }
                    break;
                case 'array':
                    $nodePropertyValue = json_decode($nodePropertyValue, true);
                    break;
            }
            if (substr($nodePropertyName, 0, 1) === '_') {
                $nodePropertyName = substr($nodePropertyName, 1);
                ObjectAccess::setProperty($node, $nodePropertyName, $nodePropertyValue);
                continue;
            }

            if (!isset($nodeTypeProperties[$nodePropertyName])) {
                if ($configuration !== null && $configuration->shouldSkipUnknownProperties()) {
                    continue;
                } else {
                    throw new TypeConverterException(sprintf('Node type "%s" does not have a property "%s" according to the schema', $nodeType->getName(), $nodePropertyName), 1359552744);
                }
            }
            $innerType = $nodePropertyType;
            if ($nodePropertyType !== null) {
                try {
                    $parsedType = TypeHandling::parseType($nodePropertyType);
                    $innerType = $parsedType['elementType'] ?: $parsedType['type'];
                } catch (InvalidTypeException $exception) {
                }
            }

            if (is_string($nodePropertyValue) && $objectManager->isRegistered($innerType) && $nodePropertyValue !== '') {
                $nodePropertyValue = $propertyMapper->convert(json_decode($nodePropertyValue, true), $nodePropertyType, $configuration);
            }
            $node->setProperty($nodePropertyName, $nodePropertyValue);
        }
    }

    /**
     * Prepares the context properties for the nodes based on the given workspace and dimensions
     *
     * @param array $source
     * @param PropertyMappingConfigurationInterface $configuration
     * @return array
     */
    protected function prepareContextProperties(array $source, PropertyMappingConfigurationInterface $configuration = null)
    {
        $contextProperties = [
            'workspaceName' => $source['__workspaceName'] ?? 'live',
            'invisibleContentShown' => false,
            'removedContentShown' => false
        ];
        if ($contextProperties['workspaceName'] !== 'live') {
            $contextProperties['invisibleContentShown'] = true;
            if ($configuration !== null && $configuration->getConfigurationValue(self::class, self::REMOVED_CONTENT_SHOWN) === true) {
                $contextProperties['removedContentShown'] = true;
            }
        }

        $contextProperties['dimensions'] = $source['__dimensions'] ?? [];

        return $contextProperties;
    }
}
