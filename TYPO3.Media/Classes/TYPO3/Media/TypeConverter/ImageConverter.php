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
 * This converter transforms arrays to \TYPO3\Media\Domain\Model\Image objects.
 *
 * Note: It works only on non-persisted Image instances (no __identity in source).
 *
 * @api
 * @Flow\Scope("singleton")
 */
class ImageConverter extends \TYPO3\Flow\Property\TypeConverter\AbstractTypeConverter
{
    /**
     * @var array
     */
    protected $sourceTypes = array('array');

    /**
     * @var string
     */
    protected $targetType = 'TYPO3\Media\Domain\Model\Image';

    /**
     * @var integer
     */
    protected $priority = 1;

    /**
     * If $source has an identity, we have a persisted Image, and therefore
     * this type converter should withdraw and let the PersistedObjectConverter kick in.
     *
     * @param mixed $source The source for the to-build Image
     * @param string $targetType Should always be 'TYPO3\Media\Domain\Model\Image'
     * @return boolean
     */
    public function canConvertFrom($source, $targetType)
    {
        if (array_key_exists('__identity', $source)) {
            return false;
        }

        return true;
    }

    /**
     * Convert all properties in the source array
     *
     * @param mixed $source
     * @return array
     */
    public function getSourceChildPropertiesToBeConverted($source)
    {
        return $source;
    }

    /**
     * Convert the property "resource"
     *
     * @param string $targetType
     * @param string $propertyName
     * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration
     * @return string
     */
    public function getTypeOfChildProperty($targetType, $propertyName, \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration)
    {
        switch ($propertyName) {
            case 'resource':
                return 'TYPO3\Flow\Resource\Resource';
            case 'title':
                return 'string';
        }
    }

    /**
     * Convert an object from $source to an \TYPO3\Media\Domain\Model\Image
     *
     * @param mixed $source
     * @param string $targetType must be 'TYPO3\Media\Domain\Model\Image'
     * @param array $convertedChildProperties
     * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration
     * @return \TYPO3\Media\Domain\Model\Image|\TYPO3\Flow\Validation\Error The converted Image, a Validation Error or NULL
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = array(), \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration = null)
    {
        if (!isset($convertedChildProperties['resource']) || !$convertedChildProperties['resource'] instanceof \TYPO3\Flow\Resource\Resource) {
            return null;
        }
        try {
            $image = new \TYPO3\Media\Domain\Model\Image($convertedChildProperties['resource']);
            if (isset($convertedChildProperties['title'])) {
                $image->setTitle($convertedChildProperties['title']);
            }

            return $image;
        } catch (\TYPO3\Media\Exception\ImageFileException $exception) {
            return new \TYPO3\Flow\Validation\Error($exception->getMessage(), $exception->getCode());
        }
    }
}
