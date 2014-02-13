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

/**
 * An extended ArrayConverter to convert an array of entity identifiers by converting all child properties to the
 * given array element type.
 *
 * @Flow\Scope("singleton")
 */
class ArrayConverter extends \TYPO3\Flow\Property\TypeConverter\ArrayConverter {

	/**
	 * @var array<string>
	 */
	protected $sourceTypes = array('array');

	/**
	 * @var integer
	 */
	protected $priority = 2;

	/**
	 * Check if there is an UUID in the array, ideally we would use the target type here, but that is normalized
	 * and does not contain the element type of the array.
	 *
	 * @param mixed $source the source data
	 * @param string $targetType the type to convert to.
	 * @return boolean TRUE if this TypeConverter can convert from $source to $targetType, FALSE otherwise.
	 */
	public function canConvertFrom($source, $targetType) {
		return isset($source[0]) && is_string($source[0]) && preg_match(\TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter::PATTERN_MATCH_UUID, $source[0]) === 1;
	}

	/**
	 * @param array|string $source
	 * @param string $targetType
	 * @param array $convertedChildProperties
	 * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration
	 * @return array
	 */
	public function convertFrom($source, $targetType, array $convertedChildProperties = array(), \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		return $convertedChildProperties;
	}

	/**
	 * Returns the source, if it is an array, otherwise an empty array.
	 *
	 * @param mixed $source
	 * @return array
	 */
	public function getSourceChildPropertiesToBeConverted($source) {
		return $source;
	}

	/**
	 * Return the type of a given sub-property inside the $targetType
	 *
	 * TODO There could be a chance that the elementType is NULL, this is not supported by the PropertyMapper! We would need to change canConvertFrom behavior to not pass the normalized type then.
	 *
	 * @param string $targetType
	 * @param string $propertyName
	 * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration
	 * @return string
	 */
	public function getTypeOfChildProperty($targetType, $propertyName, \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration) {
		$parsedTargetType = \TYPO3\Flow\Utility\TypeHandling::parseType($targetType);
		return $parsedTargetType['elementType'];
	}

}
