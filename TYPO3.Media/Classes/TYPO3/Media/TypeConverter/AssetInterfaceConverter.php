<?php
namespace TYPO3\Media\TypeConverter;

/*
 * This file is part of the TYPO3.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Property\PropertyMappingConfigurationInterface;
use TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter;
use TYPO3\Flow\Resource\Resource;
use TYPO3\Flow\Validation\Validator\UuidValidator;
use TYPO3\Media\Domain\Model\AssetInterface;

/**
 * This converter transforms to \TYPO3\Media\Domain\Model\ImageInterface (Image or ImageVariant) objects.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class AssetInterfaceConverter extends PersistentObjectConverter
{
    /**
     * @var integer
     */
    const CONFIGURATION_ONE_PER_RESOURCE = 'onePerResource';

    /**
     * @var array
     */
    protected $sourceTypes = array('string', 'array');

    /**
     * @var string
     */
    protected $targetType = 'TYPO3\Media\Domain\Model\AssetInterface';

    /**
     * @var integer
     */
    protected $priority = 1;

    /**
     * @Flow\Inject
     * @var \TYPO3\Media\Domain\Repository\AssetRepository
     */
    protected $assetRepository;

    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var \TYPO3\Media\Domain\Strategy\AssetModelMappingStrategyInterface
     */
    protected $assetModelMappingStrategy;

    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Resource\ResourceManager
     */
    protected $resourceManager;

    /**
     * If creating a new asset from this converter this defines the default type as fallback.
     *
     * @var string
     */
    protected static $defaultNewAssetType = 'TYPO3\Media\Domain\Model\Document';

    /**
     * Maps resource identifiers to assets that already got created during the current request.
     *
     * @var array
     */
    protected $resourcesAlreadyConvertedToAssets = array();

    /**
     * If $source has an identity, we have a persisted Image, and therefore
     * this type converter should withdraw and let the PersistedObjectConverter kick in.
     *
     * @param mixed $source The source for the to-build Image
     * @param string $targetType Should always be 'TYPO3\Media\Domain\Model\ImageInterface'
     * @return boolean
     */
    public function canConvertFrom($source, $targetType)
    {
        if (is_string($source) && preg_match(UuidValidator::PATTERN_MATCH_UUID, $source)) {
            return true;
        }
        // TODO: The check for "originalImage" is necessary for smooth migration to the new resource/media management. "originalImage" is deprecated, it can be removed in 3 versions.
        if (is_array($source) && ((isset($source['__identity']) && preg_match(UuidValidator::PATTERN_MATCH_UUID, $source['__identity'])) || isset($source['resource']) || isset($source['originalAsset']) || isset($source['originalImage']))) {
            return true;
        }

        return false;
    }

    /**
     * All properties in the source array except __identity and adjustments are sub-properties.
     *
     * @param mixed $source
     * @return array
     */
    public function getSourceChildPropertiesToBeConverted($source)
    {
        if (is_string($source)) {
            return array();
        }
        return parent::getSourceChildPropertiesToBeConverted($source);
    }

    /**
     * Convert the property "resource"
     *
     * @param string $targetType
     * @param string $propertyName
     * @param PropertyMappingConfigurationInterface $configuration
     * @return string
     */
    public function getTypeOfChildProperty($targetType, $propertyName, PropertyMappingConfigurationInterface $configuration)
    {
        switch ($propertyName) {
            case 'resource':
                return 'TYPO3\Flow\Resource\Resource';
            case 'originalAsset':
                return 'TYPO3\Media\Domain\Model\Image';
            case 'title':
                return 'string';
        }
        return parent::getTypeOfChildProperty($targetType, $propertyName, $configuration);
    }


    /**
     * Determines the target type based on the source's (optional) __type key.
     *
     * @param mixed $source
     * @param string $originalTargetType
     * @param PropertyMappingConfigurationInterface $configuration
     * @return string
     * @throws \TYPO3\Flow\Property\Exception\InvalidDataTypeException
     * @throws \TYPO3\Flow\Property\Exception\InvalidPropertyMappingConfigurationException
     * @throws \InvalidArgumentException
     */
    public function getTargetTypeForSource($source, $originalTargetType, PropertyMappingConfigurationInterface $configuration = null)
    {
        $targetType = $originalTargetType;

        if (is_array($source) && array_key_exists('__type', $source)) {
            $targetType = $source['__type'];

            if ($configuration === null) {
                throw new \InvalidArgumentException('A property mapping configuration must be given, not NULL.', 1421443628);
            }
            if ($configuration->getConfigurationValue('TYPO3\Flow\Property\TypeConverter\ObjectConverter', self::CONFIGURATION_OVERRIDE_TARGET_TYPE_ALLOWED) !== true) {
                throw new \TYPO3\Flow\Property\Exception\InvalidPropertyMappingConfigurationException('Override of target type not allowed. To enable this, you need to set the PropertyMappingConfiguration Value "CONFIGURATION_OVERRIDE_TARGET_TYPE_ALLOWED" to TRUE.', 1421443641);
            }

            if ($targetType !== $originalTargetType && is_a($targetType, $originalTargetType, true) === false) {
                throw new \TYPO3\Flow\Property\Exception\InvalidDataTypeException('The given type "' . $targetType . '" is not a subtype of "' . $originalTargetType . '".', 1421443648);
            }
        }

        return $targetType;
    }

    /**
     * Convert an object from $source to an \TYPO3\Media\Domain\Model\AssetInterface implementation
     *
     * @param mixed $source
     * @param string $targetType must implement 'TYPO3\Media\Domain\Model\AssetInterface'
     * @param array $convertedChildProperties
     * @param PropertyMappingConfigurationInterface $configuration
     * @return \TYPO3\Flow\Validation\Error|\TYPO3\Media\Domain\Model\Image The converted Image, a Validation Error or NULL
     * @throws \TYPO3\Flow\Property\Exception\InvalidTargetException
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = array(), PropertyMappingConfigurationInterface $configuration = null)
    {
        $object = null;
        if (is_string($source) && $source !== '') {
            $source = array('__identity' => $source);
        }

        if (isset($convertedChildProperties['resource']) && $convertedChildProperties['resource'] instanceof Resource) {
            $resource = $convertedChildProperties['resource'];
            if (isset($this->resourcesAlreadyConvertedToAssets[$resource->getSha1()])) {
                $object = $this->resourcesAlreadyConvertedToAssets[$resource->getSha1()];
            }
            // This is pretty late to override the targetType, but usually you want to determine the model type from the resource when a new resource was uploaded...
            $targetType = $this->applyModelMappingStrategy($targetType, $resource, $source);
        }

        if ($object === null) {
            if ($configuration !== null && $configuration->getConfigurationValue(self::class, self::CONFIGURATION_ONE_PER_RESOURCE) === true && isset($convertedChildProperties['resource'])) {
                $resource = $convertedChildProperties['resource'];
                $possibleAsset = $this->assetRepository->findOneByResourceSha1($resource->getSha1());
                if ($possibleAsset !== null) {
                    $this->resourceManager->deleteResource($resource);
                    return $possibleAsset;
                }
            }
            $object = parent::convertFrom($source, $targetType, $convertedChildProperties, $configuration);
        }

        if ($object instanceof AssetInterface) {
            $object = $this->applyTypeSpecificHandling($object, $source, $convertedChildProperties, $configuration);
            if ($object !== null) {
                $this->resourcesAlreadyConvertedToAssets[$object->getResource()->getSha1()] = $object;
                if (isset($resource) && $resource !== $object->getResource()) {
                    $this->resourceManager->deleteResource($resource);
                }
            }
        }

        return $object;
    }

    /**
     * Builds a new instance of $objectType with the given $possibleConstructorArgumentValues.
     * If constructor argument values are missing from the given array the method looks for a
     * default value in the constructor signature.
     *
     * Furthermore, the constructor arguments are removed from $possibleConstructorArgumentValues
     *
     * @param array &$possibleConstructorArgumentValues
     * @param string $objectType
     * @return object The created instance
     * @throws \TYPO3\Flow\Property\Exception\InvalidTargetException if a required constructor argument is missing
     */
    protected function buildObject(array &$possibleConstructorArgumentValues, $objectType)
    {
        $className = $this->objectManager->getClassNameByObjectName($objectType) ?: static::$defaultNewAssetType;
        if (count($possibleConstructorArgumentValues)) {
            return parent::buildObject($possibleConstructorArgumentValues, $className);
        } else {
            return null;
        }
    }

    /**
     * Fetch an object from persistence layer.
     *
     * @param mixed $identity
     * @param string $targetType
     * @return object
     * @throws \TYPO3\Flow\Property\Exception\TargetNotFoundException
     * @throws \TYPO3\Flow\Property\Exception\InvalidSourceException
     */
    protected function fetchObjectFromPersistence($identity, $targetType)
    {
        if ($targetType === 'TYPO3\Media\Domain\Model\Thumbnail') {
            $object = $this->persistenceManager->getObjectByIdentifier($identity, $targetType);
        } elseif (is_string($identity)) {
            $object = $this->assetRepository->findByIdentifier($identity);
        } else {
            throw new \TYPO3\Flow\Property\Exception\InvalidSourceException('The identity property "' . $identity . '" is not a string.', 1415817618);
        }

        return $object;
    }

    /**
     * @param AssetInterface $asset
     * @param mixed $source
     * @param array $convertedChildProperties
     * @param PropertyMappingConfigurationInterface $configuration
     * @return AssetInterface|NULL
     */
    protected function applyTypeSpecificHandling($asset, $source, array $convertedChildProperties, PropertyMappingConfigurationInterface $configuration)
    {
        return $asset;
    }

    /**
     * Applies the model mapping strategy for a converted resource to determine the final target type.
     * The strategy is NOT applied if $source['__type'] is set (overriding was allowed then, otherwise an exception would have been thrown earlier).
     *
     * @param string $originalTargetType The original target type determined so far
     * @param Resource $resource The resource that is to be converted to a media file.
     * @param array $source the original source properties for this type converter.
     * @return string Class name of the media model to use for the given resource
     */
    protected function applyModelMappingStrategy($originalTargetType, Resource $resource, array $source = array())
    {
        $finalTargetType = $originalTargetType;
        if (!isset($source['__type'])) {
            $finalTargetType = $this->assetModelMappingStrategy->map($resource, $source);
        }

        return $finalTargetType;
    }
}
