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
