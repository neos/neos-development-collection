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

/**
 * This converter transforms TYPO3.Media Image and ImageVariant objects to arrays.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class ArrayConverter extends \TYPO3\Flow\Property\TypeConverter\AbstractTypeConverter {

	/**
	 * @var array
	 */
	protected $sourceTypes = array('TYPO3\Media\Domain\Model\ImageVariant', 'TYPO3\Media\Domain\Model\Image');

	/**
	 * @var string
	 */
	protected $targetType = 'array';

	/**
	 * @var integer
	 */
	protected $priority = 1;

	/**
	 * Convert properties in the source depending on type
	 *
	 * @param mixed $source
	 * @return array
	 */
	public function getSourceChildPropertiesToBeConverted($source) {
		switch (TRUE) {
			case $source instanceof \TYPO3\Media\Domain\Model\ImageVariant:
				return array(
					'originalImage' => $source->getOriginalImage(),
					'processingInstructions' => $source->getProcessingInstructions()
				);
			case $source instanceof \TYPO3\Media\Domain\Model\Image:
				return array(
					'resource' => $source->getResource()
				);
			default:
				return array();
		}
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
		return 'array';
	}

	/**
	 * Convert an object from $source to an \TYPO3\Media\Domain\Model\ImageVariant
	 *
	 * @param array $source
	 * @param string $targetType must be 'TYPO3\Media\Domain\Model\ImageVariant'
	 * @param array $convertedChildProperties
	 * @param PropertyMappingConfigurationInterface $configuration
	 * @return \TYPO3\Media\Domain\Model\ImageVariant|\TYPO3\Flow\Validation\Error The converted Image, a Validation Error or NULL
	 */
	public function convertFrom($source, $targetType, array $convertedChildProperties = array(), PropertyMappingConfigurationInterface $configuration = NULL) {
		switch (TRUE) {
			case $source instanceof \TYPO3\Media\Domain\Model\ImageVariant:
				if (!isset($convertedChildProperties['originalImage']) || !is_array($convertedChildProperties['originalImage'])) {
					return NULL;
				}

				return array(
					'originalImage' => $convertedChildProperties['originalImage'],
					'processingInstructions' => $convertedChildProperties['processingInstructions']
				);
			case $source instanceof \TYPO3\Media\Domain\Model\Image:
				if (!isset($convertedChildProperties['resource']) || !is_array($convertedChildProperties['resource'])) {
					return NULL;
				}

				return array(
					'title' => $source->getTitle(),
					'resource' => $convertedChildProperties['resource']
				);
			default:
				throw new \TYPO3\Flow\Property\Exception\TypeConverterException('Conversion to array failed due to unsupported input', 1404977796);
		}
	}
}
