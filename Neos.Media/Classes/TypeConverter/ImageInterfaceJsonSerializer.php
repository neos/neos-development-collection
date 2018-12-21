<?php
namespace Neos\Media\TypeConverter;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\Media\Domain\Model\ImageInterface;

/**
 * This converter transforms \Neos\Media\Domain\Model\ImageInterface (Image or ImageVariant) objects to json representations.
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
     * Convert an object from \Neos\Media\Domain\Model\ImageInterface to a json representation.
     *
     * @param ImageInterface $source
     * @param string $targetType must be 'string'
     * @param array $convertedChildProperties
     * @param PropertyMappingConfigurationInterface $configuration
     * @return string The converted ImageInterface
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null)
    {
        $data = parent::convertFrom($source, 'array', $convertedChildProperties, $configuration);
        return json_encode($data);
    }
}
