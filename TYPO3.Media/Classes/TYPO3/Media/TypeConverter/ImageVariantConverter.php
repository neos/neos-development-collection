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

/**
 * This converter transforms arrays to \TYPO3\Media\Domain\Model\ImageVariant objects
 *
 * @api
 * @Flow\Scope("singleton")
 */
class ImageVariantConverter extends \TYPO3\Flow\Property\TypeConverter\AbstractTypeConverter {

	/**
	 * @var array
	 */
	protected $sourceTypes = array('array');

	/**
	 * @var string
	 */
	protected $targetType = 'TYPO3\Media\Domain\Model\ImageVariant';

	/**
	 * @var integer
	 */
	protected $priority = 1;

	/**
	 * Convert all properties in the source array
	 *
	 * @param mixed $source
	 * @return array
	 */
	public function getSourceChildPropertiesToBeConverted($source) {
		return array(
			'originalImage' => $source['originalImage'],
			'processingInstructions' => $source['processingInstructions']
		);
	}

	/**
	 * Define types of to be converted child properties
	 *
	 * @param string $targetType
	 * @param string $propertyName
	 * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration
	 * @return string
	 */
	public function getTypeOfChildProperty($targetType, $propertyName, \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration) {
		if ($propertyName === 'originalImage') {
			return '\TYPO3\Media\Domain\Model\Image';
		}
		if ($propertyName === 'processingInstructions') {
			return 'array';
		}
	}

	/**
	 * Convert an object from $source to an \TYPO3\Media\Domain\Model\ImageVariant
	 *
	 * @param array $source
	 * @param string $targetType must be 'TYPO3\Media\Domain\Model\ImageVariant'
	 * @param array $convertedChildProperties
	 * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration
	 * @return \TYPO3\Media\Domain\Model\ImageVariant|\TYPO3\Flow\Validation\Error The converted Image, a Validation Error or NULL
	 */
	public function convertFrom($source, $targetType, array $convertedChildProperties = array(), \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		if (!isset($convertedChildProperties['originalImage']) || !$convertedChildProperties['originalImage'] instanceof \TYPO3\Media\Domain\Model\Image) {
			return NULL;
		}
		return new \TYPO3\Media\Domain\Model\ImageVariant($convertedChildProperties['originalImage'], $convertedChildProperties['processingInstructions']);
	}
}
