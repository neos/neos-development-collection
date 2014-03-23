<?php
namespace TYPO3\Media\TypeConverter;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Media".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Property\PropertyMappingConfigurationInterface;
use TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter;
use TYPO3\Flow\Resource\Resource;
use TYPO3\Media\Domain\Model\AssetInterface;

/**
 * This converter transforms to \TYPO3\Media\Domain\Model\ImageInterface (Image or ImageVariant) objects.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class AssetInterfaceConverter extends PersistentObjectConverter {

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
	public function canConvertFrom($source, $targetType) {
		if (is_string($source) && preg_match(\TYPO3\Flow\Validation\Validator\UuidValidator::PATTERN_MATCH_UUID, $source)) {
			return TRUE;
		}
		// TODO: The check for "originalImage" is necessary for smooth migration to the new resource/media management. "originalImage" is deprecated, it can be removed in 3 versions.
		if (is_array($source) && ((isset($source['__identity']) && preg_match(\TYPO3\Flow\Validation\Validator\UuidValidator::PATTERN_MATCH_UUID, $source['__identity'])) || isset($source['resource']) || isset($source['originalAsset']) || isset($source['originalImage']))) {
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * All properties in the source array except __identity and adjustments are sub-properties.
	 *
	 * @param mixed $source
	 * @return array
	 */
	public function getSourceChildPropertiesToBeConverted($source) {
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
	 * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration
	 * @return string
	 */
	public function getTypeOfChildProperty($targetType, $propertyName, \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration) {
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
	 * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration
	 * @return string
	 * @throws \TYPO3\Flow\Property\Exception\InvalidDataTypeException
	 * @throws \TYPO3\Flow\Property\Exception\InvalidPropertyMappingConfigurationException
	 * @throws \InvalidArgumentException
	 */
	public function getTargetTypeForSource($source, $originalTargetType, \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		$targetType = $originalTargetType;
		if (is_array($source) && array_key_exists('__type', $source)) {
			$targetType = $source['__type'];
			if ($targetType !== $originalTargetType && is_a($targetType, $originalTargetType, TRUE) === FALSE) {
				throw new \TYPO3\Flow\Property\Exception\InvalidDataTypeException('The given type "' . $targetType . '" is not a subtype of "' . $originalTargetType . '".', 1317048056);
			}
		}

		return $targetType;
	}

	/**
	 * Convert an object from $source to an \TYPO3\Media\Domain\Model\AssetInterface implementation
	 *
	 * @param mixed $source
	 * @param string $targetType must be 'TYPO3\Media\Domain\Model\Image'
	 * @param array $convertedChildProperties
	 * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration
	 * @return \TYPO3\Flow\Validation\Error|\TYPO3\Media\Domain\Model\Image The converted Image, a Validation Error or NULL
	 * @throws \TYPO3\Flow\Property\Exception\InvalidTargetException
	 */
	public function convertFrom($source, $targetType, array $convertedChildProperties = array(), \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		if (is_string($source) && $source !== '') {
			$source = array('__identity' => $source);
		}

		if (isset($convertedChildProperties['resource']) && $convertedChildProperties['resource'] instanceof Resource) {
			$resourceIdentifier = $this->persistenceManager->getIdentifierByObject($convertedChildProperties['resource']);
			if (isset($this->resourcesAlreadyConvertedToAssets[$resourceIdentifier])) {
				return $this->resourcesAlreadyConvertedToAssets[$resourceIdentifier];
			}
		}
		$object = parent::convertFrom($source, $targetType, $convertedChildProperties, $configuration);

		if ($object instanceof AssetInterface) {
			$this->applyTypeSpecificHandling($object, $source, $convertedChildProperties, $configuration);

			if ($this->persistenceManager->isNewObject($object)) {
				$this->assetRepository->add($object);
			} else {
				$this->assetRepository->update($object);
			}

			$this->resourcesAlreadyConvertedToAssets[$this->persistenceManager->getIdentifierByObject($object->getResource())] = $object;
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
	protected function buildObject(array &$possibleConstructorArgumentValues, $objectType) {
		$className = $this->objectManager->getClassNameByObjectName($objectType) ?: static::$defaultNewAssetType;
		if (isset($possibleConstructorArgumentValues['resource'])) {
			$possibleAsset = $this->assetRepository->findOneByResource($possibleConstructorArgumentValues['resource']);
			if ($possibleAsset !== NULL) {
				return $possibleAsset;
				// TODO: Should probably throw an exception if the asset doesn't match the objectType expected.
			}
		}
		return parent::buildObject($possibleConstructorArgumentValues, $className);
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
	protected function fetchObjectFromPersistence($identity, $targetType) {
		if (is_string($identity)) {
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
	 * @return void
	 */
	protected function applyTypeSpecificHandling($asset, $source, $convertedChildProperties, $configuration) {}
}
