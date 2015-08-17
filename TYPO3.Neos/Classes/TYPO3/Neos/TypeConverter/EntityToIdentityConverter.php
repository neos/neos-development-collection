<?php
namespace TYPO3\Neos\TypeConverter;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\Property\PropertyMappingConfigurationInterface;
use TYPO3\Flow\Property\TypeConverter\AbstractTypeConverter;
use TYPO3\Flow\Utility\TypeHandling;

/**
 * Converts the given entity to a JSON representation containing the identity and object type
 */
class EntityToIdentityConverter extends AbstractTypeConverter {

	/**
	 * The source types this converter can convert.
	 *
	 * @var array<string>
	 */
	protected $sourceTypes = array('object');

	/**
	 * The target type this converter can convert to.
	 *
	 * @var string
	 */
	protected $targetType = 'array';

	/**
	 * The priority for this converter.
	 *
	 * @var integer
	 */
	protected $priority = 0;

	/**
	 * @Flow\Inject
	 * @var PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * Check if the given object has an identity.
	 *
	 * @param object $source the source data
	 * @param string $targetType the type to convert to.
	 * @return boolean TRUE if this TypeConverter can convert from $source to $targetType, FALSE otherwise.
	 */
	public function canConvertFrom($source, $targetType) {
		$identifier = $this->persistenceManager->getIdentifierByObject($source);
		return ($identifier !== NULL);
	}


	/**
	 * Converts the given source object to an array containing the type and identity.
	 *
	 * @param object $source
	 * @param string $targetType
	 * @param array $convertedChildProperties
	 * @param PropertyMappingConfigurationInterface $configuration
	 * @return array
	 */
	public function convertFrom($source, $targetType, array $convertedChildProperties = array(), PropertyMappingConfigurationInterface $configuration = NULL) {
		return [
			'__identity' => $this->persistenceManager->getIdentifierByObject($source),
			'__type' => TypeHandling::getTypeForValue($source)
		];
	}


}