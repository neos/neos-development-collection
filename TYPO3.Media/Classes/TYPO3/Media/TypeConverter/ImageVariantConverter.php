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

/**
 * This converter transforms arrays to \TYPO3\Media\Domain\Model\ImageVariant objects.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class ImageVariantConverter extends \TYPO3\Flow\Property\TypeConverter\AbstractTypeConverter
{
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
    public function getSourceChildPropertiesToBeConverted($source)
    {
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
    public function getTypeOfChildProperty($targetType, $propertyName, \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration)
    {
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
    public function convertFrom($source, $targetType, array $convertedChildProperties = array(), \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration = null)
    {
        if (!isset($convertedChildProperties['originalImage']) || !$convertedChildProperties['originalImage'] instanceof \TYPO3\Media\Domain\Model\Image) {
            return null;
        }

        return new \TYPO3\Media\Domain\Model\ImageVariant($convertedChildProperties['originalImage'], $convertedChildProperties['processingInstructions']);
    }
}
