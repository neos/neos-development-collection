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
use TYPO3\Media\Domain\Model\ImageInterface;

/**
 * This converter transforms \TYPO3\Media\Domain\Model\ImageInterface (Image or ImageVariant) objects to json representations.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class ImageInterfaceJsonSerializer extends ImageInterfaceArrayPresenter
{
    /**
     * @var string
     */
    protected $targetType = 'string';

    /**
     * @var integer
     */
    protected $priority = 1;

    /**
     * Convert an object from \TYPO3\Media\Domain\Model\ImageInterface to a json representation.
     *
     * @param ImageInterface $source
     * @param string $targetType must be 'string'
     * @param array $convertedChildProperties
     * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration
     * @return string The converted ImageInterface
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = array(), \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration = null)
    {
        $data = parent::convertFrom($source, 'array', $convertedChildProperties, $configuration);
        return json_encode($data);
    }
}
